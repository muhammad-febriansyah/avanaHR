<?php

/*
|--------------------------------------------------------------------------
| Payroll statutory parameters (Indonesia)
|--------------------------------------------------------------------------
|
| ⚠️  PLACEHOLDER — WAJIB DIVALIDASI sebelum produksi.
|
| Angka di file ini (tabel TER PPh21 & default BPJS) HARUS dicek ulang
| terhadap regulasi yang berlaku (PMK 168/2023 untuk TER, Perpres/PP BPJS)
| oleh tim payroll/akuntan. Engine payroll hanya menerapkan rumus; angka
| tarif adalah data yang dapat dikoreksi di sini (TER) atau di tabel
| `bpjs_parameters` (BPJS, per-tenant, effective-dated) tanpa ubah kode.
|
| Versi referensi tabel TER: PMK 168/2023 (berlaku 2024).
|
*/

return [

    // Pemetaan status PTKP -> kategori TER bulanan (PMK 168/2023).
    'ptkp_ter_category' => [
        'TK/0' => 'A', 'TK/1' => 'A', 'K/0' => 'A',
        'TK/2' => 'B', 'TK/3' => 'B', 'K/1' => 'B', 'K/2' => 'B',
        'K/3' => 'C',
    ],

    /*
    | Tabel TER bulanan: tiap baris [batas_atas_bruto_bulanan, tarif_desimal].
    | Baris terakhir batas_atas = null (tak terhingga). Lookup: tarif pertama
    | yang bruto <= batas_atas. Kategori B & C WAJIB diisi dari regulasi
    | sebelum dipakai (dibiarkan kosong agar tidak diam-diam salah hitung).
    */
    'ter_monthly' => [
        'A' => [
            [5_400_000, 0.0000], [5_650_000, 0.0025], [5_950_000, 0.0050],
            [6_300_000, 0.0075], [6_750_000, 0.0100], [7_500_000, 0.0125],
            [8_550_000, 0.0150], [9_650_000, 0.0175], [10_050_000, 0.0200],
            [10_350_000, 0.0225], [10_700_000, 0.0250], [11_050_000, 0.0300],
            [11_600_000, 0.0350], [12_500_000, 0.0400], [13_750_000, 0.0500],
            [15_100_000, 0.0600], [16_950_000, 0.0700], [19_750_000, 0.0800],
            [24_150_000, 0.0900], [26_450_000, 0.1000], [28_000_000, 0.1100],
            [30_050_000, 0.1200], [32_400_000, 0.1300], [35_400_000, 0.1400],
            [39_100_000, 0.1500], [43_850_000, 0.1600], [47_800_000, 0.1700],
            [51_400_000, 0.1800], [56_300_000, 0.1900], [62_200_000, 0.2000],
            [68_600_000, 0.2100], [77_500_000, 0.2200], [89_000_000, 0.2300],
            [103_000_000, 0.2400], [125_000_000, 0.2500], [157_000_000, 0.2600],
            [206_000_000, 0.2700], [337_000_000, 0.2800], [454_000_000, 0.2900],
            [550_000_000, 0.3000], [695_000_000, 0.3100], [910_000_000, 0.3200],
            [1_400_000_000, 0.3300], [null, 0.3400],
        ],
        'B' => [], // TODO: isi dari PMK 168/2023 sebelum dipakai
        'C' => [], // TODO: isi dari PMK 168/2023 sebelum dipakai
    ],

    /*
    | Default BPJS untuk seeding `bpjs_parameters` (tenant dapat override).
    | Rate dalam persen. Komponen employer yang taxable (ditambahkan ke bruto
    | pajak) ditentukan via 'taxable_employer_components'.
    */
    'bpjs_defaults' => [
        'kes_rate_employee' => 1.0,   // 1% (potong karyawan), basis cap
        'kes_rate_employer' => 4.0,   // 4% (perusahaan)
        'kes_cap' => 12_000_000,      // plafon upah BPJS Kesehatan
        'tk_rates' => [
            'jht_employee' => 2.0,    // JHT karyawan
            'jht_employer' => 3.7,    // JHT perusahaan
            'jkk' => 0.24,            // JKK perusahaan (risiko, contoh tingkat I)
            'jkm' => 0.30,            // JKM perusahaan
            'jp_employee' => 1.0,     // JP karyawan
            'jp_employer' => 2.0,     // JP perusahaan
            'jp_cap' => 10_042_300,   // plafon upah JP (validasi tahunan)
        ],
    ],

    // Komponen BPJS yang dibayar perusahaan dan dihitung sebagai penghasilan
    // bruto kena pajak (asumsi: validasi dengan kebijakan/akuntan).
    'taxable_employer_components' => ['kesehatan_employer', 'jkk', 'jkm'],

    /*
    |--------------------------------------------------------------------------
    | Uang lembur (Kepmenaker 102/2004)
    |--------------------------------------------------------------------------
    |
    | Upah/jam = upah sebulan / 173. Hari kerja: jam ke-1 = 1.5x upah/jam,
    | jam berikutnya = 2x. Tarif hari libur/istirahat (2x/3x/4x) belum
    | diterapkan — butuh penanda jenis hari pada pengajuan lembur.
    |
    | `base_components`: sumber "upah sebulan" untuk dasar lembur.
    |   - 'fixed_earnings' (default): jumlah komponen earning tetap (non-persen),
    |     nominal penuh (tidak diprorata) = upah pokok + tunjangan tetap.
    */
    'overtime' => [
        'enabled' => true,
        'hours_divisor' => 173,

        // Tiered multipliers per day type: [up_to_hour, multiplier], cumulative;
        // last row up_to_hour = null (no ceiling).
        //  - workday  : jam ke-1 1.5x, berikutnya 2x.
        //  - holiday  : hari libur/istirahat (asumsi 5 hari kerja) jam 1–8 2x,
        //               jam ke-9 3x, jam ke-10–11 4x. (Kepmenaker 102/2004)
        'tiers' => [
            'workday' => [[1, 1.5], [null, 2.0]],
            'holiday' => [[8, 2.0], [9, 3.0], [null, 4.0]],
        ],

        'base_components' => 'fixed_earnings',
        'component_code' => 'LEMBUR',
        'component_name' => 'Uang Lembur',
        'taxable' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | PPh 21 tahunan (koreksi Desember + bukti potong 1721-A1)
    |--------------------------------------------------------------------------
    |
    | ⚠️  PLACEHOLDER — WAJIB DIVALIDASI akuntan/regulasi sebelum produksi.
    |
    | Metode TER dipakai Jan–Nov; Desember = koreksi tahunan: pajak setahun
    | (tarif progresif Pasal 17 UU HPP atas PKP) dikurangi PPh21 yang sudah
    | dipotong Jan–Nov. PKP = bruto setahun − biaya jabatan − PTKP, dibulatkan
    | ke ribuan ke bawah.
    |
    | Angka di bawah = referensi UU HPP / PMK (validasi sebelum dipakai).
    */
    'annual' => [
        'enabled' => true,

        // Biaya jabatan: 5% dari penghasilan bruto, maksimum 6.000.000/tahun.
        'occupational_cost_rate' => 0.05,
        'occupational_cost_cap' => 6_000_000,

        // PTKP setahun per status (UU HPP). TK/0 = 54jt, +4.5jt utk kawin,
        // +4.5jt per tanggungan (maks 3).
        'ptkp_annual' => [
            'TK/0' => 54_000_000, 'TK/1' => 58_500_000, 'TK/2' => 63_000_000, 'TK/3' => 67_500_000,
            'K/0' => 58_500_000, 'K/1' => 63_000_000, 'K/2' => 67_500_000, 'K/3' => 72_000_000,
        ],

        // Tarif progresif Pasal 17 UU HPP: [batas_atas_PKP, tarif].
        // Baris terakhir batas_atas = null (tak terhingga).
        'pasal17' => [
            [60_000_000, 0.05],
            [250_000_000, 0.15],
            [500_000_000, 0.25],
            [5_000_000_000, 0.30],
            [null, 0.35],
        ],
    ],
];
