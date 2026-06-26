@php
    $rp = fn ($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
    $earnings = $payslip->lines->where('type', 'earning');
    $deductions = $payslip->lines->where('type', 'deduction');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Slip Gaji — {{ $payslip->employee?->employee_no }}</title>
    <style>
        @page { margin: 28px 32px; }
        * { font-family: "DejaVu Sans", sans-serif; }
        body { color: #0E1A3A; font-size: 12px; margin: 0; }
        .head-table { width: 100%; border-bottom: 2px solid #2F54C9; padding-bottom: 8px; margin-bottom: 16px; }
        .company { font-size: 18px; font-weight: bold; color: #2F54C9; }
        .muted { color: #64748b; }
        .doc-title { font-size: 15px; font-weight: bold; text-transform: uppercase; }
        .meta { width: 100%; margin-bottom: 16px; }
        .meta td { padding: 2px 0; }
        .meta .lbl { color: #64748b; width: 70px; }
        h4 { margin: 0 0 4px; font-size: 12px; text-transform: uppercase; color: #2F54C9; }
        .split { width: 100%; }
        .split > td { vertical-align: top; width: 50%; }
        .split > td:first-child { padding-right: 14px; }
        .split > td:last-child { padding-left: 14px; }
        .lines { width: 100%; border-collapse: collapse; }
        .lines td { padding: 4px 0; border-bottom: 1px dotted #E5E9F2; }
        .lines td.amt { text-align: right; }
        .total td { border-top: 2px solid #0E1A3A; border-bottom: none; font-weight: bold; padding-top: 6px; }
        .net { width: 100%; background: #F4F6FB; margin-top: 16px; }
        .net td { padding: 12px 14px; font-size: 15px; font-weight: bold; }
        .net td.amt { text-align: right; color: #16A34A; }
        .foot { margin-top: 20px; font-size: 10px; color: #94a3b8; }
    </style>
</head>
<body>
    <table class="head-table">
        <tr>
            <td>
                @if (! empty($logoPath) && file_exists($logoPath))
                    <img src="{{ $logoPath }}" style="max-height:42px; max-width:160px; margin-bottom:4px;">
                @endif
                <div class="company">{{ $company?->name ?? 'AvanaHR' }}</div>
                <div class="muted">Slip Gaji Karyawan</div>
            </td>
            <td style="text-align:right">
                <div class="doc-title">Slip Gaji</div>
                <div class="muted">{{ $payslip->run?->period?->code }}</div>
                <div class="muted">No. {{ $payslip->run?->run_no }}</div>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td class="lbl">Nama</td><td>{{ $payslip->employee?->fullName() }}</td>
            <td class="lbl">NIK</td><td>{{ $payslip->employee?->employee_no }}</td>
        </tr>
        <tr>
            <td class="lbl">Periode</td><td>{{ $payslip->run?->period?->code }}</td>
            <td class="lbl">Status</td><td>{{ $payslip->snapshot['ptkp_status'] ?? '-' }}</td>
        </tr>
    </table>

    <table class="split">
        <tr>
            <td>
                <h4>Penghasilan</h4>
                <table class="lines">
                    @forelse ($earnings as $line)
                        <tr><td>{{ $line->component_name }}</td><td class="amt">{{ $rp($line->amount) }}</td></tr>
                    @empty
                        <tr><td class="muted">—</td><td></td></tr>
                    @endforelse
                    <tr class="total"><td>Bruto</td><td class="amt">{{ $rp($payslip->gross) }}</td></tr>
                </table>
            </td>
            <td>
                <h4>Potongan</h4>
                <table class="lines">
                    @forelse ($deductions as $line)
                        <tr><td>{{ $line->component_name }}</td><td class="amt">{{ $rp($line->amount) }}</td></tr>
                    @empty
                        <tr><td class="muted">—</td><td></td></tr>
                    @endforelse
                    <tr class="total"><td>Total Potongan</td><td class="amt">{{ $rp($payslip->deductions) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="net">
        <tr>
            <td>Gaji Bersih (Netto)</td>
            <td class="amt">{{ $rp($payslip->net) }}</td>
        </tr>
    </table>

    <div class="foot">
        Dokumen ini dihasilkan otomatis oleh AvanaHR. PPh 21 metode TER. BPJS sesuai parameter berlaku.
        Komponen pajak/BPJS placeholder wajib divalidasi sesuai regulasi.
    </div>
</body>
</html>
