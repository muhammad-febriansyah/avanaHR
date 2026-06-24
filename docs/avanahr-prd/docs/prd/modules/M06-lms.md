# M06 — Learning Management System (LMS)

**BRD:** §10.6 (BR-LMS-001…005) · **Tier:** Enterprise 360 (Training Management) · **Fase:** 3 · **Depends:** M01, engine approval, M05/M07 (competency/talent). *Status: ringkas — expand ke FSD penuh saat dijadwalkan.*

## Tujuan & scope
Katalog training & e-learning · training request + approval · class management (jadwal/instruktur/peserta/biaya/kehadiran) · sertifikat + expiry reminder · competency tracking + gap analysis · budget control.

## Entitas (data dictionary ringkas)
**training_catalog** `tenant_id, code, title, category, competency_ids(json), audience, cost, delivery(classroom|elearning|blended), instructor_ref, status`.
**training_requests** `tenant_id, employee_id, catalog_id, cost, status, approval_request_id` (QA-0078).
**training_classes** `tenant_id, catalog_id, schedule_start, schedule_end, location/online_link, instructor_id, capacity, budget, status`.
**class_participants** `class_id, employee_id, attendance(json), assessment_score, completion_status`.
**elearning_progress** `tenant_id, employee_id, course_id, progress, quiz_score, completed_at` (QA-0080).
**certificates** `tenant_id, employee_id, name, issued_at, expired_at, reminder_days, file_attachment_id` (QA-0081).
**competencies** `tenant_id, code, name, category` · **employee_competencies** `tenant_id, employee_id, competency_id, actual_level` · **position_competencies** `tenant_id, position_id, competency_id, required_level`.
**gap_analyses** `tenant_id, employee_id, generated_at, result(json)` (QA-0082).
**training_budgets** `tenant_id, unit/department_id, period, amount, used` (QA-0083).

**Index:** `training_requests(tenant_id, status)`, `class_participants(class_id, employee_id)`, `certificates(tenant_id, expired_at)`, `employee_competencies(tenant_id, employee_id, competency_id)`.

## Pages (no-modal CRUD)
`training-catalog.*`, `training-requests.*`, `classes.*` (+ peserta/kehadiran), `elearning.*` (player + quiz), `certificates.*`, `competency.*` (matrix), `gap-analysis.*`, `training-budget.*`.

## Business rules (BRD §10.6 + §16)
- Katalog by kategori/kompetensi/jabatan/audience; karyawan ajukan sesuai eligibility/approval (QA-0077).
- Training request ikut **workflow approval**; status berubah sesuai tindakan (QA-0078).
- Class: jadwal/instruktur/peserta/notifikasi + kalender (bila terintegrasi) (QA-0079).
- E-learning: progress & nilai quiz tersimpan (QA-0080).
- **Sertifikat compliance** punya masa berlaku + **reminder** sebelum expired (QA-0081, RULE-009).
- **Competency gap analysis** dari required vs actual + rekomendasi training (QA-0082).
- Training **di atas budget** → warning/approval tambahan (QA-0083).
- Post-training evaluation masuk laporan efektivitas (QA-0084).
- Data kompetensi terhubung ke jabatan & talent profile.

## Workflow / integrasi / notif
Approval: training request, biaya di atas threshold. Kirim completion/sertifikat/kompetensi ke **M07** (talent) & **M05** (gap). Notif jadwal/sertifikat expired/overdue mandatory.

## Acceptance (UAT)
QA-0077…QA-0084. E2E: FLOW-005 Learning-to-Competency (QA-0153). RTM REQ-040…047.
