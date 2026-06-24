# M09 — Analytics & Reporting

**BRD:** §10.9 (BR-ANL-001…005) · **Tier:** Essential (dashboard dasar) → Enterprise (report builder advanced) · **Fase:** 1 (dasar) / 4 (advanced) · **Depends:** semua modul transaksional.

## Tujuan
Insight workforce + laporan kepatuhan via dashboard, report builder, export, dengan data governance & row-level security.

## Scope
Workforce analytics (headcount, turnover, demografi, cost, absensi, performance, talent) · executive dashboard (BOD, drill-down) · **report builder** no-code (filter, grouping, formula sederhana, scheduling) · export Excel/PDF terkontrol · compliance report (payroll/BPJS/pajak/ketenagakerjaan/audit).

## Entitas & data dictionary
**report_definitions** — `tenant_id, name, source(entity/dataset), columns(json), filters(json), grouping(json), formulas(json), visibility(role/scope), created_by`.
**report_schedules** — `tenant_id, report_id, cron, recipients(json), format(excel|pdf), last_run_at` (QA-0144).
**dashboards** — `tenant_id, name, audience(role), layout(json)`.
**dashboard_widgets** — `dashboard_id, metric_key, viz_type, filter(json), drilldown(json)`.
**metric_definitions** — `tenant_id, key, name, formula, dimension(json)` (definisi konsisten lintas dashboard/report — BR-ANL).
**Materialized summaries** (di-refresh job): `headcount_daily_summary`, `attendance_daily_summary`, `payroll_cost_summary`, `turnover_summary`, dst — sumber dashboard cepat (lihat `03 §13`).
**export_jobs** (core) — audit setiap export sensitif.

**Index wajib:** summary tables di-index `(tenant_id, period/date, dimension)`. `report_definitions(tenant_id)`, `report_schedules(tenant_id, report_id)`.

## Pages / routes
- `analytics.workforce` — headcount/turnover/demografi/cost/absensi (filter company/division/month via **DateRangePicker**), kartu KPI + chart.
- `analytics.executive` — dashboard BOD lintas company (sesuai tenant + permission), drill-down.
- `reports.builder` — UI no-code: pilih dataset → field (hanya yang diizinkan role) → filter/group/formula → run → **export Excel/PDF**.
- `reports.index/show` — daftar report tersimpan + schedule.
- `reports.compliance.*` — pajak, BPJS, ketenagakerjaan (dari payroll **final**, bukan draft).

## Business rules (BRD §10.9 + §16)
- Laporan hanya tampil data sesuai **permission & scope organisasi** (row-level security) — manager hanya divisi/bawahannya (QA-0103/0106/0110).
- **Export data sensitif diaudit**; hanya role tertentu (RULE-007, QA-0107).
- Dashboard eksekutif punya **definisi KPI disepakati**; metrik konsisten lintas dashboard & report builder.
- Report builder **tidak akses data antar tenant** (tenant isolation).
- Compliance report (pajak/BPJS) ambil **payroll final** (QA-0108/0109).
- Turnover dihitung sesuai formula configurable & konsisten dgn employee lifecycle (QA-0104).
- Export format tanggal/angka & filter konsisten dengan tampilan (QA-0107).
- Report besar tidak timeout untuk volume target (queued + summary tables) (QA-0164).

## Integrasi
Konsumsi data semua modul (lewat summary tables/ETL ringan). Scheduled report kirim email/file (QA-0144). Fase 4: data warehouse/BI replication (BRD §13).

## Notifikasi
Scheduled report terkirim; alert anomali export (opsional).

## Acceptance (UAT)
QA-0103…QA-0110 (Analytics), QA-0164 (large report), QA-0107/0144 (export/schedule). E2E: FLOW-010 Data-to-Insight (QA-0158). RTM REQ-065…069, REQ-113.
