# 00 — Product Overview

## Visi
**AvanaHR** adalah platform HRIS/HCM SaaS multi-tenant yang menjadi *single source of truth* data karyawan dan mengotomasi seluruh siklus HR (hire-to-retire): personalia, absensi, payroll, rekrutmen, performance, learning, talent, self-service, analytics — di atas fondasi SaaS yang aman, configurable, dan mobile-first. Tagline: *Advancing People, Empowering Growth*.

## Masalah yang diselesaikan
Data HR tersebar & manual, approval lambat, payroll rawan salah & telat, absensi rawan titip-absen, reporting manual, dan kebutuhan akses mobile untuk karyawan/manager. (BRD §3)

## Target pengguna (persona)
Karyawan (ESS), Manager (MSS), HR Admin/Ops, Payroll Officer, Finance, Recruiter/HRBP, L&D, Talent Manager, IT/System Admin, Auditor/Compliance, BOD/Management. Detail kebutuhan tiap persona di BRD §5; matriks akses di `05-rbac-and-workflow.md`.

## Tujuan bisnis & KPI (baseline, validasi stakeholder)
| Kode | Tujuan | Indikator | Target awal |
|------|--------|-----------|-------------|
| OBJ-01 | Single source of truth data karyawan | % data lengkap & tervalidasi | ≥95% saat go-live |
| OBJ-02 | Percepat payroll bulanan | Durasi cut-off → siap disburse | turun ≥50% |
| OBJ-03 | Kurangi dispute absensi/payroll | Tiket koreksi/periode | turun ≥30% / 3 bln |
| OBJ-04 | Adopsi self-service | % transaksi via ESS/MSS | ≥80% transaksi rutin |
| OBJ-05 | Visibilitas manajemen | Dashboard headcount/turnover/cost/perf | tersedia utk BOD |
| OBJ-06 | Siap skala SaaS | Tenant isolation, RBAC, audit, no-code | lulus SIT/UAT/security |

## Paket / Tier (dari quotation AvanaHR)
Feature-gating per tenant berdasarkan tier (`tenant_subscriptions.tier` + feature flags).

| Fitur | Essential (HC Starter) | Professional (HC Growth) | Enterprise 360 (HC Strategic) |
|-------|:---:|:---:|:---:|
| Organization Structure | ✅ | ✅ | ✅ |
| Database Employee | ✅ | ✅ | ✅ |
| Employee Management | ✅ | ✅ (+ Movement) | ✅ (+ Movement) |
| Time & Attendance | ✅ | ✅ | ✅ |
| Leave Management | ✅ | ✅ | ✅ |
| Payroll Management | ✅ | ✅ | ✅ |
| Employee Self Service | ✅ | ✅ | ✅ |
| Manager Self Service | ✅ | ✅ | ✅ |
| Mobile Apps | ✅ | ✅ | ✅ |
| Reporting & Dashboard | ✅ | ✅ | ✅ |
| Contract Management | — | ✅ | ✅ |
| Benefit Management | — | ✅ | ✅ |
| Duty Travel Management | — | ✅ | ✅ |
| Performance Management | — | — | ✅ |
| Recruitment Management | — | — | ✅ |
| Talent Management | — | — | ✅ |
| Training Management | — | — | ✅ |
| Calendar Management | — | — | ✅ |
| AI Features & Assistant | — | — | ✅ |

> Engineering: tiap modul/fitur cek `tenant->can_use('feature_key')`. Default Essential. Asset Management & CRM = extension enterprise opsional.

## Scope
**In scope:** seluruh 12 modul HRIS/HCM (BRD §6.1) + Asset & CRM sebagai extension.
**Out of scope awal:** custom ekstrem per tenant di luar konfigurasi/app builder, migrasi historis tanpa batas, interpretasi hukum statutory di luar parameter, integrasi real-time semua bank/portal/device tanpa kontrak teknis. (BRD §6.2)

## Proses bisnis To-Be (ringkas)
Employee Lifecycle · Attendance→Payroll · Leave/Overtime · Recruit→Onboard · Performance→Talent · Learning→Competency · Analytics→Compliance. (BRD §7, flow detail di tiap modul.)

## Prinsip solusi (non-negotiable)
Single source of truth · effective-dated transaction · maker-checker/approval · configurable-first · audit-ready · tenant isolation · mobile-enabled · secure by design. (BRD §8 — diterjemahkan ke aturan teknis di `/CLAUDE.md` & file 02–06.)

## Fase rilis & urutan build
**Fase 1 — Foundation/MVP**
Platform/Tenant + RBAC + Audit Log → HR Core + Organization → Time & Attendance → Leave + Overtime → Payroll core → ESS/MSS + Mobile → Analytics dasar.
*Go-live:* master valid, attendance→payroll sukses, role utama teruji, tenant isolation lulus.

**Fase 2 — Operational Expansion**
Recruitment, Onboarding, Reimbursement, Loan, Timesheet, Document/Contract, Helpdesk HR, Benefit, Duty Travel, Mobile full.

**Fase 3 — Performance & Development**
Performance & Task Appraisal, LMS, Competency, Talent & Succession, Calendar.

**Fase 4 — Enterprise Platform**
Advanced analytics + report builder, app builder/no-code, API sandbox, multi-tenancy advanced, Asset Management, CRM, AI assistant.

## Asumsi & Keputusan Arsitektur (opinionated — boleh divalidasi)
1. **Multi-tenancy = single database + row-level (`tenant_id`)** untuk MVP, dengan path ke DB-per-tenant untuk enterprise/on-prem. Alasan: time-to-market & ops sederhana, isolasi tetap dipaksa + diuji. (Detail `01-architecture.md`.)
2. **MVP = HR Core + Absensi + Payroll + ESS/MSS** (sesuai phasing BRD). Recruitment/Performance/Talent menyusul.
3. **Payroll Indonesia**: PPh21 skema **TER** (PMK terbaru) + rekalkulasi tahunan, BPJS Kes & TK, THR prorata, loan deduction, multi-bank file. Parameter pajak/BPJS **configurable + effective-dated** (bukan hardcode).
4. **Notifikasi**: Email + Push (FCM) + WhatsApp (Cloud API resmi + template), dengan **fallback chain**.
5. **Face recognition / GPS**: capture di device, verifikasi confidence ≥ threshold (default 0.85), anti-backdate untuk offline. Vendor face bisa on-device atau service — abstraksi `FaceMatcher`.
6. **App builder / no-code config**: fondasinya disiapkan dari awal (custom field, workflow config, report builder) tapi UI builder penuh = Fase 4.

## Open questions untuk stakeholder (tidak memblok build MVP)
Jumlah tenant & karyawan target kapasitas awal · parallel run & retroactive payroll di fase 1? · integrasi bank: file upload / host-to-host / API? · kebijakan retensi data (kandidat, resign, slip, pajak, audit) · WA pakai WABA resmi? · deployment awal cloud/private/on-prem · owner final parameter payroll/pajak/BPJS/cuti/lembur. (BRD §22)
