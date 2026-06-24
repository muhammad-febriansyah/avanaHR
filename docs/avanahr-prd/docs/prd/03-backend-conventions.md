# 03 — Backend Conventions

> Fokus: query cepat, konsistensi, audit-ready. Semua modul mengikuti file ini.

## 1. Layering & tanggung jawab
- **Controller** tipis: terima FormRequest, panggil Policy, panggil Action/Service, return Inertia/Resource. Tidak ada business logic & query langsung di controller.
- **Action**: 1 use-case, idempotent bila menulis banyak hal, dibungkus `DB::transaction()`.
- **Service**: orkestrasi proses panjang/berulang (PayrollEngine, ApprovalEngine, ReportBuilder).
- **Repository**: satu-satunya tempat query domain + eager loading. Method jelas (`paginateForIndex`, `findWithRelations`).
- **Model**: relasi, casts (enum/date/array), scopes, traits (`BelongsToTenant`, `SoftDeletes`, `Auditable`).

## 2. Indexing (WAJIB — query cepat)
Checklist tiap migration:
- [ ] PK `id` BIGINT.
- [ ] Setiap `*_id` (FK) **di-index** (`$table->foreignId(...)->constrained()->index()` atau index manual).
- [ ] `tenant_id` di-index; tambah **composite** untuk pola filter umum:
  - `(tenant_id, status)`, `(tenant_id, employee_id)`, `(tenant_id, employee_id, date)`, `(tenant_id, period_id)`, `(tenant_id, effective_date)`.
- [ ] **Unique per tenant** untuk business key: `unique(tenant_id, nik)`, `unique(tenant_id, serial_number)`, `unique(tenant_id, code)`.
- [ ] Kolom yang sering di-`orderBy`/`where`/`join` di-index.
- [ ] Hindari over-index pada tabel write-heavy (attendance raw) — index seperlunya.

## 3. Eager loading & anti N+1 (WAJIB)
- `Model::preventLazyLoading(! app()->isProduction())` di `AppServiceProvider::boot()` → lazy load = exception di non-prod.
- Repository selalu `with([...])`/`withCount([...])` relasi yang dipakai view. Untuk koleksi yang sudah ada: `$collection->load([...])`.
- Pilih kolom seperlunya pada relasi berat: `with('employee:id,name,position_id')`.
- Bulk: `chunkById()` / `lazy()` / `cursor()`; jangan `all()` di tabel besar.
- Hindari `count()` berulang di loop — pakai `withCount`.

## 4. Pagination
- Index list: `paginate($perPage)` (default 15) atau `cursorPaginate()` untuk dataset sangat besar / infinite.
- Kirim metadata pagination ke Inertia; jangan kirim seluruh dataset.
- Server menerima `page, per_page, sort, dir, filters[...]` dan memvalidasi whitelist kolom sort/filter.

## 5. Validasi
- Selalu **FormRequest** + `authorize()` (delegasi ke Policy).
- **Pesan Bahasa Indonesia** (override `messages()` / set `lang/id`). Contoh: `'name.required' => 'Nama wajib diisi.'`.
- Validasi referensial pakai `exists` ber-tenant (rule kustom `ExistsForTenant`).
- Uang divalidasi integer ≥ 0; tanggal `date`; effective_date tidak boleh memicu inkonsistensi (cek di Action).

## 6. Otorisasi
- **Policy per model** + `spatie/laravel-permission` (teams=tenant). Cek permission `module.action`.
- Field sensitif (rekening, NPWP, gaji) **dimasking** sesuai permission saat serialisasi (Resource/Inertia transform).
- Endpoint & halaman tolak akses lintas tenant & IDOR (selalu lewat scope + Policy).

## 7. Effective-dating (data master kritikal)
Untuk jabatan, grade, salary, status kerja, manager, cost center:
- Simpan di tabel histori `*_histories` / `employee_employments` dengan `effective_date` + `end_date` nullable.
- "As of" resolver: ambil row dengan `effective_date <= :asOf` terbaru. Default `:asOf = today`.
- Transaksi (payroll, report) memakai nilai **sesuai effective date**, bukan tanggal input. (RULE-002, QA-0016, QA-0146, QA-0167.)

## 8. Snapshot (data transaksional)
Payroll line, payslip, offer letter, claim settlement menyimpan **snapshot** nilai master saat transaksi (nama, jabatan, rate, komponen) → immutable, tahan perubahan master di kemudian hari. Perubahan master hanya pengaruhi draft/open period sesuai effective date. (QA-0163.)

## 9. Soft delete & immutability
- Master entity: `SoftDeletes`. Resign/terminate = ubah status, bukan delete.
- **Raw attendance immutable**: koreksi = record transaksi terpisah ber-approval (RULE-003, QA-0022/0023).
- **Payroll period locked immutable**: ubah hanya via unlock/adjustment berotorisasi (RULE-004, QA-0036).

## 10. Transaksi, Event, Queue
- Multi-step write dibungkus `DB::transaction()`.
- Side-effect via **Event → Listener (queued)**: notifikasi, recalculation, materialized summary refresh, audit enrich.
- Proses berat = **Queued Job** (PayrollRunJob, ExportReportJob, BulkImportJob, SendNotificationJob) + progress + **idempotency key** (cegah double run / double pay). (QA-0165.)
- Job membawa `tenant_id` dan re-bind tenant di `handle()`.

## 11. Audit log
- Trait `Auditable` → tabel `audit_logs`: `tenant_id, user_id, auditable_type/id, event, old_values, new_values, ip, user_agent, created_at`.
- Wajib untuk: perubahan data sensitif (rekening/gaji/status), approval, payroll run, export data sensitif, perubahan role/permission, login/akses gagal. (RULE-007, QA-0114.)

## 12. Penomoran & settings
- `number_sequences` per tenant (atomic increment dalam transaksi) untuk Employee ID, payroll run no, claim no, offer no, asset id, ticket no, dst.
- `settings` per tenant (key/value/typed) untuk parameter konfigurasi (toleransi absen, threshold approval, dst).

## 13. Caching
- Cache master data (organisasi, kalender, komponen gaji, permission map) dengan **tags tenant** → invalidasi saat berubah.
- Dashboard/aggregate: tabel `*_daily_summary` di-refresh job terjadwal; cache hasil query berat.

## 14. API (ringkas — detail di 06)
`/api/v1`, Sanctum, Resource classes, error envelope konsisten, rate limit, idempotency-key untuk mutasi, versioned, sandbox env.

## 15. Testing backend
- Feature test per Action penting (payroll calc, approval routing, tenant isolation, IDOR).
- Pest/PHPUnit. Factory + seeder untuk data dummy (lihat Test Data UAT: EMP0001, NIK dummy, gaji 10.000.000, dst).
- Acceptance mengacu UAT (`testing/uat-traceability.md`).
