# 05 — RBAC & Approval Workflow Engine

> Dua fondasi lintas modul: (A) RBAC granular per tenant, (B) **approval engine generik** yang dipakai SEMUA transaksi. Jangan bikin approval ad-hoc per modul.

## A. RBAC

### Roles (baseline, dari persona BRD §5)
| Role | Cakupan |
|------|---------|
| `super-admin` | Platform (lintas tenant): provisioning tenant, monitoring, billing. Bukan akses data HR tenant kecuali eksplisit + audited. |
| `tenant-admin` / `it-admin` | Konfigurasi tenant: role, workflow, form, integrasi, SSO/MFA. |
| `hr-admin` | Data & proses HR end-to-end. |
| `payroll-officer` | Payroll, pajak, BPJS, disbursement. |
| `finance` | Review payroll cost, reimbursement, loan, file bank. |
| `recruiter` / `hrbp` | Rekrutmen, manpower, onboarding. |
| `ld-admin` | LMS / training. |
| `talent-manager` | Talent & succession. |
| `manager` | MSS: approval & data tim (sesuai reporting line). |
| `employee` | ESS: data & transaksi pribadi. |
| `auditor` | Read-only + audit log, tanpa ubah data. |
| `bod` / `management` | Dashboard & KPI strategis (drill-down sesuai akses). |
| `asset-admin` | Modul Asset. |
| `sales` / `implementation` | Modul CRM + handoff tenant. |

> Role bersifat **per tenant** (teams mode). Tenant boleh bikin **custom role** + permission granular (QA-0113).

### Permission
- Format `module.action` (mis. `employee.create`, `payroll.run`, `leave.approve`, `report.export`). Bisa lebih granular `module.entity.action`.
- **Field-level masking**: flag permission `employee.view_sensitive` (rekening/NPWP/gaji). Tanpa itu → field dimasking di Resource/Inertia.
- **Row-level scope**: manager hanya lihat data bawahan (resolusi via reporting line); report row-level security (QA-0098, QA-0110).
- Cek di Policy + middleware `permission:...`. Perubahan role/permission **diaudit** (QA-0114).

### Matriks akses (contoh ringkas — lengkapi per modul)
| Aksi | employee | manager | hr-admin | payroll | finance | auditor | bod |
|------|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| Lihat data sendiri | ✅ | ✅ | ✅ | ✅ | ✅ | ✅(ro) | — |
| Lihat data tim | — | ✅ | ✅ | — | — | ✅(ro) | drill |
| Kelola master karyawan | — | — | ✅ | — | — | — | — |
| Run payroll | — | — | — | ✅ | — | — | — |
| Approve payroll | — | — | — | — | ✅* | — | — |
| Approve cuti/lembur/klaim | — | ✅ | ✅ | — | ✅(klaim) | — | — |
| Export data sensitif | — | — | ✅ | ✅ | ✅ | ✅ | ✅ |
| Konfigurasi tenant | — | — | — | — | — | — | — (it-admin) |

\* Segregation of duties: pembuat payroll ≠ approver (QA-0052).

## B. Approval Workflow Engine (generik)

Dipakai lintas modul: perubahan data karyawan, cuti, izin, lembur, koreksi absensi, klaim/reimbursement, loan, mutasi/promosi, salary increment, payroll approval, training request, asset request, CRM discount, dll. (BR-HRC-004, BR-X-002, FLOW-009, QA-0010/0011/0157.)

### Skema data
**approval_flows** — `tenant_id, transaction_type(enum/string), name, is_active, conditions(json)`.
Conditions = rule yang menentukan flow terpilih: grade, nominal/amount, department, location/branch, component, employment_type, dst.
**approval_flow_steps** — `flow_id, order, mode(sequential|parallel), approver_type(role|specific_user|manager_of_requester|manager_level_n|dynamic_field), approver_ref, sla_hours, escalate_to(nullable), allow_delegate(bool), min_approvals(untuk parallel)`.
**approval_requests** (polymorphic) — `tenant_id, approvable_type/id, flow_id, requested_by, status(pending|approved|rejected|revision|cancelled), current_step_order, payload_snapshot(json), submitted_at, completed_at`.
**approval_steps_state** — `request_id, step_order, approver_id, status(pending|approved|rejected|skipped|delegated|escalated), acted_at, reason`.
**approval_actions** — `request_id, step_order, actor_id, action(approve|reject|revise|delegate|escalate), to_user_id(delegate), reason, created_at` (riwayat lengkap untuk audit).
**approval_delegations** — `tenant_id, from_user_id, to_user_id, transaction_types(json|all), starts_at, ends_at` (QA-0011).

### Perilaku
- **Routing deterministik**: evaluasi `approval_flows.conditions` → pilih satu flow → generate step state. Harus bisa dijelaskan ke auditor (QA-0157, FLOW-009).
- **Sequential / parallel**; parallel butuh `min_approvals` tercapai.
- **Approver resolver**: role (semua/eskalasi), user spesifik, "manager of requester", "manager level N", atau dari field dinamis (mis. cost center owner).
- **SLA & eskalasi**: scheduled job cek step pending melewati `sla_hours` → reminder lalu eskalasi ke `escalate_to` (QA-0157).
- **Delegasi**: jika approver punya delegasi aktif → task dialihkan, **approver asli tetap tercatat** (QA-0011).
- **Maker-checker / SoD**: requester tidak boleh jadi approver atas transaksinya bila rule SoD aktif (QA-0052).
- **Aksi**: approve / reject (wajib alasan) / minta revisi (kembali ke requester) / delegate / escalate. Setelah final-approved → **eksekusi transaksi** di modul sumber via callback/event. Setelah rejected → tutup dengan alasan.
- **Notifikasi** tiap perubahan status (email/push/WA, fallback) — engine memicu `SendNotification`.
- **Audit**: semua aksi + old/new tersimpan; approval route dapat ditelusuri.

### Kontrak integrasi modul
Modul cukup: (1) implement `Approvable` (punya `approvalType()` + `onApproved()/onRejected()`), (2) panggil `ApprovalEngine::submit($model, $payload)`. Engine urus routing, state, notifikasi, eksekusi callback. **Modul tidak menyimpan logika approval sendiri.**

### Acceptance (UAT)
QA-0010 (multi-level by amount/grade/location), QA-0011 (delegation), QA-0052 (SoD payroll), QA-0157 (generic multi-level + escalation), FLOW-009/UAT-009.
