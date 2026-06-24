# UAT Traceability

> Pemetaan **Modul → Requirement → QA Scenario → E2E Flow** sebagai sumber acceptance criteria. **Acceptance final mengikuti file UAT asli** (`Skenario_UAT.xlsx`, 168 test case). File ini ringkasan navigasi untuk dev.

## Ringkasan cakupan
- Total test case: **168** (P0: 84, E2E: 14, Security: 10) · semua kandidat automation.
- RTM requirement: **117** (REQ-001…117) — status Covered.
- E2E flows: **10** (FLOW-001…010) · Integration: **8** (INT-001…008) · UAT checklist: **24** (UAT-001…024) · Test data: 20 set.

## Modul → QA Scenario → RTM → E2E
| Modul (PRD) | QA IDs | RTM REQ | E2E Flow terkait |
|-------------|--------|---------|------------------|
| M01 HR Core | QA-0001…0016, QA-0167 | REQ-001…007, 116 | FLOW-001 (QA-0145/0146/0147) |
| M02 Time & Attendance | QA-0017…0034, QA-0166 | REQ-008…014, 115 | FLOW-002 (QA-0148), Overtime QA-0149, Leave QA-0150 |
| M03 Payroll | QA-0035…0052, QA-0163 | REQ-015…025, 112 | FLOW-002/003/004 (QA-0148/0151/0152) |
| M04 Recruitment | QA-0053…0064 | REQ-026…032 | FLOW-001 (QA-0145) |
| M05 Performance | QA-0065…0076 | REQ-033…039 | FLOW-004 (QA-0152) |
| M06 LMS | QA-0077…0084 | REQ-040…047 | FLOW-005 (QA-0153) |
| M07 Talent | QA-0085…0091 | REQ-048…053 | FLOW-006 (QA-0154) |
| M08 ESS/MSS & Mobile | QA-0092…0102, QA-0117, QA-0168 | REQ-054…064, 117 | FLOW-009 (QA-0157) |
| M09 Analytics | QA-0103…0110, QA-0164 | REQ-065…069, 113 | FLOW-010 (QA-0158) |
| M10 Platform/Security | QA-0111…0122, QA-0159/0160/0161 | REQ-070…078, 110 | (security across) |
| M11 Asset | QA-0123…0129 | REQ-079…083 | FLOW-007 (QA-0155) |
| M12 CRM | QA-0130…0134 | REQ-084…088 | FLOW-008 (QA-0156) |
| Integration & API | QA-0135…0144, QA-0165 | REQ-089…097, 114 | INT-001…008 |
| End-to-End | QA-0145…0158 | REQ-098…109 | FLOW-001…010 |

## E2E Flows (FLOW-001…010)
| Flow | Nama | QA | Prioritas | UAT |
|------|------|----|-----------|-----|
| FLOW-001 | Hire-to-Retire | QA-0145/0146/0147 | P0 | UAT-001 |
| FLOW-002 | Time-to-Payroll | QA-0148 | P0 | UAT-002 |
| FLOW-003 | Claim-to-Payroll | QA-0151 | P0 | UAT-003 |
| FLOW-004 | Performance-to-Compensation | QA-0152 | P1 | UAT-004 |
| FLOW-005 | Learning-to-Competency | QA-0153 | P1 | UAT-005 |
| FLOW-006 | Talent-to-Succession | QA-0154 | P1 | UAT-006 |
| FLOW-007 | Asset-to-Exit | QA-0155 | P1 | UAT-007 |
| FLOW-008 | Lead-to-Tenant | QA-0156 | P1 | UAT-008 |
| FLOW-009 | Approval Workflow (generik) | QA-0157 | P0 | UAT-009 |
| FLOW-010 | Data-to-Insight | QA-0158 | P1 | UAT-010 |

## Integration Matrix (INT-001…008)
INT-001 Bank (P0, QA-0050/0051/0139) · INT-002 BPJS (P0, QA-0109) · INT-003 Pajak (P0, QA-0108/0038/0039) · INT-004 Biometrik (P0, QA-0033/0034/0138) · INT-005 Mobile (P0, QA-0020/0166) · INT-006 SSO/IdP (P0, QA-0115/0142) · INT-007 Notif (P1, QA-0101/0140/0141) · INT-008 CRM→Tenant (P1, QA-0134/0156).

## Security cases (10) — fokus QA Platform
QA-0111 (tenant isolation), QA-0113 (RBAC granular), QA-0114 (audit trail), QA-0116 (MFA), QA-0118 (TLS/masking), QA-0136 (API token), QA-0159 (session timeout), QA-0160 (brute-force lockout), QA-0161 (IDOR), QA-0045 (payslip unauthorized).

## Test Data acuan (Skenario_UAT → sheet Test Data)
EMP0001 (tenant A) · NIK 3173010101900001 · Tax K/0 · GPS -6.20,106.8167 · Face confidence 0.92 (threshold 0.85) · Annual leave 12 · Basic salary 10.000.000 · BPJS basis 12.000.000 · Medical claim 750.000 · Loan 5.000.000 (tenor 10) · Goal weight 25% (total 100%) · Asset serial SN-LAP-0001 · Opportunity 250.000.000 · tenant-alpha / tenant-beta (isolation) · Idempotency idem-20260623-001 · Period 2026-06.

## Definition of Done per fitur (recap)
Fitur dianggap selesai bila QA scenario modul terkait **pass** + memenuhi DoD di `/CLAUDE.md §7`. Modul P0 (Fase 1) wajib lulus sebelum go-live: master valid, attendance→payroll sukses, RBAC & tenant isolation lulus (AC-002/003/005/009).
