# M11 — Asset Management

**BRD:** §10.11 (BR-AST-001…005) · **Tier:** Enterprise (extension) · **Fase:** 4 · **Depends:** M01 (employee, exit clearance), engine approval. *Status: ringkas — expand ke FSD penuh saat dijadwalkan.*

## Tujuan & scope
Master aset · assignment ke karyawan/unit (bukti serah terima) · return/transfer/kehilangan/disposal · maintenance + warranty reminder · depreciation reference · stock opname · terhubung onboarding/offboarding (exit clearance).

## Entitas (data dictionary ringkas)
**assets** `tenant_id, asset_no(unique per tenant), serial_number(unique per tenant), category, name, location, status(available|assigned|maintenance|lost|disposed), value(BIGINT), purchase_date, warranty_expired_at, qr_code` (QA-0123/0124).
**asset_assignments** `tenant_id, asset_id, employee_id|unit_id, assigned_at, returned_at, handover_attachment_id, status` (QA-0125/0126).
**asset_transfers** `tenant_id, asset_id, from_holder, to_holder, reason, at`.
**asset_maintenances** `tenant_id, asset_id, ticket_no, issue, status(open|in_progress|done), reported_by, cost` (QA-0127).
**asset_depreciations** `tenant_id, asset_id, method, useful_life_years(default 5), schedule(json), book_value` (QA-0128).
**stock_opnames** `tenant_id, scheduled_at, status` · **stock_opname_items** `opname_id, asset_id, found(bool), note` (QA-0129).

**Index:** `assets(tenant_id, status)`, `unique(tenant_id, serial_number)`, `unique(tenant_id, asset_no)`, `asset_assignments(tenant_id, asset_id)`, `asset_assignments(tenant_id, employee_id)`, `assets(tenant_id, warranty_expired_at)`.

## Pages (no-modal CRUD)
`assets.*` (+ QR), `asset-assignments.*` (assign/return + serah terima), `asset-transfers.*`, `asset-maintenance.*`, `asset-depreciation.*`, `stock-opname.*`.

## Business rules (BRD §10.11 + §16)
- Master aset dgn asset_no unik + status Available; **serial number tidak boleh duplikat** (QA-0123/0124).
- Assign → status Assigned + tampil di profil employee; bukti serah terima wajib (QA-0125).
- **Return saat resign** lewat **exit clearance**: clearance tidak selesai sebelum aset wajib kembali / di-waive role berwenang (QA-0126, QA-0155, RULE-010).
- Maintenance ubah status Under Maintenance + history (QA-0127).
- Depreciation by metode/periode (default 5 tahun) → book value (QA-0128).
- Stock opname: selisih → exception report (QA-0129).
- Perubahan status aset punya alasan + user pencatat; aset hilang/rusak → claim/deduction sesuai policy; warranty/maintenance reminder (RULE-009).

## Workflow / integrasi / notif
Approval: asset request, disposal/write-off, waiver clearance. Terhubung onboarding (M04) & exit clearance (M01). Notif maintenance due / warranty expired / outstanding clearance.

## Acceptance (UAT)
QA-0123…QA-0129. E2E: FLOW-007 Asset-to-Exit (QA-0155). RTM REQ-079…083.
