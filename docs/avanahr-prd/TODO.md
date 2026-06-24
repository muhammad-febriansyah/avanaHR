# AvanaHR — TODO / Status

_Terakhir diperbarui: 2026-06-25. Hasil audit Playwright E2E (admin, manager, employee, super-admin)._

## Ringkasan
- Web app fokus role: **admin / HR / manager / finance / super-admin**.
- Fitur **karyawan (ESS/MSS)** → dibangun di **Flutter (JWT)**, **ditunda**. Employee `403` di web = by design, bukan bug.

---

## ✅ Sudah jadi (Phase 1 MVP) — terverifikasi E2E
- **M01 HR Core** — Employees CRUD (soft-delete), Companies, Departments & Positions, Cost Center, Job Level, Job Grade, Struktur Organisasi.
- **M03 Payroll** — Komponen Gaji, Periode, Proses Payroll, Slip Gaji, THR & Bonus, File Bank.
- **M10 Platform/Security/SaaS** — Platform Tenant CRUD (super-admin), Roles, Users, Permissions.
- **Leave & Overtime** — Jenis Cuti, Saldo Cuti, Pengajuan Cuti + Lembur, **approval/decide** (Pending → Disetujui, balance terpotong).
- **Reimbursement & Pinjaman** — CRUD + decide (akses finance/admin).
- **Auth** — login/logout semua role, validasi, RBAC scoping benar per role.
- **Master Waktu** — Shift, Kalender Kerja, Hari Libur.

---

## 🐛 Bug (semua sudah diperbaiki)
- [x] Super-admin buka `/roles` + `/users` → **500** (`tenantId(): int` dapat `null`). Fixed → `403` saat tanpa konteks tenant.
- [x] `SetCurrentTenant` middleware tidak membersihkan tenant stale (risiko bocor antar request di Octane/queue). Fixed → selalu re-bind (set `null` bila user tanpa tenant).
- [x] `SecurityTest` meng-assert prop passkey yang tidak disediakan controller (passkey tidak dipakai). Fixed → assertion passkey dibuang.

Status test: **241/241 pass**.

---

## ⏳ Ditunda ke Flutter (ESS/MSS — JANGAN dibuat di web)
- Self-Service: Dashboard Saya, Profil, Slip Gaji Saya, Cuti Saya, Klaim, Status Pengajuan.
- MSS approval mobile: Inbox Approval, Tim Saya, Kalender Tim.
- Absensi karyawan: clock-in/out GPS / face recognition / kiosk.

---

## 📋 Belum dikerjakan (web)

### Modul PRD kosong total (belum ada controller/route)
- [ ] **M04** Recruitment & Onboarding
- [ ] **M05** Performance / Appraisal
- [ ] **M06** LMS
- [ ] **M07** Talent & Succession
- [ ] **M11** Asset Management
- [ ] **M12** CRM

### Parsial (master ada, fitur inti belum)
- [ ] **M02 Attendance** — belum: Absen Hari Ini, Rekap Absensi, Koreksi Absensi, Timesheet, Import Biometrik (belum ada `AttendanceController`).
- [ ] **M09 Analytics & Reporting** — belum: Analitik Workforce, Dashboard Eksekutif, Report Builder, Laporan Kepatuhan (belum ada Report controller).

### Stub sidebar (`href:'#'`)
- [ ] HR Core: Lifecycle, Dokumen, Tiket HR.
- [ ] Pengaturan: Workflow Approval, Custom Field, Keamanan, Integrasi, Langganan.
- [ ] Platform: Langganan & Paket, Provisioning, Audit Log, Security Events, Backup & Restore.
