# M07 — Talent & Succession

**BRD:** §10.7 (BR-TAL-001…005) · **Tier:** Enterprise 360 (Talent Management) · **Fase:** 3 · **Depends:** M05 (performance/potential), M06 (competency), M01. *Status: ringkas — expand ke FSD penuh saat dijadwalkan.*

## Tujuan & scope
Talent pool · talent mapping & 9-box · succession planning (readiness/risk) · career path (per job family) · development plan (IDP) · skill/competency matrix. Data sensitif → akses terbatas.

## Entitas (data dictionary ringkas)
**talent_pools** `tenant_id, name, criteria(json)` · **talent_pool_members** `pool_id, employee_id, category, reason, added_by, review_period` (QA-0085).
**nine_box_assessments** `tenant_id, employee_id, cycle_id, performance_rating, potential_rating, box(1..9), generated_at` (QA-0086).
**key_positions** `tenant_id, position_id, criticality, risk_level`.
**succession_candidates** `tenant_id, key_position_id, employee_id, readiness(ready_now|1_2y|2_3y), gap(json), development_action` (QA-0087/0088).
**career_paths** `tenant_id, job_family, from_position_id, to_position_id, required_competencies(json)` (QA-0089).
**development_plans** (IDP) `tenant_id, employee_id, competency_gaps(json), actions(json), target_role_id, progress` (QA-0090).
**skill_matrix** = `employee_competencies` + `position_competencies` (M06) + proficiency view (QA-0091).

**Index:** `talent_pool_members(pool_id, employee_id)`, `nine_box_assessments(tenant_id, employee_id, cycle_id)`, `succession_candidates(tenant_id, key_position_id)`, `development_plans(tenant_id, employee_id)`.

## Pages (no-modal CRUD)
`talent-pool.*`, `nine-box.*` (grid, filter departemen), `succession.*` (+ risk dashboard), `career-path.*` (per job family), `idp.*`, `skill-matrix.*`.

## Business rules (BRD §10.7 + §16)
- Talent pool by performa/potensi/kompetensi/jabatan/rekomendasi; ada history & alasan; **review berkala + approver** (QA-0085).
- **9-box** dari performance × potential; filter per unit (QA-0086).
- **Succession** posisi kunci dgn readiness + gap + development action; risk dashboard posisi tanpa successor (QA-0087/0088).
- **Career path** configurable per **job family** (QA-0089).
- **IDP** per karyawan, progres dipantau (QA-0090).
- **Skill matrix** terhubung jabatan & LMS; filter by skill/proficiency (QA-0091).
- Akses data talent **dibatasi** (HR/management/manager terkait); rekomendasi succession butuh evidence.

## Workflow / integrasi / notif
Konsumsi rating (M05) & competency/training (M06). Output ke development & succession dashboard. Notif review/readiness.

## Acceptance (UAT)
QA-0085…QA-0091. E2E: FLOW-006 Talent-to-Succession (QA-0154). RTM REQ-048…053.
