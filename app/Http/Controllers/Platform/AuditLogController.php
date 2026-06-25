<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only('search', 'event');

        $logs = AuditLog::query()
            ->with(['user:id,name,email', 'tenant:id,name'])
            ->when($filters['event'] ?? null, fn ($query, $event) => $query->where('event', $event))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($q) use ($search) {
                $q->where('auditable_type', 'like', "%{$search}%")
                    ->orWhere('event', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            }))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (AuditLog $log): array => [
                'id' => $log->id,
                'event' => $log->event,
                'entity' => $log->auditable_type ? class_basename($log->auditable_type) : null,
                'entity_id' => $log->auditable_id,
                'user_name' => $log->user?->name,
                'user_email' => $log->user?->email,
                'tenant_name' => $log->tenant?->name,
                'ip' => $log->ip,
                'changes' => $this->diff($log),
                'created_at' => $log->created_at?->format('Y-m-d H:i:s'),
            ]);

        return Inertia::render('platform/audit-logs/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Audit Log', 'href' => route('platform.audit-logs.index')],
            ],
            'logs' => $logs,
            'filters' => (object) $filters,
            'events' => $this->eventOptions(),
        ]);
    }

    /**
     * Build a flat field-level diff (old → new) for changed attributes.
     *
     * @return list<array{field: string, old: mixed, new: mixed}>
     */
    private function diff(AuditLog $log): array
    {
        $old = $log->old_values ?? [];
        $new = $log->new_values ?? [];
        $fields = array_keys($old + $new);

        return collect($fields)
            ->map(fn (string $field): array => [
                'field' => $field,
                'old' => $this->stringify($old[$field] ?? null),
                'new' => $this->stringify($new[$field] ?? null),
            ])
            ->all();
    }

    private function stringify(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return is_scalar($value) ? (string) $value : Str::limit(json_encode($value), 80);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function eventOptions(): array
    {
        return [
            ['value' => 'created', 'label' => 'Dibuat'],
            ['value' => 'updated', 'label' => 'Diubah'],
            ['value' => 'deleted', 'label' => 'Dihapus'],
        ];
    }
}
