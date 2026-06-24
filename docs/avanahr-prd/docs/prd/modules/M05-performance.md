# M05 — Performance & Task Appraisal

**BRD:** §10.5 (BR-PFM-001…006) · **Tier:** Enterprise 360 · **Fase:** 3 · **Depends:** M01, engine approval, M03 (bonus/salary planning), M07 (talent). *Status: ringkas — expand ke FSD penuh saat dijadwalkan.*

## Tujuan & scope
Goal/OKR cascade · appraisal (probation/mid-year/annual/ad-hoc) · 360 & continuous feedback · KPI dashboard · PIP · task appraisal. Rating final → reward/development/succession.

## Entitas (data dictionary ringkas)
**goals** `tenant_id, owner_type(company|department|team|employee), owner_id, parent_goal_id(cascade), title, metric, target, weight, period_id, progress`.
**appraisal_cycles** `tenant_id, name, type(probation|mid_year|annual|adhoc), period_start, period_end, template_id, rating_scale(json), status(draft|open|calibration|closed)`.
**appraisal_reviews** `tenant_id, cycle_id, employee_id, reviewer_id, stage(self|manager|calibration), scores(json), draft_rating, final_rating, status`.
**feedback_360** `tenant_id, employee_id, cycle_id, giver_id, anonymous(bool), responses(json), visibility`.
**continuous_feedback** `tenant_id, employee_id, from_id, body, created_at` (timeline).
**kpis** `tenant_id, employee_id|team_id, kpi_key, target, actual, period_id`.
**pips** `tenant_id, employee_id, start_date, end_date, targets(json), reviewer_id, status(active|closed|extended|failed), milestones(json), outcome`.
**task_appraisals** `tenant_id, employee_id, title, due_date, weight, progress, evidence_attachment_id, score`.

**Index:** `goals(tenant_id, owner_type, owner_id, period_id)`, `appraisal_reviews(tenant_id, cycle_id, employee_id)`, `pips(tenant_id, employee_id, status)`, `task_appraisals(tenant_id, employee_id)`.

## Pages (no-modal CRUD)
`okr.*` (cascade tree), `appraisal-cycles.*`, `appraisal.my` (self review), `appraisal.team` (manager review + calibration), `feedback360.*`, `feedback.*` (continuous), `kpi.dashboard`, `pip.*`, `task-appraisal.*`.

## Business rules (BRD §10.5 + §16)
- OKR cascade company→department→individual; alignment terlihat (QA-0065).
- **Bobot goal harus 100%** (tolak bila tidak sesuai policy) (QA-0066).
- Cycle (probation/mid/annual/adhoc) punya periode/peserta/reviewer/template; task muncul ke karyawan/manager (QA-0067).
- Self appraisal → Manager Review → **calibration**; rating tidak final sebelum approval HR/BOD (QA-0068/0069). Perubahan rating setelah finalisasi butuh approval + audit.
- **360 feedback** sesuai visibility & **anonim** bila dikonfigurasi; feedback rahasia tidak dibuka di luar role (QA-0070).
- KPI dashboard per individu/tim sesuai akses (QA-0072).
- **PIP**: target/milestone/reviewer/durasi + reminder; keputusan close/extend/fail tersimpan di riwayat (QA-0073/0074).
- **Task appraisal**: task by bobot masuk perhitungan; evidence upload + review (QA-0075/0076).

## Workflow / integrasi / notif
Approval: rating final/calibration, perubahan rating. Kirim rating ke **M03** (bonus/salary planning, QA-0152) & **M07** (talent/9-box). Notif task/feedback/review.

## Acceptance (UAT)
QA-0065…QA-0076. E2E: FLOW-004 Performance-to-Compensation (QA-0152). RTM REQ-033…039.
