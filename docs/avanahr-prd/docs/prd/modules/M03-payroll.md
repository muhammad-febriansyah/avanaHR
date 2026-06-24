# M03 — Payroll & Compensation

**BRD:** §10.3 (BR-PAY-001…009) · **Tier:** Essential+ · **Fase:** 1 · **Depends:** M01 (employee/tax/bpjs/bank master), M02 (attendance final), engine approval. **Modul paling kritikal — akurasi & audit utama.**

## Tujuan
Payroll Indonesia yang akurat, aman, tepat waktu, auditable: gaji pokok, tunjangan, potongan, PPh21 TER, BPJS, THR/bonus, loan, reimbursement, multi-bank disbursement.

## Scope
Payroll engine configurable · PPh21 TER + rekalkulasi tahunan · BPJS Kes & TK · band/grade/salary planning · slip gaji digital (proteksi akses) · reimbursement · employee loan · THR/bonus/variable · bank file multi-bank.

## Entitas & data dictionary (uang = BIGINT rupiah; parameter statutory effective-dated)
**payroll_components** — `tenant_id, code, name, type(earning|deduction|statutory|info), calc_type(fixed|formula|attendance|percentage), formula(json/expr), taxable(bool), is_bpjs_base(bool), default_amount`.
**employee_salary_components** ⏱ — `tenant_id, employee_id, component_id, effective_date, amount/rate` (komponen gaji per karyawan).
**salary_structures** — `tenant_id, job_grade_id, band_min, band_max, currency`.
**tax_parameters** ⏱ — `tenant_id, effective_date, scheme(TER), ter_table(json: kategori A/B/C + bracket), ptkp_table(json), rates(json)` (configurable — JANGAN hardcode).
**bpjs_parameters** ⏱ — `tenant_id, effective_date, kes_rate_employee, kes_rate_employer, kes_cap, tk_jht/jp/jkk/jkm rates & caps(json)`.
**payroll_periods** — `tenant_id, code, month, year, cutoff_date, pay_date, status(draft|calculated|reviewed|approved|locked|disbursed), locked_by, locked_at`.
**payroll_runs** — `tenant_id, period_id, run_no, type(regular|thr|bonus|retro|adjustment), status, total_gross, total_net, total_tax, total_bpjs, created_by, approved_by` (idempotent).
**payslips** — `tenant_id, run_id, employee_id, snapshot(json: nama/jabatan/komponen), gross, deductions, tax, bpjs_emp, bpjs_co, net, file_path, access_protected(bool)` (immutable snapshot).
**payslip_lines** — `payslip_id, component_code, component_name, type, amount` (snapshot).
**reimbursements** — `tenant_id, employee_id, category, amount, attachment_id, status, settlement(payroll|non_payroll), period_id, approval_request_id` (QA-0046).
**employee_loans** — `tenant_id, employee_id, principal, tenor_months, installment, outstanding, start_period, status, approval_request_id`.
**loan_installments** — `loan_id, period_id, amount, status(scheduled|deducted)` (QA-0047).
**thr_bonus_runs** — `tenant_id, type(thr|bonus), period_ref, formula(json), status` (prorata/performance multiplier — QA-0048/0049).
**bank_files** — `tenant_id, run_id, bank_code, format, file_path, total, exception_report` (QA-0050/0051).
**payroll_adjustments** — `tenant_id, employee_id, period_id, component_code, amount, reason, approval_request_id` (retro/koreksi).

**Index wajib:** `payroll_periods(tenant_id, year, month)` unique, `payroll_runs(tenant_id, period_id)`, `payslips(tenant_id, run_id, employee_id)`, `payslips(tenant_id, employee_id)`, `employee_salary_components(tenant_id, employee_id, effective_date)`, `loan_installments(loan_id, period_id)`, `tax_parameters(tenant_id, effective_date)`.

## Pages / routes (no-modal CRUD)
- `payroll.periods.*` — buka periode + cutoff/pay date (**date picker**), status lifecycle.
- `payroll.run` — wizard: pilih periode → tarik data (attendance/komponen/loan/claim/tax/bpjs) → **pre-payroll validation** (exception list) → calculate (queued job + progress) → preview → review variance → **approve (SoD)** → **lock** → generate slip & bank file.
- `payroll.runs.show` — hasil run, variance vs periode lalu, daftar payslip.
- `payslips.index/show` — DataTable + **Money** rata kanan; download (proteksi akses).
- `components.*`, `salary-structures.*`, `salary-components.*` — CRUD (RupiahInput).
- `tax-parameters.*`, `bpjs-parameters.*` — admin parameter (effective_date), audit.
- `reimbursements.*`, `loans.*` — pengajuan + approval + settlement.
- `thr-bonus.*` — generate THR/bonus.
- `bank-files.*` — generate & download file bank (pilih bank/format), exception.
- `salary-planning.*` — proposal kenaikan → review budget → approve → publish (effective date) (QA-0043).

## Business rules (BRD §10.3 + §16)
- Payroll engine configurable: gross, tunjangan, potongan, prorate, allowance, deduction, overtime, unpaid leave, variabel (QA-0035).
- **PPh21 TER** sesuai parameter (configurable, dapat diupdate admin berwenang); **rekalkulasi tahunan** Desember (kurang/lebih bayar) (QA-0038/0039).
- **BPJS** Kes & TK dari parameter rate/plafon/basis; pro-rate saat eligibility berubah mid-period (QA-0040/0041).
- **Lock period**: setelah lock, data tidak berubah tanpa **unlock + approval/adjustment** (RULE-004, QA-0036).
- **Retro adjustment**: perubahan gaji efektif masa lalu → selisih dihitung sebagai komponen adjustment periode berjalan (QA-0037). Perubahan master hanya pengaruhi draft/open period sesuai effective date (QA-0163).
- **Slip gaji**: hanya pemilik & role berwenang; akses langsung via URL employee lain ditolak + dicatat security log (RULE-005, QA-0044/0045).
- **Reimbursement**: approved masuk komponen payroll periode pembayaran; tidak double-paid (QA-0046, QA-0151).
- **Loan**: cicilan auto-deduction, outstanding berkurang (QA-0047).
- **THR** prorata masa kerja; **bonus** by eligible salary × multiplier × rating (QA-0048/0049).
- **Bank file** sesuai format bank, total = net pay final; rekening invalid → exception, tidak ikut file (QA-0050/0051).
- **Segregation of Duties**: pembuat payroll ≠ approver (QA-0052).
- Setiap formula/parameter statutory **effective-dated + histori**.

## Workflow hooks
Reimbursement, loan, salary increment, payroll approval, unlock, adjustment → ApprovalEngine (SoD aktif untuk payroll approval).

## Integrasi
Bank (file/API + callback status, QA-0139), BPJS report (QA-0109), Pajak report (QA-0108), ERP/accounting journal (BRD §13). Ambil attendance final/locked dari M02. Terima rating dari M05 untuk bonus/salary planning (QA-0152).

## Notifikasi
Slip gaji tersedia (ESS), status approval payroll/claim/loan, reminder cut-off, hasil disbursement (paid/failed).

## Reports / dashboard
Payroll register, summary payroll cost, bank transfer, PPh21, BPJS, THR/bonus, loan, reimbursement (M09). KPI: waktu proses payroll, jumlah dispute, % slip terkirim, adjustment rate, akurasi disbursement.

## Acceptance (UAT)
QA-0035…QA-0052 (Payroll), QA-0163 (regression recalc), QA-0108/0109 (compliance report). E2E: FLOW-002 (QA-0148), Overtime→Payroll (QA-0149), Claim→Payroll (QA-0151), Performance→Compensation (QA-0152). Integrasi INT-001/002/003. RTM REQ-015…025, REQ-112.
