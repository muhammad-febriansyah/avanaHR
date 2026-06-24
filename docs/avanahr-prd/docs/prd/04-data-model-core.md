# 04 — Core Data Model

> ERD inti & shared tables yang dipakai semua modul. Tabel spesifik modul ada di `modules/*`. Semua tabel tenant-scoped punya `tenant_id` (BIGINT, indexed) + `BelongsToTenant`. Kolom standar: `id, …, created_at, updated_at` (+`deleted_at` pada master).

## 1. Tenancy & Subscription
**tenants** — `id, name, slug(unique), domain, locale(default 'id'), timezone(default 'Asia/Jakarta'), currency('IDR'), status, logo_path`.
**tenant_settings** — `tenant_id, key, value(json), type` · `unique(tenant_id,key)`.
**tenant_subscriptions** — `tenant_id, tier(enum: essential|professional|enterprise), feature_flags(json), starts_at, ends_at, status`. Helper `tenant->can_use('feature_key')`.

## 2. Identity, Auth, RBAC
**users** — `tenant_id(nullable utk super-admin), employee_id(nullable), name, email(unique per tenant), password, mfa_secret, mfa_enabled, last_login_at, status`.
`spatie/laravel-permission` (teams mode, `team_id=tenant_id`): **roles, permissions, model_has_roles, role_has_permissions, model_has_permissions**.
**sessions** (db driver) · **password_reset_tokens** · **personal_access_tokens** (Sanctum, mobile).
**login_attempts** — untuk lockout brute-force (QA-0160).

## 3. Organization (effective-dated bila ditandai ⏱)
**companies** — `tenant_id, code, name, npwp, address, …`.
**branches** — `tenant_id, company_id, code, name, address, latitude, longitude(geofence), radius_m`.
**departments** — `tenant_id, company_id, parent_id(nullable, hierarki), code, name`.
**positions** — `tenant_id, department_id, code, name, job_level_id, job_grade_id`.
**job_levels** — `tenant_id, code, name, order`.
**job_grades** — `tenant_id, code, name, salary_band_min, salary_band_max(BIGINT)`.
**cost_centers** — `tenant_id, code, name`.
**work_calendars** — `tenant_id, name, is_default`.
**holidays** — `tenant_id, calendar_id, date, name, is_national`.

> Struktur org mendukung drag-and-drop reporting line **tanpa circular** (validasi di Action — QA-0008/0009). Reporting line manager disimpan di `employee_employments` (⏱), bukan kolom statis.

## 4. Employee (master + effective-dated employment)
**employees** (master) — `tenant_id, employee_no(unique per tenant), first_name, last_name, gender, birth_date, birth_place, religion, marital_status, nik_ktp(unique per tenant), npwp(unique per tenant, nullable), email, phone, photo_path, address, status(enum: probation|active|on_leave|suspended|resigned|terminated), join_date, resign_date`.
**employee_employments** ⏱ — `tenant_id, employee_id, effective_date, end_date(nullable), company_id, branch_id, department_id, position_id, job_grade_id, cost_center_id, manager_id(employee), employment_type(permanent|contract|intern|outsource), work_calendar_id, status`. → sumber kebenaran jabatan/atasan/grade "as of date".
**employee_dependents** — `employee_id, name, relationship, birth_date, is_bpjs_dependent`.
**employee_educations** — `employee_id, level, institution, major, year_start, year_end`.
**employee_emergency_contacts** — `employee_id, name, relationship, phone`.
**employee_bank_accounts** — `employee_id, bank_code, account_no, account_name, is_primary` (sensitif → masked).
**employee_tax_profiles** ⏱ — `employee_id, effective_date, ptkp_status(enum TK/0…K/3, dst), npwp, tax_method(ter|gross|nett), beginning_ytd`.
**employee_bpjs_profiles** ⏱ — `employee_id, effective_date, bpjs_kesehatan_no, bpjs_tk_no, kesehatan_basis, tk_basis, participation_flags(json)`.
**employee_documents** — `employee_id, document_type, file(attachment), number, issued_at, expired_at, reminder_days, access_level` (kontrak/expiry reminder — QA-0012/0013).
**employee_lifecycle_events** — `employee_id, type(hire|onboard|mutation|promotion|demotion|suspension|resign|terminate), effective_date, from_json, to_json, reason, approval_request_id` (riwayat hire-to-retire — QA-0003/0004/0005/0006).

## 5. Shared / Cross-cutting tables
**attachments** (polymorphic) — `tenant_id, attachable_type/id, disk, path, original_name, mime, size, uploaded_by`. (BR-X-003)
**audit_logs** — lihat `03 §11`.
**notifications** — `tenant_id, user_id, channel(email|push|whatsapp|inapp), type, payload(json), status, read_at, sent_at`. + log delivery & fallback (QA-0101, QA-0140).
**number_sequences** — `tenant_id, key, prefix, next_number, padding` (atomic).
**settings** — global app settings (non-tenant) + tenant via `tenant_settings`.
**approval_*** — engine generik (lihat `05-rbac-and-workflow.md`).
**import_batches** / **export_jobs** — `tenant_id, type, status, total, success, failed, error_report(file)` (bulk import per-row error — QA-0143; scheduled export — QA-0144).
**custom_fields** / **custom_field_values** (polymorphic) — fondasi no-code custom field (QA-0120); UI builder = Fase 4.

## 6. Aturan data master (BRD §12 + §16)
- Employee no unik per tenant; perubahan data kritikal **effective-dated + approval** (RULE-001/002).
- Dokumen kontrak hanya bisa diakses role berizin; expiry → reminder (RULE-009).
- Resign tidak final bila masih ada clearance/payroll/asset/dokumen belum selesai (RULE-010, QA-0006, QA-0147, QA-0155).
- Semua modul **wajib memakai master** karyawan/organisasi/grade/kalender dari HR Core (BR-X-001). Jangan duplikasi master.

## 7. Diagram relasi (ringkas)
```
tenants 1─* users ─* roles/permissions(team=tenant)
tenants 1─* companies 1─* branches/departments 1─* positions ─ job_grades/job_levels
employees 1─* employee_employments(⏱) ─> position/grade/manager/cost_center
employees 1─* {dependents, educations, bank_accounts, tax_profiles(⏱), bpjs_profiles(⏱), documents, lifecycle_events}
* ─ attachments(poly) · audit_logs(poly) · approval_requests(poly) · custom_field_values(poly)
```
