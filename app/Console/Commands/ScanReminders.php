<?php

namespace App\Console\Commands;

use App\Approvals\ApprovalNotifier;
use App\Enums\EmploymentType;
use App\Models\DocumentReminder;
use App\Models\EmployeeDocument;
use App\Models\EmployeeEmployment;
use App\Models\Notification;
use App\Models\Scopes\TenantScope;
use App\Models\User;
use App\Support\CurrentTenant;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Spatie\Permission\PermissionRegistrar;

/**
 * Scans for two HR compliance reminders and notifies HR (email rows delivered
 * by notifications:dispatch). Runs cross-tenant, idempotent per item:
 *
 *  - Employee documents nearing/expired against their reminder lead time
 *    (deduped by a {@see DocumentReminder} marker row).
 *  - Fixed-term (PKWT / intern / outsource) contracts whose end-date falls
 *    within the configured window (deduped by an existing notification).
 */
class ScanReminders extends Command
{
    protected $signature = 'reminders:scan';

    protected $description = 'Notify HR of expiring employee documents and ending fixed-term contracts.';

    public function handle(ApprovalNotifier $notifier, CurrentTenant $currentTenant): int
    {
        $today = CarbonImmutable::today();
        $documentReminders = $this->scanDocuments($notifier, $currentTenant, $today);
        $contractAlerts = $this->scanContracts($notifier, $currentTenant, $today);

        $currentTenant->forget();
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        $this->info("Pengingat — dokumen: {$documentReminders}, kontrak: {$contractAlerts}.");

        return self::SUCCESS;
    }

    private function scanDocuments(ApprovalNotifier $notifier, CurrentTenant $currentTenant, CarbonImmutable $today): int
    {
        $defaultDays = (int) config('reminders.document_default_days', 30);

        $documents = EmployeeDocument::query()
            ->withoutGlobalScope(TenantScope::class)
            ->whereNotNull('expired_at')
            ->with(['tenant', 'employee', 'reminders'])
            ->orderBy('tenant_id')
            ->get();

        $count = 0;
        foreach ($documents as $document) {
            $leadDays = $document->reminder_days !== null ? (int) $document->reminder_days : $defaultDays;
            $remindAt = CarbonImmutable::parse($document->expired_at)->subDays($leadDays);

            if ($today->lt($remindAt)) {
                continue;
            }
            if ($document->reminders->isNotEmpty()) {
                continue; // already reminded for this document
            }

            $this->switchTenant($currentTenant, (int) $document->tenant_id, $document->tenant);

            DocumentReminder::create([
                'tenant_id' => $document->tenant_id,
                'employee_document_id' => $document->id,
                'remind_at' => $remindAt->toDateString(),
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            $notifier->toUsers((int) $document->tenant_id, $this->recipients(), 'document.expiring', [
                'title' => 'Dokumen '.$document->document_type.' '.($document->employee?->fullName() ?? 'karyawan')
                    .' kedaluwarsa '.CarbonImmutable::parse($document->expired_at)->format('d M Y'),
                'document_id' => $document->id,
                'document_type' => $document->document_type,
                'expired_at' => CarbonImmutable::parse($document->expired_at)->toDateString(),
            ]);
            $count++;
        }

        return $count;
    }

    private function scanContracts(ApprovalNotifier $notifier, CurrentTenant $currentTenant, CarbonImmutable $today): int
    {
        $windowEnd = $today->addDays((int) config('reminders.contract_days_before', 30));

        $employments = EmployeeEmployment::query()
            ->withoutGlobalScope(TenantScope::class)
            ->whereNotNull('end_date')
            ->where('employment_type', '!=', EmploymentType::Permanent->value)
            ->whereDate('effective_date', '<=', $today->toDateString())
            ->whereDate('end_date', '>=', $today->toDateString())
            ->whereDate('end_date', '<=', $windowEnd->toDateString())
            ->with(['tenant', 'employee'])
            ->orderBy('tenant_id')
            ->get();

        $count = 0;
        foreach ($employments as $employment) {
            $alreadyAlerted = Notification::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('type', 'contract.expiring')
                ->where('payload->employment_id', $employment->id)
                ->exists();

            if ($alreadyAlerted) {
                continue;
            }

            $this->switchTenant($currentTenant, (int) $employment->tenant_id, $employment->tenant);

            $notifier->toUsers((int) $employment->tenant_id, $this->recipients(), 'contract.expiring', [
                'title' => 'Kontrak '.($employment->employee?->fullName() ?? 'karyawan')
                    .' ('.$employment->employment_type->value.') berakhir '
                    .CarbonImmutable::parse($employment->end_date)->format('d M Y'),
                'employment_id' => $employment->id,
                'employee_id' => $employment->employee_id,
                'end_date' => CarbonImmutable::parse($employment->end_date)->toDateString(),
            ]);
            $count++;
        }

        return $count;
    }

    private function switchTenant(CurrentTenant $currentTenant, int $tenantId, $tenant): void
    {
        $currentTenant->set($tenant);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);
    }

    /**
     * HR recipient user ids in the current tenant/team context.
     *
     * @return array<int, int>
     */
    private function recipients(): array
    {
        return User::query()
            ->role((array) config('reminders.recipient_roles', []))
            ->pluck('id')
            ->all();
    }
}
