# AvanaHR HRIS/HCM — PRD (Modular)

PRD ini dipecah modular supaya enak dipakai di **Claude Code** (konteks fokus per file). Mulai dari sini.

**Sumber:** BRD v1.1 (judul kerja "Humancore"), List Modul + tier quotation, Skenario UAT (168 test case). Nama produk resmi = **AvanaHR**.

## Cara baca (urutan disarankan)
1. `00-product-overview.md` — visi, scope, persona, tier paket, fase rilis, asumsi & keputusan.
2. `01-architecture.md` — stack, layering, multi-tenancy, struktur project, mobile.
3. `02-frontend-conventions.md` — UI/UX wajib (Card, `*` merah, DataTable, Sonner, Rupiah, Date Picker, performa).
4. `03-backend-conventions.md` — Service-Repo-Action, indexing, eager loading, validasi, audit, soft delete, transaksi.
5. `04-data-model-core.md` — ERD inti (tenant, identity, organisasi, employee), effective-dating, snapshot, audit.
6. `05-rbac-and-workflow.md` — role, matriks permission, **approval engine generik** lintas modul.
7. `06-api-and-integration.md` — konvensi API `/api/v1`, integrasi bank/BPJS/pajak/SSO/WA/biometrik, idempotency.

## Modul (`modules/`)
| ID | Modul | Fase | Status PRD |
|----|-------|------|-----------|
| M10 | Platform, Security & SaaS | 1 | Penuh |
| M01 | HR Core & Personalia | 1 | Penuh |
| M02 | Time & Attendance / Absensi | 1 | Penuh |
| M03 | Payroll & Compensation | 1 | Penuh |
| M08 | ESS/MSS & Mobile | 1 | Penuh |
| M09 | Analytics & Reporting | 1 | Penuh |
| M04 | Recruitment & Onboarding | 2 | Ringkas |
| M05 | Performance & Task Appraisal | 3 | Ringkas |
| M06 | Learning Management (LMS) | 3 | Ringkas |
| M07 | Talent & Succession | 3 | Ringkas |
| M11 | Asset Management | 4 | Ringkas |
| M12 | CRM Extension | 4 | Ringkas |

> "Ringkas" = scope, entitas, page, business rule, dan link UAT sudah ada — cukup untuk scaffold. Detail FSD per screen di-expand saat modul dijadwalkan.

## Testing (`testing/`)
- `uat-traceability.md` — pemetaan Modul → Requirement → QA ID → E2E Flow. Acceptance criteria final mengikuti file UAT asli (168 case).

## Prinsip emas
Semua aturan teknis & UX yang tidak boleh dilanggar diringkas di **`/CLAUDE.md`** (root). Tiap modul mengacu ke konvensi di file 02–06, tidak mengulang.
