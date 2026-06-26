# AvanaHR — TODO / Status

_Terakhir diperbarui: 2026-06-25. Audit Playwright E2E + cross-check BRD v1.1 / List Modul.xlsx (lihat §Gap vs BRD)._

## Ringkasan
- Web app fokus role: **admin / HR / manager / finance / super-admin**.
- Fitur **karyawan (ESS/MSS)** → dibangun di **Flutter (JWT)**, **ditunda**. Employee `403` di web = by design, bukan bug.

## 🧹 Menu di-hide dari sidebar (2026-06-25) — code/route/test tetap ada
- **Grup Self-Service** (Dashboard Saya, Profil, Slip Gaji Saya, dll) — ESS = Flutter, web tak ada karyawan login. Semua dulu `href:'#'` (dead).
- **Grup Persetujuan** (Inbox Approval, Tim Saya, Kalender Tim) — MSS = Flutter; approval manager sudah lewat halaman transaksi (decide). Redundant + dead.
- **"Absen Hari Ini"** — duplikat "Rekap Absensi" (href identik); clock-in = Flutter.
- **"Talent & Suksesi"** — DIHAPUS total (2026-06-25). Dulu di-hide; sekarang controller/model/migration/test/route dibuang. Lihat §Modul PRD.
- **"Custom Field"** (Pengaturan) — CRUD definisi **+ render/simpan value di form Employee SELESAI (2026-06-25)**. End-to-end fungsional.
- Hasil: **0 link `href:'#'` tersisa** di sidebar.

---

## ✅ Sudah jadi (Phase 1 MVP) — terverifikasi E2E
- **M01 HR Core** — Employees CRUD (soft-delete), Companies, Departments & Positions, Cost Center, Job Level, Job Grade, Struktur Organisasi.
- **M03 Payroll** — Komponen Gaji, Periode, Proses Payroll, Slip Gaji, THR & Bonus, File Bank.
- **M10 Platform/Security/SaaS** — Platform Tenant CRUD (super-admin), Roles, Users, Permissions.
- **Leave & Overtime** — Jenis Cuti, Saldo Cuti, Pengajuan Cuti + Lembur, **approval/decide** (Pending → Disetujui, balance terpotong).
- **Reimbursement & Pinjaman** — CRUD + decide (akses finance/admin).
- **Auth** — login/logout semua role, validasi, RBAC scoping benar per role.
- **Master Waktu** — Shift, Kalender Kerja, Hari Libur.
- **M02 Attendance (web HR/admin)** — Rekap Absensi (filter rentang tanggal + status, jam in/out dari raw log), Koreksi Absensi (approve/reject → flag `attendance_daily.has_correction`), Timesheet CRUD. Clock-in karyawan = Flutter (face recognition).

---

## 🐛 Bug (semua sudah diperbaiki)
- [x] Super-admin buka `/roles` + `/users` → **500** (`tenantId(): int` dapat `null`). Fixed → `403` saat tanpa konteks tenant.
- [x] `SetCurrentTenant` middleware tidak membersihkan tenant stale (risiko bocor antar request di Octane/queue). Fixed → selalu re-bind (set `null` bila user tanpa tenant).
- [x] `SecurityTest` meng-assert prop passkey yang tidak disediakan controller (passkey tidak dipakai). Fixed → assertion passkey dibuang.
- [x] Model `AttendanceDaily` tidak set `$table` → query ke `attendance_dailies` (tabel asli `attendance_daily`). Fixed.

- [x] `PayrollRun::payslips()` pakai FK default `payroll_run_id` (kolom asli `run_id`). Fixed → `hasMany(Payslip::class, 'run_id')`.
- [x] Test flaky (factory code GR/LV/CC/CMP/DEP/POS overlap literal test). Fixed → range factory di-cap di bawah literal. + `tests/Pest.php` reset CurrentTenant/permission cache global.

Status test: **428/428 pass**.

## ✅ Employee Movement Management — Slice 1 (2026-06-25)
Menu "Mutasi & Movement" (sidebar grup Karyawan). Movement = transaksi aktif effective-dated (bukan ledger pasif kayak Lifecycle).
- Tabel `employee_movements` (type, effective_date, status draft→applied→cancelled, payload/before/after json, requires_clearance, approval_request_id, applied_at/by).
- Enum `EmployeeMovementType` (promotion·transfer·demotion·contract_change·suspension·reactivate·resign·terminate) + `EmployeeMovementStatus`.
- CRUD: index (filter tipe/status/search) + create + show (before→after diff) + destroy (draft only).
- **Apply** (`ApplyMovementAction`, DB transaction): bikin `employee_employments` row baru effective-dated + override payload, end-date row lama, update `employees.status` (resign→resigned+resign_date, terminate, suspension, reactivate, dll), tulis `employee_lifecycle_events`, snapshot before/after.
- `requires_clearance` auto-true utk exit (resign/terminate).
- Playwright E2E verified (create→show→apply, DB chain confirmed: employment baru + lifecycle event + status).

### ✅ Slice 2 (2026-06-25) — Clearance gate + propagasi akses
- Tabel `clearance_items` (category hr/finance/it/asset/legal, status pending/done/waived, completed_by/at). Model `ClearanceItem` + `::defaultChecklist()` (5 item).
- Store exit movement → auto-generate checklist 5 item. `EmployeeMovement::hasPendingClearance()` + `isApplicable()`.
- **Gate apply:** exit movement diblok selama ada clearance pending (`can_apply=false`, tombol Apply hilang). Defensive guard di `ApplyMovementAction` (throw kalau !isApplicable).
- UI show: panel Clearance (badge "N menunggu"/"Semua selesai", tombol Selesai/Waive per item → PATCH `movements.clearance.update`).
- **Propagasi akses:** apply resign/terminate/suspension → linked `User.status='inactive'` (cabut akses web); reactivate → 'active'. Null-user guarded.
- Bug fixed: `ApplyMovementAction` crash kalau employee no current employment (`employment_type` NOT NULL) → default 'permanent' + regression test.
- 15 movement+clearance test + 1 regression pass.

### ✅ Slice 3 (2026-06-25) — Scheduler effective-date auto-apply
- Status baru `scheduled` (Terjadwal) di enum. `canSchedule()` (draft only) / `canUnschedule()` (scheduled) / `canApply()` (draft|scheduled).
- Transisi: draft → **Jadwalkan** (`movements.schedule`) → scheduled; **Batalkan Jadwal** (`movements.unschedule`) → draft. Apply-now button draft-only; scheduled cuma bisa unschedule (auto-apply nunggu tanggal).
- Command `movements:apply-due` (daily 01:00 via routes/console.php): cari movement `scheduled` + `effective_date <= today` **lintas tenant** (`withoutGlobalScope(TenantScope)`), set `CurrentTenant` per movement, apply via `ApplyMovementAction` (actor null → applied_by null). Exit dgn clearance pending → di-skip (tetap scheduled).
- `ApplyMovementAction::execute(movement, ?User $actor = null)` — actor opsional (command tanpa user).
- 7 schedule test (incl. **cross-tenant isolation**: command set tenant context benar, row baru dapat tenant_id tepat) + Playwright E2E (Jadwalkan → status Terjadwal → command auto-apply, applied_by null). **364/364 total.**
- **Defer ke Slice 4 (butuh payroll engine dulu):** payroll-final clearance gate + payroll exclude filter (payroll run masih stub, belum ada lock/payslip-gen). Approval engine generik runtime (besar, enterprise).

## ✅ Kunjungan Kerja (WorkVisit) — 2026-06-25
"Duty Travel" di-**rebrand "Kunjungan Kerja"** (nama "dinas" baca PNS; target PT). Menu sidebar grup "Cuti & Lembur".
- Tabel `work_visits` (employee, destination, purpose, start/end_date, transport_mode, estimated_cost rupiah, status RequestStatus, decided_by/at, decision_note) + `work_visit_reports` (bukti kunjungan: visited_at, location, notes, attachment_path link).
- CRUD pengajuan (index filter+search, create, edit/update, destroy — pending only) + **decide** approve/reject (PATCH, decision_note) pola overtime/reimbursement.
- **Laporan Kunjungan** (bukti): tambah/hapus report di halaman show (POST/DELETE `work-visits.reports.*`).
- Estimasi biaya rupiah (formatRupiah). Klaim biaya aktual → lewat Reimbursement existing (jangan duplikat).
- Reuse permission employee.view/employee.update. 12 test + Playwright E2E (create Rp2.5jt → show → Setujui → tambah laporan). **383/383 total.**
- Catatan: bukti kunjungan = link/text (attachment_path), **binary file upload defer** (belum ada Storage infra dipakai di app).

## ✅ Plafon Benefit (Benefit Management) — 2026-06-25
"Benefit Management" di-scope jadi **Plafon Benefit** (hindari duplikat: tunjangan-masuk-gaji tetap di PayrollComponent, BPJS di EmployeeBpjsProfile, klaim umum di Reimbursement). Menu sidebar grup "Payroll".
- 3 tabel: `benefit_types` (master: code, name, default_quota rupiah, is_active) + `employee_benefits` (plafon per karyawan/tahun: quota rupiah, unique employee+type+year) + `benefit_claims` (pemakaian: amount, status RequestStatus, decided_by/at).
- **Jenis Benefit**: master CRUD inline dialog (pola leave-types). **Benefit Karyawan**: index (plafon/terpakai/sisa) + create (tetapkan) + show (ringkasan plafon + progress bar + klaim).
- **Klaim**: tambah klaim → decide approve/reject. **Sisa = quota − Σ klaim approved** (`remainingQuota()`). Approve **diblok kalau > sisa plafon** ('Klaim melebihi sisa plafon').
- BenefitType ga bisa dihapus kalau dipakai. Reuse permission payroll.view/payroll.approve (admin+finance).
- 13 test + Playwright E2E (buat jenis KES → tetapkan plafon Rp5jt ke Carla → klaim Rp1.5jt → Setujui → Sisa Rp3.5jt/30%, DB confirm). **396/396 total.**

## ✅ Payroll Engine (PPh21 TER + BPJS + prorate) — 2026-06-25
**Mesin hitung gaji** — sebelumnya PayrollRun cuma stub (bikin record, ga ngitung). Sekarang menghitung beneran.
- **`ProcessPayrollRunAction`** (DB transaction, idempotent): per karyawan payrollable (punya komponen gaji, employed di periode) → earnings/deductions dari `employee_salary_components`, BPJS, PPh21 TER, prorate join/resign tengah bulan (hari kalender) → bikin `Payslip` + `payslip_lines` + total run, status draft→calculated.
- **`BpjsCalculator`**: Kesehatan (cap basis) + JHT/JKK/JKM/JP, split karyawan vs perusahaan, dari `bpjs_parameters` (configurable, effective-dated) + `employee_bpjs_profiles`.
- **`Pph21TerCalculator`**: PPh21 metode TER bulanan (PMK 168/2023), rate lookup dari `config/payroll.php`.
- **⚠️ Angka TER/BPJS = config/seed ber-FLAG "PLACEHOLDER, wajib validasi regulasi"** (anti salah-angka-fatal). Kategori TER A terisi; B/C sengaja kosong (throw kalau dipakai) sampai diisi dari regulasi. Logika rumus benar; angka tarif data yang dikoreksi tanpa ubah kode.
- UI: tombol **Hitung Payroll** + halaman run (4 kartu total + tabel payslip) + halaman payslip (lines). **Input UI**: assign komponen gaji per karyawan (`employees/{id}/salary`).
- Seed demo: komponen GAPOK/TRANS/MAKAN, BpjsParameter 2024, 6 karyawan (gaji + PTKP kategori-A + profil BPJS).
- **19 test** (unit math: BPJS split, cap, TER lookup, netto, prorate, idempotent, no-tax-profile + HTTP: process route, RBAC payroll.run, salary CRUD) + Playwright E2E (buat periode→run→Hitung Payroll → 6 payslip, Bruto Rp82jt, PPh21 Rp5.106.474, BPJS Rp9.125.407, Netto Rp74.312.257; payslip pertama net Rp9.202.453 = match unit test). **415/415 total.**
- **3 asumsi butuh validasi akuntan:** metode TER (bukan gross-up); bruto-kena-pajak = gaji + BPJS-employer taxable (Kesehatan+JKK+JKM); prorate by hari kalender.
- **Belum (next):** slip PDF, UI config BPJS param + PTKP/BPJS profil per karyawan (sekarang seed-only), TER kategori B/C, komponen percentage/formula (sekarang fixed amount).

## ✅ Payroll Approval + Lock + Period Lock — 2026-06-25
Bikin payroll **production-safe & immutable** (BRD: "payroll period terkunci = IMMUTABLE").
- **Status flow run:** draft → calculated → **approved** (kunci) → **paid** (final). Tombol Setujui&Kunci / Batalkan Persetujuan / Tandai Dibayar di halaman run (gate `can_approve/revert/pay`). Permission `payroll.approve` (admin+finance).
- **Immutable:** run approved/paid → `process()` + `update()` + `destroy()` diblok (ga bisa hitung ulang/ubah/hapus).
- **Audit:** tiap transisi tulis `audit_logs` (event `payroll.approved`/`reverted`/`paid`, old/new status, ip/ua).
- **Clearance gate (Movement Slice 4 — UNBLOCKED):** approve diblok kalau ada karyawan di run dgn exit movement **committed (scheduled/applied)** + clearance pending. Draft resign ga blok (rencana, bukan exit). BRD "payroll final menunggu clearance".
- **Period lock:** `payroll-periods.close` (Draft→Locked) / `reopen` (Locked→Draft), permission payroll.approve. Periode Locked → **ga bisa bikin run baru** (guard di PayrollRunController.store). Tombol Kunci/Buka di halaman periode.
- **13 test** (approve/revert/pay transisi, clearance gate block+allow, RBAC payroll.approve, immutability, audit, period close/reopen/guard) + Playwright E2E (run calculated → Setujui&Kunci → approved+audit → Tandai Dibayar → paid; audit trail confirmed). **428/428 total.**

---

## ✅ Approval Engine Runtime — Slice 1 (2026-06-26)
Engine generik **runtime** akhirnya dipakai transaksi (sebelumnya cuma config `approval_flows` tanpa eksekusi). Gap BRD terbesar (BR-HRC-004 workflow bertingkat) mulai ketutup.
- **`App\Contracts\Approvable`** (`approvalType/approvalContext/approvalTitle/approvalRequesterUserId/approvalRequesterEmployee/onApproved/onRejected`) + trait **`HasApprovals`** (morphMany `approvalRequests`, pointer `approval_request_id`).
- **`App\Approvals\ApprovalEngine`**: `submit()` (pilih flow by `transaction_type`+conditions deterministik → bikin `approval_requests` + `approval_steps_state` per langkah; **no-flow → auto-approve** jalankan `onApproved`), `act()` (approve/reject/revise, advance sequential / finalize + callback modul, tulis `approval_actions` + `audit_logs`), `canAct()`. **SoD**: pemohon ga bisa setujui sendiri. Resolver approver: `role` (dinamis hasRole), `user` (id), `manager`/`department_head` (reporting line `employee_employments.manager_id`).
- **Wire pertama: Lembur** — `OvertimeRequest implements Approvable`; controller `store()→submit`. Tombol Setujui/Tolak inline di halaman lembur **dihapus** (route `overtime-requests.decide` + method dibuang); approval pindah ke **Inbox**.
- **Inbox Approval** (`/approvals`) — `ApprovalController` index (filter step pending milik approver login)/show (timeline langkah + riwayat aksi)/act. FormRequest validasi (alasan wajib utk reject/revise). Permission baru **`approval.act`** (hr-admin/manager/finance/payroll-officer). Sidebar item "Inbox Approval" (grup atas, gated `approval.act`).
- Seeder: flow demo Lembur (1 langkah role=manager) + grant `approval.act`. **`ApprovalAction` `$timestamps=false`** (tabel cuma `created_at`).
- **18 test** (engine: auto-approve no-flow, pending+stepstate, finalize callback, reject callback, SoD block, non-approver block, multi-step advance, conditionsMatch; inbox HTTP: list eligible, approve via route, hide dari non-approver, reject butuh alasan, RBAC 403) + Playwright E2E (admin buat Lembur Amalia→Pending; manager Estiawan Inbox→Tinjau→Setujui → request+OT approved, step approved, action+audit `approval.approve` confirmed; inbox kosong). **437/437 total.**
- **Belum (Slice 3+):** SLA + eskalasi job + notifikasi, `department_head` resolver beneran (sekarang fallback ke manager), conditions UI di config flow. (Parallel + delegasi → SELESAI Slice 2.)

---

## ✅ Approval Engine Runtime — Slice 4 (2026-06-26): wire 6 transaksi
Lanjutan Slice 1. **6 transaksi lagi** sekarang lewat `ApprovalEngine` + Inbox (3 agent paralel). Tiap model `implements Approvable` + `HasApprovals`, controller `store()→submit`, tombol decide inline + route `*.decide` + method dibuang, side-effect pindah ke `onApproved()`.
- **Cuti** (`leave`) — deduksi `leave_balances` (used+=days, recompute available) pindah ke `LeaveRequest::onApproved()`.
- **Reimbursement** (`reimbursement`) — status only. Title pakai rupiah.
- **Pinjaman** (`loan`) — status only (ga ada generate installment saat approve).
- **Kunjungan Kerja** (`work_visit`) — status only; decide UI di `work-visits/show.tsx` dibuang.
- **Klaim Benefit** (`benefit_claim`) — **veto plafon**: `BenefitClaim::onApproved()` throw `ApprovalException('Klaim melebihi sisa plafon')` kalau amount > sisa quota → engine `act()` di DB transaction → rollback, klaim tetap pending. Requester employee via nested `employeeBenefit->employee`.
- **Koreksi Absensi** (`attendance_correction`) — set `attendance_daily.has_correction=true` di `onApproved()` (raw attendance tetap immutable).
- Shared (aku, bukan agent): hapus 6 route `*.decide` di `web.php`; seed 6 flow demo (role=manager) di `DemoTenantSeeder`; `transaction_type` `work_visit`+`benefit_claim` ditambah ke config UI (`ApprovalFlowController`) + label Inbox; hapus 7 orphan `Decide*Request` FormRequest; fix `ApprovalFlowTest` (seeder kini banyak flow → query by name).
- **60 test agen** (19 cuti+reimburse, 22 loan+work-visit, 19 benefit+koreksi: pending+approval_request row, approve via engine + side-effect, reject, auto-approve no-flow, plafon veto) + Playwright E2E live (Reimbursement Rp750rb: admin submit→Pending→manager Inbox→Setujui→approved, DB confirm). **440/440 total**, build hijau.
- **Belum lewat engine (special, defer):** **Movement** (`apply` flow effective-dated, bukan decide) + **Payroll approval** (`payroll-runs.approve` punya lock/clearance gate sendiri). Wire nanti kalau perlu.

---

## ✅ Approval Engine Runtime — Slice 2 (2026-06-26): parallel steps + delegasi
- **Parallel steps + `min_approvals`**: step `mode='parallel'` nunggu N approver distinct setuju baru advance (`stepSatisfied`: count distinct approve action ≥ min). Reject pertama langsung tolak request. **Double-act guard** (`hasActed`): approver yg udah aksi di step itu ga bisa aksi lagi (penting buat pool paralel) + auto-drop dari Inbox.
- **Delegasi** (`approval_delegations`): step approver konkret (`user`/`manager`) → delegate aktif boleh aksi atas nama approver asli. `isActiveDelegate` cek from/to + window `starts_at`/`ends_at` + `transaction_types` (null=semua). **Approver asli tetap tercatat** di step state; delegate tercatat di `approval_actions` + note `[Delegasi dari user#X]`. Role step ga pakai delegasi (pool).
- **Delegasi CRUD** (`/approval-delegations`, sidebar "Delegasi Approval", gated `approval.act`): user kelola delegasi sendiri (from=login). Controller scoped (destroy cuma punya sendiri → 403 kalau orang lain). FormRequest (ga boleh delegasi ke diri sendiri, ends≥starts). Page: form (delegasikan ke + jenis transaksi + periode) + tabel (status Aktif/Nonaktif).
- **12 test baru** (parallel: hold sampai min_approvals, double-act block, reject-first kills; delegasi engine: delegate boleh aksi + asli tetap tercatat, delegasi expired ditolak; delegasi CRUD: render, create scoped, type→array vs all→null, anti-self, destroy own, 403 destroy orang lain, RBAC) + Playwright E2E live (Estiawan buat delegasi ke Nurul "Semua Transaksi" → row Aktif, DB from=2→to=3 confirm). **452/452 total**, build hijau.
- (SLA + eskalasi + notifikasi → SELESAI Slice 3.)

---

## ✅ Approval Engine Runtime — Slice 3 (2026-06-26): SLA + eskalasi + notifikasi
- **SLA tracking**: migration tambah `due_at`/`reminded_at`/`escalated_at` ke `approval_steps_state`. Engine `armSla()` set `due_at = now + step.sla_hours` saat step jadi current (di `submit` step-1, di `advanceOrComplete` step berikut); null kalau step tanpa `sla_hours`.
- **Command `approvals:check-sla`** (scheduled **hourly** di `console.php`, lintas tenant pola `movements:apply-due` + set team id): scan request pending yg step current-nya lewat `due_at` → **reminder** (set `reminded_at`, notif approver) → run berikut **eskalasi** kalau `escalate_to` ada (status step `escalated`, set `escalated_at`, notif target). Idempotent (kolom marker).
- **Eskalasi eligibility**: `canActOnStep` — step `escalated` → target `escalate_to` boleh aksi (selain approver asli). `escalate_to` = user id (numeric) atau nama role. Engine helper publik `approverUserIds()` + `escalateTargetUserIds()`.
- **Notifikasi** (`ApprovalNotifier` → tabel `notifications` channel `inapp`): event `approval.assigned` (approver step saat submit/advance), `approval.approved`/`approval.rejected`/`approval.revision` (ke requester saat finalize), `approval.sla_reminder` (approver saat overdue), `approval.escalated` (target saat eskalasi). **Email/push/WA defer** — baris inapp tinggal dibaca dispatcher channel nanti. Bell UI belum diwire.
- **6 test** (arm due_at saat submit, notif approver saat submit, notif requester saat final approve, reminder sekali saat overdue + ga dobel, eskalasi setelah reminder, target eskalasi bisa aksi setelah escalated) + live CLI E2E (req overdue → run1 reminder:1 + 4 notif sla_reminder, run2 eskalasi:1 + status `escalated` + notif `approval.escalated` ke hr-admin; idempotent). **458/458 total**, build hijau.
- **Dispatcher email → SELESAI** (lihat slice Branding+Email). Push/WA + Bell UI in-app = defer. `department_head` resolver + conditions UI masih sisa.

---

## ✅ Payroll Lengkap — 2026-06-26 (komponen formula + slip PDF + exclude exit)
3 gap payroll P0 ditutup:
- **Komponen gaji persentase** — `ProcessPayrollRunAction` 2-pass: fixed dulu (akumulasi base = total penghasilan tetap prorated), lalu `calc_type='percentage'` = `rate% × base` (rate dari `employee_salary_components.rate`). Mis. Tunjangan Jabatan 10% → 10% × (GAPOK+TRANS+MAKAN). UI: form komponen gaji per karyawan render field **Persentase (%)** vs **Nominal (Rp)** otomatis sesuai `calc_type` komponen.
- **Slip gaji PDF** — **dompdf** (`barryvdh/laravel-dompdf`, dep baru disetujui user). `PayslipController@print` → `Pdf::loadView('payslip-print')->stream()`. Blade table-layout (dompdf ga support flex/grid). Tombol **Cetak / PDF** di halaman slip (buka tab → PDF inline, bisa download). Format rupiah, split penghasilan/potongan, netto.
- **Exclude exit otomatis** — payroll run skip karyawan `status='suspended'` (inaktif tanpa resign_date). Resign/terminate udah ke-handle `resign_date` (exclude kalau sebelum periode, prorate kalau tengah periode).
- **3 test unit** (persentase 10% dari base 9.75jt = 975rb, exclude suspended, PDF stream content-type) + Playwright E2E live (slip PDF render: PT Avana, GAPOK 8jt→netto Rp9.202.453 match unit test Slice payroll; salary form switch ke field Persentase saat pilih komponen %). **461/461 total**, build hijau.
- **Belum (payroll sisa):** ~~UI config BPJS param + PTKP/BPJS profil~~ → SELESAI (slice BPJS/PTKP Config). TER kategori B/C (nunggu angka regulasi), `calc_type='formula'` ekspresi kompleks (sekarang percentage cukup).

---

## ✅ Branding Tenant + Email Notif — 2026-06-26
- **Branding tenant-specific** — bug: `app-sidebar-header.tsx` hardcoded `COMPANY_NAME='PT Nusantara Jaya'` (sama semua tenant). Fix: share `org` (nama + logo) dari **Company tenant sendiri** via `HandleInertiaRequests::resolveBranding` (scoped ke `tenant_id` user, `withoutGlobalScope` + where tenant_id — ga bocor antar tenant). Topbar + user chip pakai `org.name`, logo tampil di topbar. **Slip PDF** pakai company karyawan (`currentEmployment.company`) + logo.
- **Branding settings** (`/branding`, sidebar Pengaturan, `setting.manage`) — edit nama Company + **upload logo** (public storage, path `logos/{tenant_id}/` = isolasi per tenant). Migration `companies.logo_path`. `storage:link` udah ada.
- **Email notif (SMTP)** — `ApprovalNotifier` channel `inapp`→**`email`**. Command **`notifications:dispatch`** (scheduled everyMinute, lintas tenant, tiap row kirim ke `user.email` sendiri → isolasi) + Mailable `ApprovalNotificationMail` (markdown, subject per type) → `notifications.status='sent'`. Dev MAIL_MAILER=log, prod SMTP via env. **Push/WA defer**, Bell UI in-app defer (user: "notif paling email saja").
- **8 test** (branding: render, update nama, upload logo ke path tenant, tolak non-image, RBAC; email: bikin row pending email, dispatch kirim+mark sent, render mailable, skip tanpa email) + Playwright E2E live (branding: upload logo "AVANA PT" → tampil topbar + nama "PT Avana Indonesia" real bukan hardcoded, DB `logos/1/`; email: submit OT → 4 notif pending → dispatch → 4 sent + email ke laravel.log). **470/470 total**, build hijau. **dompdf** dep (slip PDF).
- **Isolasi tenant** dipegang: branding query scoped tenant user, logo path per-tenant, notif tiap row ke user/tenant sendiri.

---

## ✅ BPJS/PTKP Config UI — 2026-06-26 (payroll config: seed-only → admin-editable)
3 config payroll yang dulu seed-only sekarang bisa diedit admin via UI:
- **Parameter BPJS** (tenant, `/bpjs-parameters`, sidebar Payroll, `payroll.view`/`payroll.run`) — `BpjsParameterController` index/store/destroy. List effective-dated + tambah versi (form prefill dari `config('payroll.bpjs_defaults')`): kes rate kary/persh, plafon kes, tk_rates (JHT/JKK/JKM/JP kary+persh + plafon JP). Engine pilih param yang berlaku per periode run. Form assemble `tk_rates` json.
- **Profil Pajak (PTKP)** per karyawan — `EmployeeTaxBpjsController` (`/employees/{id}/tax-bpjs`, link di Employee show, `payroll.view`/`payroll.run`). CRUD effective-dated: ptkp_status (TK/0..K/3), npwp, tax_method (ter/gross/nett), beginning_ytd.
- **Profil BPJS** per karyawan (page sama, section kedua) — CRUD effective-dated: no kesehatan/tk, basis kesehatan/tk (rupiah), participation_flags (checkbox kesehatan/jht/jkk/jkm/jp → json).
- **11 test** (BPJS param: render, store+assemble tk_rates, validasi, destroy, RBAC; tax/bpjs: render, add tax, tolak PTKP invalid, add bpjs+flags, destroy, RBAC) + Playwright E2E live (BPJS param 2025 prefill defaults→simpan, DB tk_rates assembled; tax profil 2026 TK/0 ter). **484/484 total**, build hijau.

---

## ✅ Effective-dated History UI — 2026-06-26
Timeline riwayat penempatan + gaji per tanggal efektif (BRD golden rule #11 effective-dating). Data udah ada (employment versions + salary effective_date), tinggal UI.
- **`EmployeeHistoryController@index`** (`/employees/{id}/history`, `employee.view`) — load semua `employee_employments` versi (terbaru dulu) + `employee_salary_components`. Tiap versi di-**diff** vs versi lebih lama → field yang berubah (Jabatan/Departemen/Grade/Atasan/Cost Center/Cabang/Tipe/Status); versi pertama = "Awal".
- **Page `employees/history.tsx`** — timeline kartu per versi (effective→end, badge `Δ <field>` perubahan), **date picker "Per tanggal" (as-of)** → highlight badge "Berlaku" di versi yang aktif pada tanggal itu (effective ≤ asOf ≤ end). + tabel Riwayat Gaji (komponen, nilai fixed/persentase, tanggal berlaku). Tombol "Riwayat" di halaman Employee show.
- **3 test** (render props, diff flag field berubah = ['Jabatan'] + versi awal = ['Awal'], RBAC 403) + Playwright E2E live (promosi emp: v1 2026-06 "Berlaku" + Δ Jabatan/Grade, v2 2022 "Awal"; ganti as-of ke 2026-03 → "Berlaku" pindah ke v2). **473/473 total**, build hijau.

---

## ✅ HCMS Gap Final — 2026-06-26 (5 fitur, 5 agent paralel)
Tutup sisa gap HCMS web yang buildable. 5 agent paralel; shared route pre-added, migration tiap agent file sendiri.
- **Org chart drag-drop** — `OrganizationController@reparent` (`PATCH organization/departments/{department}/reparent`, `employee.update`): drag node departemen → reparent (`departments.parent_id`), guard cycle + cross-company + self. Page `organization/structure.tsx` HTML5 DnD (no dep baru). 5 test.
- **Conditions UI approval routing** — `ApprovalFlowController@updateConditions` (`PATCH approval-flows/{flow}/conditions`): editor "Kondisi Routing" di flow show (amount_min/max, grade_in, department_id, branch_id — match `conditionsMatch` engine). Kosong = semua. 5 test.
- **department_head resolver** — migration `departments.head_employee_id`, set "Kepala" di Departemen, model `head()`. Engine `resolveApprover` `department_head` → kepala dept requester → user, fallback manager. 3 test.
- **Binary file upload** — migration `employee_documents.file_path`. Upload dokumen → `documents/{tenant_id}/`, work-visit attachment → `visits/{tenant_id}/` (public disk, per-tenant isolasi). File input + link Unduh di page. 10 test.
- **Security policy enforcement** — `PasswordPolicy::rules()` (baca tenant security config → min/mixedCase/numbers/symbols) wired ke `PasswordValidationRules` trait (CreateNewUser/ResetUserPassword/PasswordUpdate). Middleware `EnforceSessionTimeout` (idle logout > `session_timeout_minutes`) + enforce_2fa redirect (default off = no-op), append di `bootstrap/app.php`. Exempt security-settings/settings page. 5 test.
- Integrasi: fix 1 (middleware 2FA redirect block `/security-settings` → exempt). **512/512 total**, build hijau. Playwright E2E spot: conditions card render, kolom Kepala, file input dokumen, auth sehat.
- **Isolasi tenant**: semua scoped (file path per-tenant, query tenant-scoped, password policy per-tenant config).

> **HCMS web AvanaHR = LENGKAP.** Sisa cuma: TER kategori B/C (nunggu angka regulasi), `calc_type=formula` ekspresi (percentage cukup), Bell UI in-app (notif via email), multi-bahasa (P2), login lockout runtime (Fortify default throttle ada). Recruitment/Performance/LMS = eksternal. ESS/MSS = Flutter.

---

## ⏳ Ditunda ke Flutter (ESS/MSS — JANGAN dibuat di web)
- Self-Service: Dashboard Saya, Profil, Slip Gaji Saya, Cuti Saya, Klaim, Status Pengajuan.
- MSS approval mobile: Inbox Approval, Tim Saya, Kalender Tim.
- Absensi karyawan: clock-in/out GPS / face recognition / kiosk.

---

## 📋 Belum dikerjakan (web)

### Modul PRD kosong total (belum ada controller/route)
- [🔗] **M04** Recruitment & Onboarding → **eksternal karivia.id** (integrasi saja)
- [🔗] **M05** Performance / Appraisal → **eksternal** (integrasi saja)
- [🔗] **M06** LMS → **eksternal learnpath.id** (integrasi saja)
- [🗑️] **M07** Talent & Succession — **DIHAPUS (2026-06-25)**: orphan, butuh data performance/appraisal eksternal (karivia). Controller/model/migration/test/halaman/route dihapus total. Bangun ulang nanti kalau integrasi karivia siap.
- [🗑️] **M11** Asset Management — **DIHAPUS (2026-06-25)**: "extension enterprise" di BRD, bukan HCMS core, berdiri sendiri tak nyambung HR Core flow. Controller/model/migration/test/halaman/route dihapus total. Fokus HCMS dulu.
- [🚫] **M12** CRM — **DROP** (scope creep, di luar HRIS)

### Parsial (master ada, fitur inti belum)
- [x] **M02 Attendance (web)** — Rekap, Koreksi+approve, Timesheet **selesai**. Import Biometrik **DIHAPUS (2026-06-25)** (controller/model/migration/test/halaman/route) — absensi prioritas face recognition via Flutter. Clock-in/out GPS/face/kiosk = Flutter (ESS).
- [x] **M09 Analytics & Reporting SELESAI SEMUA** — Analitik Workforce (breakdown), Dashboard Eksekutif (KPI lintas modul), Laporan Kepatuhan (PPh21+BPJS per run), Report Builder (no-code: pilih sumber+kolom whitelist → simpan definisi → run tabel dinamis).

### Stub sidebar (`href:'#'`)
- [x] **HR Core SELESAI SEMUA** — Tiket HR (helpdesk thread+balasan+SLA), Dokumen (registri+expiry badge), Lifecycle (riwayat peristiwa karier: hire/promosi/mutasi/resign + from→to snapshot).
- [x] **Pengaturan SELESAI/HIDDEN** — Workflow Approval + Custom Field + Keamanan selesai. **Integrasi & Langganan di-hide** (belum dibutuhkan). Keamanan = kebijakan tenant: panjang/komposisi/masa-berlaku sandi, timeout sesi, maks login + lockout, enforce 2FA — disimpan di `tenant_settings` key='security'. Catatan: ini config tersimpan; enforcement runtime belum diwire.)
- [x] Platform: **SELESAI SEMUA** — Tenant, Audit Log, Security Events, Provisioning, Langganan & Paket, Backup & Restore. (viewer cross-tenant super-admin; Audit = diff before/after, Security = meta+severity, Provisioning = "Terapkan Config", Langganan = ringkasan tier + fitur, Backup = buat backup + restore).

---

## 🧭 Gap vs BRD v1.1 (audit 2026-06-25 — BRDUPDATE.docx + List Modul.xlsx)

> BRD pakai nama kerja **"Humancore"**; produk resmi = **AvanaHR** (konsisten, sesuai PRD). Struktur app sesuai BRD: 14 process flow PF-01..14 = modul M01..M12. Web fokus admin; karyawan via Flutter (mobile-first BRD).

### Skor vs 14 Process Flow (PF)
- **Selesai:** PF-02 HR Core, PF-04 Cuti/Lembur/Timesheet, PF-05 Payroll, PF-11 Analytics, PF-12 Platform/Security. (+ Reimbursement/Kasbon)
- **Partial:** PF-03 Absensi (clock-in GPS/face → Flutter), PF-09 Talent (tanpa 9-box/career-path/dev-plan).
- **By design → Flutter:** PF-10 ESS/MSS Mobile.
- **Eksternal (app terpisah, integrasi saja):** PF-06 Recruitment → karivia.id, PF-07 Performance → eksternal, PF-08 LMS → learnpath.id.
- **Belum total (dibangun di AvanaHR):** PF-13 Asset, PF-14 CRM.
- **PF-01 Hire-to-Retire**: tahap recruitment/onboarding/performance via app eksternal; AvanaHR pegang HR Core→Payroll→Talent→Exit + integrasi.

### 🔗 Eksternal — JANGAN dibangun di AvanaHR web (integrasi saja nanti)
> Aplikasi web terpisah milik tim sendiri. AvanaHR cukup integrasi (API/SSO/sync data karyawan), bukan rebuild.
- **PF-06 / M04 Recruitment & Onboarding** → **karivia.id** (ATS: portal pelamar, pipeline, offer). AvanaHR: terima hasil hire → buat employee record.
- **PF-07 / M05 Performance & Task Appraisal** → eksternal (karivia.id / app terpisah).
- **PF-08 / M06 LMS / Training** → **learnpath.id** (training, kelas, sertifikat, competency). AvanaHR: sync History Training.

### Modul belum sama sekali — DIBANGUN di AvanaHR web
- [🗑️] **PF-13 / M11 Asset Management** — **DIHAPUS (2026-06-25)**: bukan HCMS core, fokus HR Core dulu.

### 🚫 DROP — keputusan 2026-06-25 (scope creep, jangan dibangun)
- **PF-14 / M12 CRM** — sales pipeline = di luar scope HRIS ("extension" di BRD). Drop kecuali ada kebutuhan jualan nyata.
- **App Builder / no-code (PF-12)** — effort besar, enterprise-only, jarang dipakai. Custom Field sudah cukup untuk configurability.
- **AI Features & Assistant (Enterprise tier)** — vague, tanpa use-case konkret. Skip.

### ⏸️ DEFER — bangun hanya kalau klien minta
- **SSO / MFA + API publik (PF-12)** — infra enterprise.
- **Talent extras (PF-09)**: 9-box matrix, career path, development plan. Succession core cukup buat MVP.
- **Sanksi Absensi · Tunjangan Kehadiran** — pakai Komponen Gaji fleksibel + formula payroll yang sudah ada, tak perlu fitur terpisah.
- **Slip pesangon/kompensasi terpisah (Payroll custom)** — edge case PHK.
- **COE (Calendar of Event)** — minor; lipat ke Kalender Kerja atau skip.
- **Enforcement runtime kebijakan keamanan** — config sudah tersimpan; wiring nanti.

### Gap kecil — masih WORTH dibangun
- [x] **Custom Field render+simpan di form Employee** — **SELESAI (2026-06-25)**. Form Employee (create/edit) render field dinamis per definisi tenant (text/textarea/number/date/select/checkbox) di section "Data Tambahan". Validasi dinamis (required + type + select in-options) via trait `ValidatesCustomFields`. Simpan via `SyncCustomFieldValuesAction` (morphMany `customFieldValues`, empty→clear). Edit prefill value. 7 test + Playwright E2E (create simpan "L" → edit prefill "L"). **371/371 total.**

> Gap kecil lain (Sanksi Absensi, Tunjangan Kehadiran, COE, slip pesangon, 9-box/career-path/dev-plan, SSO/MFA/API/App Builder, AI) → sudah dipindah ke **🚫 DROP / ⏸️ DEFER** di atas.

### Catatan integrasi lintas modul (BRD)
- Perubahan status employee harus mengalir ke modul terkait via **effective date** — ✅ via Employee Movement Management (Slice 1+2): apply resign/terminate → employment row baru + `employees.status` + cabut akses `User.status='inactive'`. Sisa: payroll exclude otomatis (tinggal filter employment status di payroll run) + scheduler future-dated.
- Payroll final tidak boleh jalan sebelum **clearance** — clearance/exit belum ada.
- Workflow Approval engine **runtime jalan (Slice 1+4, 2026-06-26)** — 7 transaksi lewat `ApprovalEngine` + Inbox: lembur, cuti, reimburse, pinjaman, kunjungan kerja, klaim benefit, koreksi absensi. **Belum di-wire:** Movement (apply effective-dated) + Payroll approval (lock/clearance gate sendiri) — defer.
