@php
    $rp = fn ($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Bukti Potong 1721-A1 — {{ $year }}</title>
    <style>
        @page { margin: 24px 28px; }
        * { font-family: "DejaVu Sans", sans-serif; }
        body { color: #0E1A3A; font-size: 10px; margin: 0; }
        .head { border-bottom: 2px solid #2F54C9; padding-bottom: 6px; margin-bottom: 12px; }
        .company { font-size: 15px; font-weight: bold; color: #2F54C9; }
        .doc-title { font-size: 13px; font-weight: bold; text-transform: uppercase; margin-top: 2px; }
        .muted { color: #64748b; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { border: 0.5px solid #cbd5e1; padding: 4px 5px; }
        table.data th { background: #F4F6FB; color: #2F54C9; text-align: left; font-size: 9px; text-transform: uppercase; }
        .num { text-align: right; }
        tfoot td { font-weight: bold; background: #F4F6FB; }
        .note { margin-top: 10px; color: #64748b; font-size: 8px; }
    </style>
</head>
<body>
    <div class="head">
        <div class="company">{{ $org ?? 'AvanaHR' }}</div>
        <div class="doc-title">Bukti Potong PPh 21 Tahunan — Form 1721-A1 · {{ $year }}</div>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th>Karyawan</th>
                <th>NPWP</th>
                <th>PTKP</th>
                <th class="num">Bruto Setahun</th>
                <th class="num">PKP</th>
                <th class="num">PPh 21 Terutang</th>
                <th class="num">Dipotong</th>
                <th class="num">Kurang/Lebih</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['employee_name'] }}<br><span class="muted">{{ $row['employee_no'] }}</span></td>
                    <td>{{ $row['npwp'] ?? '—' }}</td>
                    <td>{{ $row['ptkp_status'] ?? '—' }}</td>
                    <td class="num">{{ $rp($row['gross']) }}</td>
                    <td class="num">{{ $rp($row['pkp']) }}</td>
                    <td class="num">{{ $rp($row['annual_tax']) }}</td>
                    <td class="num">{{ $rp($row['withheld']) }}</td>
                    <td class="num">{{ $rp($row['difference']) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;padding:16px;">Belum ada data payroll untuk tahun ini.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5">Total ({{ $summary['employees'] }} karyawan)</td>
                <td class="num">{{ $rp($summary['annual_tax']) }}</td>
                <td class="num">{{ $rp($summary['withheld']) }}</td>
                <td class="num">{{ $rp($summary['difference']) }}</td>
            </tr>
        </tfoot>
    </table>

    <p class="note">
        Dokumen ini dihasilkan otomatis dari data payroll. Angka tarif (TER, PTKP, Pasal 17) bersifat
        placeholder dan wajib divalidasi oleh tim pajak/akuntan sebelum dipakai sebagai bukti potong resmi.
    </p>
</body>
</html>
