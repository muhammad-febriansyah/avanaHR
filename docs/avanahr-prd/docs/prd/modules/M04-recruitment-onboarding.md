# M04 — Recruitment & Onboarding

**BRD:** §10.4 (BR-REC-001…006) · **Tier:** Enterprise 360 · **Fase:** 2 · **Depends:** M01 (employee conversion), engine approval, M06 (career portal API). *Status: ringkas — expand ke FSD penuh saat dijadwalkan.*

## Tujuan & scope
Manpower planning · career page & job posting · ATS pipeline · evaluasi/scoring kandidat · interview scheduling · offer · onboarding checklist. Kandidat hired → employee record (tanpa input ulang).

## Entitas (data dictionary ringkas)
**manpower_plans** `tenant_id, department_id, position_id, year, planned_headcount, budget, status` · **manpower_requests** `tenant_id, position_id, type(new|replacement), budget, target_join_date, status, approval_request_id`.
**job_vacancies** `tenant_id, request_id, title, description, requirements, location, status(draft|published|closed)`.
**candidates** `tenant_id, name, email, phone, source, cv_attachment_id, consent(bool), dedup_key` · `unique(tenant_id, email)`.
**applications** `tenant_id, candidate_id, vacancy_id, stage(applied|screening|interview|offer|hired|rejected), app_no`.
**application_stage_history** `application_id, from_stage, to_stage, note, by, at`.
**interviews** `tenant_id, application_id, scheduled_at, interviewer_id, mode, status` · **interview_scores** `interview_id, scorer_id, criteria(json), score`.
**offers** `tenant_id, application_id, compensation(json), letter_path, status, approval_request_id`.
**onboarding_checklists** `tenant_id, employee_id, template_id, status` · **onboarding_tasks** `checklist_id, title, owner_role(hr|it|manager|employee), due_date, status, evidence_attachment_id`.

**Index:** `applications(tenant_id, vacancy_id, stage)`, `candidates(tenant_id, email)` unique, `interviews(tenant_id, scheduled_at)`, `onboarding_tasks(checklist_id, status)`.

## Pages (no-modal CRUD)
`manpower.*`, `vacancies.*`, career portal publik (`/careers`), `candidates.*` (ATS board per stage — bisa kanban, tapi detail = halaman), `interviews.*`, `offers.*`, `onboarding.*`.

## Business rules (BRD §10.4 + §16)
- Manpower request ber-approval budget+headcount; vacancy melebihi approved manpower → warning/tolak (QA-0053/0054).
- Publish lowongan ke career portal & terima lamaran (QA-0055/0056).
- Pindah stage tercatat di history (QA-0057); scoring agregasi by bobot + ranking (QA-0058/0059).
- Offer dari template + data benar; approval offer sesuai policy (QA-0060).
- **Convert kandidat → employee** tanpa input ulang + trigger onboarding checklist (QA-0061, BR-REC-005).
- Onboarding progress real-time; complete hanya bila semua task wajib selesai; probation reminder (QA-0062/0063).
- **Duplicate candidate** dicegah (email/telepon/identitas); **consent** data privacy wajib (QA-0064, UU PDP).
- Kandidat tidak jadi karyawan aktif sebelum data wajib + approval offer (RULE).

## Workflow / integrasi / notif
Approval: manpower, offer. Email/Calendar: undangan interview, offer, onboarding (INT-007). Notif kandidat & interviewer.

## Acceptance (UAT)
QA-0053…QA-0064. E2E: FLOW-001 Hire-to-Retire (QA-0145). RTM REQ-026…032.
