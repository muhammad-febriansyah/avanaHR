# M01 — HR Core & Personalia

**BRD:** §10.1 (BR-HRC-001…007) · **Tier:** Essential+ · **Fase:** 1 · **Depends:** M10 (tenant/RBAC/audit). Fondasi semua modul lain.

## Tujuan
Single source of truth data karyawan, organisasi, dokumen, lifecycle, workflow, dan helpdesk. Semua modul memakai master dari sini (BR-X-001).

## Scope
Employee master data · org chart & struktur · lifecycle (hire→resign) · workflow approval (pakai engine M05/05-workflow) · helpdesk HR · dokumen & kontrak · multi-bahasa.

## Entitas & data dictionary
Master employee & organisasi sudah didefinisikan di `04-data-model-core.md` (employees, employee_employments⏱, dependents, educations, bank_accounts, tax_profiles⏱, bpjs_profiles⏱, documents, lifecycle_events; companies/branches/departments/positions/job_grades/job_levels/cost_centers/work_calendars/holidays). Tambahan modul:

**hr_tickets** — `tenant_id, ticket_no(unique per tenant), employee_id, category(enum: payroll|data|leave|general|it), subject, description, priority, status(open|in_progress|resolved|closed), assigned_to, sla_due_at, resolved_at`.
**hr_ticket_messages** — `ticket_id, user_id, body, attachment_id`.
**document_reminders** (view/job) — turunan dari `employee_documents.expired_at` + `reminder_days`.

**Index wajib:** `employees(tenant_id, status)`, `unique(tenant_id, employee_no)`, `unique(tenant_id, nik_ktp)`, `unique(tenant_id, npwp)`, `employee_employments(tenant_id, employee_id, effective_date)`, `employee_employments(tenant_id, manager_id)`, `hr_tickets(tenant_id, status)`, `employee_documents(tenant_id, expired_at)`.

## Pages / routes (Inertia, no-modal CRUD)
- `employees.index` — DataTable (kolom: foto, no, nama, departemen, posisi, status badge, join date). Filter: status, departemen, branch. Search nama/NIK/no. Bulk export.
- `employees.create` / `employees.edit` — form multi-section dalam **Card** (Personal, Employment, Payroll/Tax, BPJS, Bank, Dokumen). Field wajib `*` merah, placeholder, **date picker** (lahir, join), **RupiahInput** (gaji pokok). Section employment menulis `employee_employments` (effective_date).
- `employees.show` — profil lengkap + tab (Data, Riwayat lifecycle, Dokumen, Cuti, Payroll history) — field sensitif termasking sesuai permission.
- `org.chart` — visual org chart **drag-and-drop** reporting line (tanpa circular), publish berubah by effective date.
- `org.units.*` — CRUD company/branch/department/position/grade/level/cost-center (halaman terpisah).
- `calendars.*` + `holidays.*` — kalender kerja & libur.
- `lifecycle.*` — transaksi mutasi/promosi/demosi/suspensi/resign (form effective_date + reason → **approval engine**).
- `tickets.index/create/show` — helpdesk HR + SLA + assign + reply (Sonner notif).
- `documents.*` — upload kontrak/dokumen (expiry + reminder_days + access_level).

## Business rules (BRD §10.1)
- Employee no unik per tenant; NIK/KTP/NPWP/email **tidak boleh duplikat** → validasi inline (QA-0001/0002).
- Perubahan data sensitif (alamat, rekening, status, jabatan, grade, gaji) **maker-checker**: status Pending → aktif setelah approve (QA-0003).
- Mutasi/promosi/demosi/resign **effective-dated + approval**; struktur org & assignment ikut berubah sesuai effective date; riwayat tersimpan (QA-0004/0005/0006).
- Org chart drag-and-drop **tidak boleh circular reporting** (QA-0008/0009).
- Dokumen kontrak punya masa berlaku + **reminder** sebelum expired; akses terbatas role berizin (QA-0012/0013, RULE-009).
- Resign **tidak final** bila clearance/asset/payroll/dokumen wajib belum selesai (RULE-010 — orkestrasi exit clearance lintas modul).
- Multi-bahasa & **double-byte** characters tersimpan/tampil/export tanpa rusak (QA-0015).
- Effective-dated master: sistem pakai nilai sesuai effective date, bukan tanggal input (QA-0016); import historis tetap kronologis (QA-0167).
- Helpdesk: ticket no unik, SLA berjalan, status & notifikasi (QA-0014).

## Workflow hooks
Perubahan data sensitif, mutasi, promosi, resign → submit ke **ApprovalEngine** (`transaction_type` masing-masing). `onApproved` menerapkan perubahan + tulis `lifecycle_events` + audit.

## Notifikasi
Reminder kontrak expired (HR dashboard + email), status approval perubahan data, update ticket, reminder probation (lihat M04 onboarding).

## Reports / dashboard
Headcount, demografi, kontrak akan expired, movement (mutasi/promosi), turnover (detail di M09). Kelengkapan data karyawan, jumlah duplikat, SLA tiket, waktu proses mutasi/promosi (KPI BRD §10.1).

## Acceptance (UAT)
QA-0001…QA-0016 (HR Core), QA-0167 (data migration). E2E: FLOW-001 Hire-to-Retire (QA-0145/0146/0147). RTM REQ-001…007, REQ-116.
