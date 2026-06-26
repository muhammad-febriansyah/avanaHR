<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeBpjsProfile\StoreBpjsProfileRequest;
use App\Http\Requests\EmployeeTaxProfile\StoreTaxProfileRequest;
use App\Models\Employee;
use App\Models\EmployeeBpjsProfile;
use App\Models\EmployeeTaxProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Per-employee tax (PTKP) and BPJS profiles, effective-dated. These feed the
 * payroll engine's PPh 21 and BPJS calculations.
 */
class EmployeeTaxBpjsController extends Controller
{
    public function index(Request $request, Employee $employee): Response
    {
        abort_unless($request->user()?->can('payroll.view'), 403);

        return Inertia::render('employees/tax-bpjs', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Karyawan', 'href' => route('employees.index')],
                ['title' => $employee->fullName(), 'href' => route('employees.show', $employee)],
                ['title' => 'Pajak & BPJS', 'href' => route('employees.tax-bpjs.index', $employee)],
            ],
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->fullName(),
                'employee_no' => $employee->employee_no,
            ],
            'taxProfiles' => $employee->taxProfiles()
                ->orderByDesc('effective_date')->orderByDesc('id')->get()
                ->map(fn (EmployeeTaxProfile $tp): array => [
                    'id' => $tp->id,
                    'effective_date' => $tp->effective_date?->format('Y-m-d'),
                    'ptkp_status' => $tp->ptkp_status,
                    'npwp' => $tp->npwp,
                    'tax_method' => $tp->tax_method,
                    'beginning_ytd' => (int) $tp->beginning_ytd,
                ]),
            'bpjsProfiles' => $employee->bpjsProfiles()
                ->orderByDesc('effective_date')->orderByDesc('id')->get()
                ->map(fn (EmployeeBpjsProfile $bp): array => [
                    'id' => $bp->id,
                    'effective_date' => $bp->effective_date?->format('Y-m-d'),
                    'bpjs_kesehatan_no' => $bp->bpjs_kesehatan_no,
                    'bpjs_tk_no' => $bp->bpjs_tk_no,
                    'kesehatan_basis' => (int) $bp->kesehatan_basis,
                    'tk_basis' => (int) $bp->tk_basis,
                    'participation_flags' => $bp->participation_flags ?? [],
                ]),
            'ptkpOptions' => $this->ptkpOptions(),
        ]);
    }

    public function createTax(Request $request, Employee $employee): Response
    {
        abort_unless($request->user()?->can('payroll.run'), 403);

        return Inertia::render('employees/tax-profile-create', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Karyawan', 'href' => route('employees.index')],
                ['title' => $employee->fullName(), 'href' => route('employees.show', $employee)],
                ['title' => 'Pajak & BPJS', 'href' => route('employees.tax-bpjs.index', $employee)],
                ['title' => 'Tambah Profil Pajak', 'href' => route('employees.tax-profiles.create', $employee)],
            ],
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->fullName(),
                'employee_no' => $employee->employee_no,
            ],
            'ptkpOptions' => $this->ptkpOptions(),
            'taxMethodOptions' => $this->taxMethodOptions(),
        ]);
    }

    public function createBpjs(Request $request, Employee $employee): Response
    {
        abort_unless($request->user()?->can('payroll.run'), 403);

        return Inertia::render('employees/bpjs-profile-create', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Karyawan', 'href' => route('employees.index')],
                ['title' => $employee->fullName(), 'href' => route('employees.show', $employee)],
                ['title' => 'Pajak & BPJS', 'href' => route('employees.tax-bpjs.index', $employee)],
                ['title' => 'Tambah Profil BPJS', 'href' => route('employees.bpjs-profiles.create', $employee)],
            ],
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->fullName(),
                'employee_no' => $employee->employee_no,
            ],
            'participationOptions' => $this->participationOptions(),
        ]);
    }

    public function storeTax(StoreTaxProfileRequest $request, Employee $employee): RedirectResponse
    {
        $employee->taxProfiles()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profil pajak ditambahkan.']);

        return back();
    }

    public function destroyTax(Request $request, EmployeeTaxProfile $taxProfile): RedirectResponse
    {
        abort_unless($request->user()?->can('payroll.run'), 403);

        $taxProfile->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profil pajak dihapus.']);

        return back();
    }

    public function storeBpjs(StoreBpjsProfileRequest $request, Employee $employee): RedirectResponse
    {
        $data = $request->validated();

        $employee->bpjsProfiles()->create([
            'effective_date' => $data['effective_date'],
            'bpjs_kesehatan_no' => $data['bpjs_kesehatan_no'] ?? null,
            'bpjs_tk_no' => $data['bpjs_tk_no'] ?? null,
            'kesehatan_basis' => $data['kesehatan_basis'] ?? null,
            'tk_basis' => $data['tk_basis'] ?? null,
            'participation_flags' => [
                'kesehatan' => (bool) ($data['kesehatan'] ?? false),
                'jht' => (bool) ($data['jht'] ?? false),
                'jkk' => (bool) ($data['jkk'] ?? false),
                'jkm' => (bool) ($data['jkm'] ?? false),
                'jp' => (bool) ($data['jp'] ?? false),
            ],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profil BPJS ditambahkan.']);

        return back();
    }

    public function destroyBpjs(Request $request, EmployeeBpjsProfile $bpjsProfile): RedirectResponse
    {
        abort_unless($request->user()?->can('payroll.run'), 403);

        $bpjsProfile->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profil BPJS dihapus.']);

        return back();
    }

    /**
     * @return list<string>
     */
    private function ptkpOptions(): array
    {
        return ['TK/0', 'TK/1', 'TK/2', 'TK/3', 'K/0', 'K/1', 'K/2', 'K/3'];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function taxMethodOptions(): array
    {
        return [
            ['value' => 'ter', 'label' => 'TER'],
            ['value' => 'gross', 'label' => 'Gross'],
            ['value' => 'nett', 'label' => 'Nett'],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function participationOptions(): array
    {
        return [
            ['value' => 'kesehatan', 'label' => 'Kesehatan'],
            ['value' => 'jht', 'label' => 'JHT'],
            ['value' => 'jkk', 'label' => 'JKK'],
            ['value' => 'jkm', 'label' => 'JKM'],
            ['value' => 'jp', 'label' => 'JP'],
        ];
    }
}
