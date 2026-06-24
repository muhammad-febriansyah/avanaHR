<?php

namespace App\Http\Controllers;

use App\Http\Requests\BankFile\StoreBankFileRequest;
use App\Models\BankFile;
use App\Models\PayrollRun;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BankFileController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', BankFile::class);

        $filters = $request->only('run_id');

        $files = BankFile::query()
            ->with('run:id,run_no')
            ->when($filters['run_id'] ?? null, fn ($query, $runId) => $query->where('run_id', $runId))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (BankFile $file): array => [
                'id' => $file->id,
                'run_no' => $file->run?->run_no,
                'bank_code' => $file->bank_code,
                'format' => $file->format,
                'total' => (int) $file->total,
                'file_path' => $file->file_path,
            ]);

        return Inertia::render('bank-files/index', [
            'bankFiles' => $files,
            'filters' => (object) $filters,
            'banks' => $this->bankOptions(),
            'formats' => $this->formatOptions(),
            'options' => [
                'runs' => PayrollRun::orderByDesc('id')->get(['id', 'run_no']),
            ],
        ]);
    }

    public function store(StoreBankFileRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $run = PayrollRun::findOrFail($data['run_id']);

        $file = BankFile::create([
            ...$data,
            'total' => (int) $run->net_total,
            'file_path' => sprintf(
                'bank-files/%s-%s.%s',
                Str::slug($run->run_no),
                Str::lower($data['bank_code']),
                $data['format'],
            ),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "File bank {$file->bank_code} berhasil dibuat."]);

        return back();
    }

    public function destroy(BankFile $bankFile): RedirectResponse
    {
        $this->authorize('delete', $bankFile);

        $bankFile->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'File bank berhasil dihapus.']);

        return back();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function bankOptions(): array
    {
        return array_map(
            fn (string $bank): array => ['value' => $bank, 'label' => $bank],
            ['BCA', 'BNI', 'BRI', 'Mandiri', 'CIMB', 'Permata'],
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function formatOptions(): array
    {
        return [
            ['value' => 'csv', 'label' => 'CSV'],
            ['value' => 'txt', 'label' => 'TXT'],
        ];
    }
}
