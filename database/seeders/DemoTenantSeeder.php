<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeEmployment;
use App\Models\Holiday;
use App\Models\JobGrade;
use App\Models\JobLevel;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Models\WorkCalendar;
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
