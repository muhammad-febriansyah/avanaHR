<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Backup;
use App\Models\BenefitClaim;
use App\Models\BenefitType;
use App\Models\BpjsParameter;
use App\Models\Branch;
use App\Models\ClearanceItem;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\CustomField;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeBenefit;
use App\Models\EmployeeBpjsProfile;
use App\Models\EmployeeDocument;
use App\Models\EmployeeEmployment;
use App\Models\EmployeeLifecycleEvent;
use App\Models\EmployeeMovement;
use App\Models\EmployeeSalaryComponent;
use App\Models\EmployeeTaxProfile;
use App\Models\Holiday;
use App\Models\HrTicket;
use App\Models\HrTicketMessage;
use App\Models\JobGrade;
use App\Models\JobLevel;
use App\Models\PayrollComponent;
use App\Models\Position;
use App\Models\SecurityEvent;
use App\Models\Tenant;
use App\Models\TenantProvision;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Models\WorkCalendar;
use App\Models\WorkVisit;
use App\Models\WorkVisitReport;
use App\Support\CurrentTenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DemoTenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Avana Demo',
            'slug' => 'avana-demo',
        ]);
        $tid = $tenant->id;

        app(CurrentTenant::class)->set($tenant);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tid);

        TenantSubscription::factory()->create(['tenant_id' => $tid]);

        $this->seedRbac($tid);

        // Organization
        $company = Company::factory()->create([
            'tenant_id' => $tid,
            'code' => 'CMP001',
            'name' => 'PT Avana Indonesia',
        ]);

        $branches = Branch::factory()->count(2)->create([
            'tenant_id' => $tid,
            'company_id' => $company->id,
        ]);

        $levels = JobLevel::factory()->count(4)->create(['tenant_id' => $tid]);
        $grades = JobGrade::factory()->count(4)->create(['tenant_id' => $tid]);
        $costCenters = CostCenter::factory()->count(2)->create(['tenant_id' => $tid]);

        $calendar = WorkCalendar::factory()->default()->create(['tenant_id' => $tid]);
        $this->seedHolidays($tid, $calendar);

        $departments = collect(['Human Resources', 'Finance', 'Engineering', 'Sales'])
            ->map(fn (string $name, int $i): Department => Department::factory()->create([
                'tenant_id' => $tid,
                'company_id' => $company->id,
                'code' => 'DEP'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'name' => $name,
            ]));

        $positions = $departments->map(fn (Department $dept): Position => Position::factory()->create([
            'tenant_id' => $tid,
            'department_id' => $dept->id,
            'job_level_id' => $levels->random()->id,
            'job_grade_id' => $grades->random()->id,
        ]));

        // Workforce: 20 employees, each with a current employment, a login account
        // (email @avanahr.id) and a real avatar downloaded into storage.
        $emailsUsed = [];
        $portraitSeq = ['men' => 0, 'women' => 0];

        collect(range(1, 20))->each(function (int $i) use ($tid, $company, $branches, $departments, $positions, $grades, $costCenters, $calendar, &$emailsUsed, &$portraitSeq): void {
            $gender = fake()->randomElement(['male', 'female']);

            $employee = Employee::factory()->create([
                'tenant_id' => $tid,
                'gender' => $gender,
            ]);

            EmployeeEmployment::factory()->create([
                'tenant_id' => $tid,
                'employee_id' => $employee->id,
                'company_id' => $company->id,
                'branch_id' => $branches->random()->id,
                'department_id' => $departments->random()->id,
                'position_id' => $positions->random()->id,
                'job_grade_id' => $grades->random()->id,
                'cost_center_id' => $costCenters->random()->id,
                'work_calendar_id' => $calendar->id,
            ]);

            $folder = $gender === 'female' ? 'women' : 'men';
            $email = $this->uniqueEmail($employee->first_name, $employee->last_name, $emailsUsed);

            $user = User::factory()->create([
                'tenant_id' => $tid,
                'employee_id' => $employee->id,
                'name' => $employee->fullName(),
                'email' => $email,
                'avatar_path' => $this->downloadAvatar($folder, $portraitSeq[$folder]++),
            ]);

            // First few employees act as managers.
            $user->assignRole($i <= 4 ? 'manager' : 'employee');
        });

        // HR admin user.
        $adminEmployee = Employee::factory()->create([
            'tenant_id' => $tid,
            'employee_no' => 'ADM00001',
            'first_name' => 'Admin',
            'last_name' => 'Avana',
            'gender' => 'male',
            'email' => 'admin@avanahr.id',
        ]);

        $admin = User::factory()->create([
            'tenant_id' => $tid,
            'employee_id' => $adminEmployee->id,
            'name' => 'Admin Avana',
            'email' => 'admin@avanahr.id',
            'avatar_path' => $this->downloadAvatar('men', 90),
        ]);
        $admin->assignRole(['hr-admin', 'tenant-admin']);

        $this->seedAuditLogs($tid, $admin->id);
        $this->seedSecurityEvents($tid, $admin->id);
        $this->seedHrTickets($tid, $admin->id);
        $this->seedEmployeeDocuments($tid);
        $this->seedLifecycleEvents($tid);
        $this->seedMovements($tid);
        $this->seedWorkVisits($tid);
        $this->seedBenefits($tid);
        $this->seedPayrollInputs($tid);

        CustomField::create([
            'tenant_id' => $tid,
            'entity_type' => 'employee',
            'key' => 'ukuran_seragam',
            'label' => 'Ukuran Seragam',
            'type' => 'select',
            'options' => ['S', 'M', 'L', 'XL'],
            'is_required' => false,
            'order' => 1,
        ]);
        CustomField::create([
            'tenant_id' => $tid,
            'entity_type' => 'employee',
            'key' => 'golongan_darah',
            'label' => 'Golongan Darah',
            'type' => 'select',
            'options' => ['A', 'B', 'AB', 'O'],
            'is_required' => false,
            'order' => 2,
        ]);

        TenantProvision::create([
            'tenant_id' => $tid,
            'status' => 'pending',
            'default_config_applied' => false,
            'admin_user_id' => $admin->id,
            'created_by' => $admin->id,
        ]);

        Backup::create([
            'tenant_id' => null,
            'type' => 'full',
            'status' => 'completed',
            'location' => 'backups/platform-20260601-020000.zip',
        ]);
        Backup::create([
            'tenant_id' => null,
            'type' => 'database',
            'status' => 'completed',
            'location' => 'backups/platform-20260615-020000.zip',
        ]);
    }

    /**
     * Seed lifecycle events: each demo employee joins, a few get promoted.
     */
    private function seedLifecycleEvents(int $tenantId): void
    {
        $employees = Employee::query()->where('tenant_id', $tenantId)->take(6)->get();

        foreach ($employees as $index => $employee) {
            EmployeeLifecycleEvent::create([
                'tenant_id' => $tenantId,
                'employee_id' => $employee->id,
                'type' => 'hire',
                'effective_date' => now()->subYears(2)->format('Y-m-d'),
                'from_json' => null,
                'to_json' => ['value' => 'Karyawan Tetap'],
                'reason' => 'Bergabung dengan perusahaan',
            ]);

            if ($index < 2) {
                EmployeeLifecycleEvent::create([
                    'tenant_id' => $tenantId,
                    'employee_id' => $employee->id,
                    'type' => 'promotion',
                    'effective_date' => now()->subMonths(6)->format('Y-m-d'),
                    'from_json' => ['value' => 'Staff'],
                    'to_json' => ['value' => 'Supervisor'],
                    'reason' => 'Kinerja sangat baik',
                ]);
            }
        }
    }

    /**
     * Seed a few draft movements (mutasi) for the first demo employees.
     */
    private function seedMovements(int $tenantId): void
    {
        $employees = Employee::query()->where('tenant_id', $tenantId)->take(2)->get();
        $position = Position::query()->where('tenant_id', $tenantId)->orderBy('name')->first();
        $department = Department::query()->where('tenant_id', $tenantId)->orderBy('name')->first();

        if ($employees->isEmpty()) {
            return;
        }

        if ($position !== null) {
            EmployeeMovement::create([
                'tenant_id' => $tenantId,
                'employee_id' => $employees[0]->id,
                'type' => 'promotion',
                'effective_date' => now()->subDays(3)->format('Y-m-d'),
                'status' => 'draft',
                'payload_json' => ['position_id' => $position->id],
                'before_json' => null,
                'after_json' => null,
                'reason' => 'Promosi atas kinerja yang sangat baik.',
                'requires_clearance' => false,
            ]);
        }

        if ($department !== null && isset($employees[1])) {
            EmployeeMovement::create([
                'tenant_id' => $tenantId,
                'employee_id' => $employees[1]->id,
                'type' => 'transfer',
                'effective_date' => now()->subDays(1)->format('Y-m-d'),
                'status' => 'draft',
                'payload_json' => ['department_id' => $department->id],
                'before_json' => null,
                'after_json' => null,
                'reason' => 'Mutasi ke departemen lain sesuai kebutuhan organisasi.',
                'requires_clearance' => false,
            ]);
        }

        $resign = EmployeeMovement::create([
            'tenant_id' => $tenantId,
            'employee_id' => $employees[0]->id,
            'type' => 'resign',
            'effective_date' => now()->addDays(7)->format('Y-m-d'),
            'status' => 'draft',
            'payload_json' => [],
            'before_json' => null,
            'after_json' => null,
            'reason' => 'Pengunduran diri untuk melanjutkan studi.',
            'requires_clearance' => true,
        ]);

        foreach (ClearanceItem::defaultChecklist() as $item) {
            ClearanceItem::create([
                'tenant_id' => $tenantId,
                'employee_movement_id' => $resign->id,
                'category' => $item['category'],
                'label' => $item['label'],
                'status' => 'pending',
            ]);
        }
    }

    /**
     * Seed a few work visits (kunjungan kerja): one pending, one approved with reports.
     */
    private function seedWorkVisits(int $tenantId): void
    {
        $employees = Employee::query()->where('tenant_id', $tenantId)->take(3)->get();

        if ($employees->isEmpty()) {
            return;
        }

        WorkVisit::factory()->create([
            'tenant_id' => $tenantId,
            'employee_id' => $employees[0]->id,
            'destination' => 'Surabaya',
            'purpose' => 'Kunjungan ke kantor cabang untuk koordinasi operasional.',
            'transport_mode' => 'pesawat',
            'estimated_cost' => 4_500_000,
            'start_date' => now()->addDays(5)->format('Y-m-d'),
            'end_date' => now()->addDays(7)->format('Y-m-d'),
        ]);

        $approved = WorkVisit::factory()->approved()->create([
            'tenant_id' => $tenantId,
            'employee_id' => $employees[1]->id ?? $employees[0]->id,
            'destination' => 'Bandung',
            'purpose' => 'Meeting dengan klien dan survei lokasi proyek.',
            'transport_mode' => 'kereta',
            'estimated_cost' => 2_000_000,
            'start_date' => now()->subDays(10)->format('Y-m-d'),
            'end_date' => now()->subDays(8)->format('Y-m-d'),
            'decided_at' => now()->subDays(11),
            'decision_note' => 'Disetujui sesuai anggaran perjalanan dinas.',
        ]);

        WorkVisitReport::create([
            'tenant_id' => $tenantId,
            'work_visit_id' => $approved->id,
            'visited_at' => now()->subDays(10)->format('Y-m-d'),
            'location' => 'Kantor Klien Bandung',
            'notes' => 'Meeting berjalan lancar, kesepakatan tercapai.',
        ]);

        WorkVisitReport::create([
            'tenant_id' => $tenantId,
            'work_visit_id' => $approved->id,
            'visited_at' => now()->subDays(9)->format('Y-m-d'),
            'location' => 'Lokasi Proyek Bandung',
            'notes' => 'Survei lokasi selesai, dokumentasi terlampir.',
        ]);
    }

    /**
     * Seed benefit plafons (types, per-employee allocations, and claims).
     */
    private function seedBenefits(int $tenantId): void
    {
        $employees = Employee::query()->where('tenant_id', $tenantId)->take(2)->get();

        if ($employees->isEmpty()) {
            return;
        }

        $year = (int) now()->year;

        $medical = BenefitType::factory()->create([
            'tenant_id' => $tenantId,
            'code' => 'MED',
            'name' => 'Medical',
            'default_quota' => 10_000_000,
            'description' => 'Plafon biaya kesehatan tahunan.',
            'is_active' => true,
        ]);

        BenefitType::factory()->create([
            'tenant_id' => $tenantId,
            'code' => 'KCM',
            'name' => 'Kacamata',
            'default_quota' => 2_000_000,
            'description' => 'Plafon penggantian kacamata tahunan.',
            'is_active' => true,
        ]);

        $firstBenefit = EmployeeBenefit::factory()->create([
            'tenant_id' => $tenantId,
            'employee_id' => $employees[0]->id,
            'benefit_type_id' => $medical->id,
            'period_year' => $year,
            'quota' => 10_000_000,
            'notes' => 'Plafon medical tahun berjalan.',
        ]);

        BenefitClaim::factory()->approved()->create([
            'tenant_id' => $tenantId,
            'employee_benefit_id' => $firstBenefit->id,
            'claim_date' => now()->subDays(20)->format('Y-m-d'),
            'amount' => 1_500_000,
            'description' => 'Rawat jalan klinik.',
            'decided_at' => now()->subDays(19),
            'decision_note' => 'Disetujui sesuai kuitansi.',
        ]);

        BenefitClaim::factory()->create([
            'tenant_id' => $tenantId,
            'employee_benefit_id' => $firstBenefit->id,
            'claim_date' => now()->subDays(3)->format('Y-m-d'),
            'amount' => 800_000,
            'description' => 'Pembelian obat resep dokter.',
        ]);

        $secondBenefit = EmployeeBenefit::factory()->create([
            'tenant_id' => $tenantId,
            'employee_id' => $employees[1]->id ?? $employees[0]->id,
            'benefit_type_id' => $medical->id,
            'period_year' => $year,
            'quota' => 10_000_000,
            'notes' => 'Plafon medical tahun berjalan.',
        ]);

        BenefitClaim::factory()->approved()->create([
            'tenant_id' => $tenantId,
            'employee_benefit_id' => $secondBenefit->id,
            'claim_date' => now()->subDays(15)->format('Y-m-d'),
            'amount' => 2_000_000,
            'description' => 'Pemeriksaan laboratorium.',
            'decided_at' => now()->subDays(14),
            'decision_note' => 'Disetujui.',
        ]);
    }

    /**
     * Seed payroll inputs so a run can actually be calculated: components,
     * BPJS parameters, and per-employee salary/tax/BPJS profiles, plus a draft
     * period + run for the current month. PTKP statuses limited to TER cat. A.
     */
    private function seedPayrollInputs(int $tenantId): void
    {
        $employees = Employee::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_no', '!=', 'ADM00001')
            ->take(6)
            ->get();

        if ($employees->isEmpty()) {
            return;
        }

        $basic = PayrollComponent::create([
            'tenant_id' => $tenantId, 'code' => 'GAPOK', 'name' => 'Gaji Pokok',
            'type' => 'earning', 'calc_type' => 'fixed', 'formula' => null,
            'is_taxable' => true, 'is_bpjs_base' => true,
        ]);
        $transport = PayrollComponent::create([
            'tenant_id' => $tenantId, 'code' => 'TRANS', 'name' => 'Tunjangan Transport',
            'type' => 'earning', 'calc_type' => 'fixed', 'formula' => null,
            'is_taxable' => true, 'is_bpjs_base' => false,
        ]);
        $meal = PayrollComponent::create([
            'tenant_id' => $tenantId, 'code' => 'MAKAN', 'name' => 'Tunjangan Makan',
            'type' => 'earning', 'calc_type' => 'fixed', 'formula' => null,
            'is_taxable' => true, 'is_bpjs_base' => false,
        ]);

        $defaults = config('payroll.bpjs_defaults');
        BpjsParameter::create([
            'tenant_id' => $tenantId,
            'effective_date' => '2024-01-01',
            'kes_rate_employee' => $defaults['kes_rate_employee'],
            'kes_rate_employer' => $defaults['kes_rate_employer'],
            'kes_cap' => $defaults['kes_cap'],
            'tk_rates' => $defaults['tk_rates'],
        ]);

        $ptkpCycle = ['TK/0', 'K/0', 'TK/1']; // semua kategori TER A
        $basicCycle = [8_000_000, 12_000_000, 15_000_000, 9_500_000, 7_000_000, 20_000_000];

        foreach ($employees as $index => $employee) {
            $basicAmount = $basicCycle[$index % count($basicCycle)];
            $effective = '2024-01-01';

            EmployeeSalaryComponent::create(['tenant_id' => $tenantId, 'employee_id' => $employee->id, 'component_id' => $basic->id, 'effective_date' => $effective, 'amount' => $basicAmount, 'rate' => 0]);
            EmployeeSalaryComponent::create(['tenant_id' => $tenantId, 'employee_id' => $employee->id, 'component_id' => $transport->id, 'effective_date' => $effective, 'amount' => 1_000_000, 'rate' => 0]);
            EmployeeSalaryComponent::create(['tenant_id' => $tenantId, 'employee_id' => $employee->id, 'component_id' => $meal->id, 'effective_date' => $effective, 'amount' => 750_000, 'rate' => 0]);

            EmployeeTaxProfile::create([
                'tenant_id' => $tenantId, 'employee_id' => $employee->id, 'effective_date' => $effective,
                'ptkp_status' => $ptkpCycle[$index % count($ptkpCycle)], 'npwp' => null,
                'tax_method' => 'ter', 'beginning_ytd' => 0,
            ]);

            EmployeeBpjsProfile::create([
                'tenant_id' => $tenantId, 'employee_id' => $employee->id, 'effective_date' => $effective,
                'bpjs_kesehatan_no' => null, 'bpjs_tk_no' => null,
                'kesehatan_basis' => $basicAmount, 'tk_basis' => $basicAmount,
                'participation_flags' => ['kesehatan' => true, 'jht' => true, 'jkk' => true, 'jkm' => true, 'jp' => true],
            ]);
        }

    }

    /**
     * Seed employee documents with varied expiry states (valid/expiring/expired).
     */
    private function seedEmployeeDocuments(int $tenantId): void
    {
        $employees = Employee::query()->where('tenant_id', $tenantId)->take(5)->get();

        foreach ($employees as $index => $employee) {
            EmployeeDocument::create([
                'tenant_id' => $tenantId,
                'employee_id' => $employee->id,
                'document_type' => ['ktp', 'npwp', 'contract', 'bpjs', 'passport'][$index % 5],
                'number' => fake()->numerify('################'),
                'issued_at' => now()->subYears(2)->format('Y-m-d'),
                'expired_at' => match ($index % 3) {
                    0 => now()->addYear()->format('Y-m-d'),
                    1 => now()->addDays(15)->format('Y-m-d'),
                    default => now()->subDays(20)->format('Y-m-d'),
                },
                'reminder_days' => 30,
                'access_level' => 'internal',
            ]);
        }
    }

    /**
     * Seed a few HR helpdesk tickets with an opening message each.
     */
    private function seedHrTickets(int $tenantId, int $adminId): void
    {
        $employees = Employee::query()->where('tenant_id', $tenantId)->take(3)->get();

        $samples = [
            ['category' => 'payroll', 'priority' => 'high', 'status' => 'open', 'subject' => 'Selisih potongan BPJS bulan ini', 'body' => 'Potongan BPJS di slip gaji saya terlihat lebih besar dari biasanya.'],
            ['category' => 'data', 'priority' => 'medium', 'status' => 'in_progress', 'subject' => 'Update nomor rekening', 'body' => 'Mohon update nomor rekening untuk transfer gaji.'],
            ['category' => 'leave', 'priority' => 'low', 'status' => 'resolved', 'subject' => 'Sisa saldo cuti tidak sesuai', 'body' => 'Saldo cuti tahunan saya tampak kurang 2 hari.'],
        ];

        foreach ($samples as $index => $sample) {
            $ticket = HrTicket::create([
                'tenant_id' => $tenantId,
                'ticket_no' => 'TKT-'.now()->format('Ym').'-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'employee_id' => $employees[$index]->id ?? null,
                'category' => $sample['category'],
                'subject' => $sample['subject'],
                'status' => $sample['status'],
                'priority' => $sample['priority'],
                'sla_due_at' => now()->addDay(),
                'resolved_at' => $sample['status'] === 'resolved' ? now() : null,
            ]);

            HrTicketMessage::create([
                'tenant_id' => $tenantId,
                'ticket_id' => $ticket->id,
                'user_id' => $adminId,
                'body' => $sample['body'],
            ]);
        }
    }

    /**
     * Seed a handful of security events so the platform monitor has data.
     */
    private function seedSecurityEvents(int $tenantId, int $adminId): void
    {
        $events = [
            ['type' => 'login_success', 'meta' => []],
            ['type' => 'login_failed', 'meta' => ['attempts' => 2]],
            ['type' => 'login_failed', 'meta' => ['attempts' => 4]],
            ['type' => 'locked_out', 'meta' => ['attempts' => 5]],
            ['type' => 'password_changed', 'meta' => []],
            ['type' => 'two_factor_enabled', 'meta' => []],
        ];

        foreach ($events as $offset => $event) {
            SecurityEvent::create([
                'tenant_id' => $tenantId,
                'user_id' => $adminId,
                'type' => $event['type'],
                'meta' => $event['meta'],
                'ip' => '103.122.10.'.(20 + $offset),
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
            ]);
        }
    }

    /**
     * Seed a handful of audit trail entries so the platform Audit Log has data.
     */
    private function seedAuditLogs(int $tenantId, int $adminId): void
    {
        $employees = Employee::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('id')
            ->take(6)
            ->get();

        foreach ($employees as $offset => $employee) {
            $event = ['created', 'updated', 'deleted'][$offset % 3];

            AuditLog::create([
                'tenant_id' => $tenantId,
                'user_id' => $adminId,
                'auditable_type' => Employee::class,
                'auditable_id' => $employee->id,
                'event' => $event,
                'old_values' => $event === 'created' ? null : ['status' => 'probation', 'job_grade' => 'G2'],
                'new_values' => $event === 'deleted' ? null : ['status' => 'active', 'job_grade' => 'G3'],
                'ip' => '103.122.10.'.(10 + $offset),
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
            ]);
        }
    }

    /**
     * Build a unique firstname.lastname@avanahr.id email.
     *
     * @param  list<string>  $used
     */
    private function uniqueEmail(string $first, string $last, array &$used): string
    {
        $base = (string) Str::of($first.'.'.$last)->ascii()->lower()->replaceMatches('/[^a-z0-9.]+/', '');
        $email = $base.'@avanahr.id';
        $suffix = 1;

        while (in_array($email, $used, true)) {
            $email = $base.(++$suffix).'@avanahr.id';
        }

        $used[] = $email;

        return $email;
    }

    /**
     * Download a gender-matched portrait into the public disk.
     * Skipped during tests so the suite stays offline and fast.
     */
    private function downloadAvatar(string $folder, int $index): ?string
    {
        if (app()->runningUnitTests()) {
            return null;
        }

        try {
            $response = Http::timeout(15)->get("https://randomuser.me/api/portraits/{$folder}/".($index % 100).'.jpg');

            if ($response->successful()) {
                $path = 'avatars/'.Str::uuid()->toString().'.jpg';
                Storage::disk('public')->put($path, $response->body());

                return $path;
            }
        } catch (\Throwable) {
            // Fall back to the generated ui-avatars URL via the model accessor.
        }

        return null;
    }

    /**
     * Create global permissions, a global super-admin role, and tenant roles.
     */
    private function seedRbac(int $tid): void
    {
        $registrar = app(PermissionRegistrar::class);

        $permissions = [
            'employee.view', 'employee.create', 'employee.update', 'employee.delete', 'employee.view_sensitive',
            'attendance.view', 'attendance.manage',
            'leave.view', 'leave.approve',
            'payroll.view', 'payroll.run', 'payroll.approve',
            'report.view', 'report.export',
            'setting.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Global super-admin (no team).
        $registrar->setPermissionsTeamId(null);
        Role::findOrCreate('super-admin', 'web')->syncPermissions(Permission::all());
        $registrar->setPermissionsTeamId($tid);

        $roles = [
            // Tenant-level configurator: roles, permissions, workflow, layout/menu.
            'tenant-admin' => ['setting.manage', 'employee.view', 'report.view'],
            'hr-admin' => $permissions,
            'payroll-officer' => ['payroll.view', 'payroll.run', 'attendance.view', 'employee.view'],
            'finance' => ['payroll.view', 'payroll.approve', 'report.view', 'report.export'],
            'manager' => ['employee.view', 'attendance.view', 'leave.view', 'leave.approve'],
            'employee' => ['leave.view'],
            'auditor' => ['employee.view', 'payroll.view', 'report.view'],
        ];

        foreach ($roles as $role => $grants) {
            Role::findOrCreate($role, 'web')->syncPermissions($grants);
        }
    }

    private function seedHolidays(int $tid, WorkCalendar $calendar): void
    {
        $year = now()->year;
        $holidays = [
            ['date' => "$year-01-01", 'name' => 'Tahun Baru'],
            ['date' => "$year-05-01", 'name' => 'Hari Buruh'],
            ['date' => "$year-08-17", 'name' => 'Hari Kemerdekaan'],
            ['date' => "$year-12-25", 'name' => 'Hari Natal'],
        ];

        foreach ($holidays as $holiday) {
            Holiday::create([
                'tenant_id' => $tid,
                'calendar_id' => $calendar->id,
                'date' => $holiday['date'],
                'name' => $holiday['name'],
                'is_national' => true,
            ]);
        }
    }
}
