# CLAUDE.md — AvanaHR HRIS/HCM

> Aturan main wajib untuk SEMUA sesi Claude Code di repo ini. Baca file ini dulu sebelum nulis kode apa pun.
> PRD lengkap ada di `docs/prd/`. Mulai dari `docs/prd/README.md`.

**Produk:** AvanaHR — *Advancing People, Empowering Growth*. HRIS/HCM SaaS multi-tenant untuk SME → enterprise Indonesia.
(Catatan: BRD memakai judul kerja "Humancore". Nama resmi produk = **AvanaHR**.)

---

## 0. Golden Rules (jangan dilanggar)

1. **Multi-tenant by default.** Setiap tabel ber-data tenant WAJIB punya `tenant_id` (BIGINT, indexed) + ikut `TenantScope` global. Tidak ada query lintas tenant kecuali role `super-admin` platform. Lihat `docs/prd/05-rbac-and-workflow.md`.
2. **Query wajib cepat.** Index semua FK + kolom filter, eager loading wajib, pagination server-side selalu. `Model::preventLazyLoading()` aktif di non-production. N+1 = bug. Lihat `docs/prd/03-backend-conventions.md`.
3. **No modal untuk CRUD.** Create/Edit/Show = halaman Inertia terpisah. Modal hanya untuk konfirmasi (hapus) & quick action ringan.
4. **Form = selalu dibungkus `Card`**, tiap field punya `placeholder`, field wajib ada tanda `*` merah (`<RequiredMark/>`), error validasi inline (Bahasa Indonesia) di bawah field.
5. **Semua list pakai shadcn DataTable (TanStack)** — server-side pagination/sort/filter. Ref: https://ui.shadcn.com/docs/components/radix/data-table
6. **Notifikasi = Sonner** (`toast`) untuk semua feedback success/error/info, dibaca dari Inertia flash.
7. **Uang = Rupiah.** Simpan BIGINT (rupiah tanpa sen) di DB; format dengan `formatRupiah()`; input pakai `<RupiahInput/>`.
8. **Tanggal = Date Picker.** Tidak ada input tanggal manual. Simpan UTC, tampil WIB. Format `formatDateID()` (mis. `23 Jun 2026`).
9. **Icon lucide-react di SETIAP button** (leading icon).
10. **Poppins only. Light mode only.** Palet brand via CSS vars (lihat §4).
11. **Data kritikal effective-dated + audited.** Perubahan jabatan/grade/gaji/status pakai histori effective-date. Data transaksi simpan **snapshot** master saat transaksi. Audit log wajib untuk perubahan sensitif.
12. **Raw attendance & payroll period terkunci = IMMUTABLE.** Koreksi = transaksi terpisah ber-approval.

---

## 1. Tech Stack

**Web**
- Laravel 13 (PHP 8.3+), Inertia.js 2 + React 18 + **TypeScript**, Vite.
- Tailwind CSS v4 + **shadcn/ui** (Radix). TanStack Table, react-day-picker, Sonner, lucide-react.
- DB: PostgreSQL 16 (utama) / MySQL 8 didukung. Redis (cache, queue, session). Laravel Horizon.
- Auth: Laravel Sanctum (web session + mobile token). SSO SAML/OIDC opsional (enterprise).

**Mobile** (modul ESS/MSS/Kiosk)
- Flutter (stable) + **GetX** (state, routing, DI). Dio (HTTP), get_storage/hive (offline), camera + ML face, geolocator (GPS).
- Konsumsi REST API `/api/v1` (Sanctum token). Mode: Full / Lite / Kiosk.

**Infra**
- Queue (Redis) untuk payroll run, export, bulk import, notifikasi — semua **idempotent**.
- Storage S3-compatible (private), signed URL, prefix per-tenant.

---

## 2. Arsitektur Kode (Service–Repository–Action)

Alur request: **Controller (thin)** → **Action / Service** → **Repository** → **Model**.

```
app/
  Actions/<Domain>/      # 1 use-case = 1 Action (CreateEmployeeAction, RunPayrollAction)
  Services/<Domain>/     # orkestrasi lintas action / proses kompleks
  Repositories/<Domain>/ # akses data (query + eager load terpusat)
  Models/                # Eloquent, relasi, casts, scopes
  Data/                  # DTO (spatie/laravel-data)
  Http/
    Controllers/         # tipis: validasi(FormRequest) -> Action -> response Inertia/JSON
    Requests/            # FormRequest + pesan Bahasa Indonesia
    Resources/           # API Resource (untuk /api/v1)
    Middleware/
  Policies/              # otorisasi per model
  Enums/                 # status, tipe (PHP enum)
  Support/               # helper (Rupiah, Tanggal, NumberSequence)
resources/js/
  Pages/<Module>/        # halaman Inertia (Index/Create/Edit/Show)
  components/ui/          # shadcn components
  components/             # shared: DataTable, RupiahInput, DatePicker, RequiredMark, FormCard, PageHeader
  layouts/
  lib/                   # format.ts (formatRupiah, formatDateID), api, hooks (useFlashToast)
  types/                 # tipe TS (generated dari backend bila memungkinkan)
database/
  migrations/ seeders/ factories/
```

**Wajib:** FormRequest untuk validasi, Policy untuk otorisasi, DB transaction untuk multi-step write, Event/Listener + Queued Job untuk side-effect (notif, recalc, export).

---

## 3. Konvensi Database

- Primary key `id` BIGINT. FK `*_id` BIGINT **selalu di-index**.
- `tenant_id` di semua tabel tenant-scoped; composite index umum: `(tenant_id, status)`, `(tenant_id, employee_id, date)`.
- Business key unik **per tenant**: `unique(tenant_id, <key>)` (mis. NIK karyawan, serial asset, kode komponen).
- **Soft deletes** di master entity (`deleted_at`). Transaksi terkunci: tidak soft-delete, pakai status.
- Timestamp UTC (`timestamps`), tampil WIB di UI.
- Uang: kolom BIGINT (rupiah). Persentase/rate: DECIMAL. Tanggal efektif: kolom `effective_date` (date).
- Penomoran dokumen: tabel `number_sequences` per tenant (mis. `EMP00001`, `PAY-2026-06-0001`).
- Audit: tabel `audit_logs` (actor, tenant, auditable, old/new JSON, ip, ua, at).

Checklist index per tabel ada di tiap file modul (`docs/prd/modules/*`).

---

## 4. Design Tokens (brand AvanaHR)

```css
/* light mode only */
--primary:        #2F54C9;  /* royal blue (logo) */
--primary-foreground: #FFFFFF;
--navy:           #0E1A3A;  /* deep navy ("HR") — heading/teks kuat */
--accent:         #6E9BE6;  /* sky (swoosh) */
--background:     #FFFFFF;
--muted:          #F4F6FB;
--border:         #E5E9F2;
--destructive:    #DC2626;  /* merah: required mark & error */
--success:        #16A34A;
--warning:        #D97706;
--ring:           #2F54C9;
font-family: "Poppins", ui-sans-serif, system-ui;
radius: 0.625rem;
```
Required mark = `<span class="text-destructive">*</span>`. Semua angka uang rata kanan.

---

## 5. Komponen Reusable Wajib (resources/js/components)

- `FormCard` — wrapper Card untuk form (header judul+deskripsi, content, footer aksi).
- `RequiredMark` — bintang merah untuk label wajib.
- `DataTable` — wrapper TanStack + server pagination/sort/filter, column visibility, row selection, search debounce 300ms, empty/loading state. Tiap resource punya `columns.tsx`.
- `RupiahInput` / `formatRupiah` — input & display uang.
- `DatePicker` / `DateRangePicker` / `formatDateID` — semua field tanggal.
- `PageHeader` — judul + breadcrumb + tombol aksi (dengan ikon lucide).
- `useFlashToast` — baca `flash.success|error|info` dari Inertia → Sonner.
- `ConfirmDialog` — konfirmasi destruktif (satu-satunya modal yang umum).
- `StatusBadge`, `EmptyState`, `Money`, `EffectiveDateBadge`.

---

## 6. Workflow & RBAC (ringkas)

- RBAC pakai `spatie/laravel-permission` **mode teams** (team = tenant). Permission granular `module.action` + flag masking field sensitif.
- **Approval engine generik** dipakai lintas modul (cuti, lembur, klaim, perubahan data, payroll, training, asset, dst). Jangan bikin approval ad-hoc per modul — pakai engine di `docs/prd/05-rbac-and-workflow.md`. Dukung sequential/parallel, delegasi, SLA + eskalasi, maker-checker / segregation of duties.

---

## 7. Definition of Done (per fitur)

- [ ] Migration + index sesuai checklist modul; factory + seeder.
- [ ] Action/Service/Repository + FormRequest (pesan ID) + Policy.
- [ ] Halaman Inertia (Index DataTable / Create / Edit / Show) — no modal CRUD, form dalam Card, `*` merah, placeholder, date picker, Rupiah, ikon button.
- [ ] Eager loading benar (cek N+1), pagination server-side, query ter-index.
- [ ] Sonner toast pada sukses/gagal.
- [ ] Audit log untuk perubahan sensitif; effective-date / snapshot bila relevan.
- [ ] Approval lewat engine generik (kalau transaksi butuh approval).
- [ ] Tenant isolation teruji (tidak bocor lintas tenant).
- [ ] Acceptance criteria UAT modul terkait terpenuhi (lihat `docs/prd/testing/uat-traceability.md`).
- [ ] Mobile (ESS/MSS) bila modul punya permukaan mobile.

---

## 8. Urutan Build (lihat 00-product-overview untuk detail)

Fase 1 (MVP): Platform/Tenant + RBAC + Audit → HR Core + Org → Time & Attendance → Leave & Overtime → Payroll core → ESS/MSS + Mobile → Analytics dasar.
Fase 2: Recruitment+Onboarding, Reimbursement, Loan, Timesheet, Document/Contract, Helpdesk, Mobile full.
Fase 3: Performance & Task Appraisal, LMS, Talent & Succession.
Fase 4: Advanced analytics/report builder, App builder, API sandbox, Asset, CRM.
