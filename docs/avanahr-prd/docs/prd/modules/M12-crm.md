# M12 — CRM Extension

**BRD:** §10.12 (BR-CRM-001…005) · **Tier:** Enterprise (extension) · **Fase:** 4 · **Depends:** M10 (tenant provisioning handoff), engine approval. *Status: ringkas — expand ke FSD penuh saat dijadwalkan.*

> Extension, bukan inti HRIS. Akses **terpisah** & tidak mengganggu isolasi data HR/payroll (BR-CRM aturan bisnis).

## Tujuan & scope
Customer/prospect master · lead/opportunity/pipeline · activity tracking · customer contract · CRM→HRIS handoff (won deal → tenant provisioning). Berguna saat AvanaHR dipakai sebagai platform enterprise dengan tracking sales/service.

## Entitas (data dictionary ringkas)
**crm_customers** `tenant_id, company_name, segment, owner_id, contacts(json)` · **crm_contacts** `customer_id, name, email, phone, role`.
**crm_leads** `tenant_id, company, contact, source, owner_id, status(new|qualified|disqualified), dedup_key` (QA-0130).
**crm_opportunities** `tenant_id, lead_id, customer_id, value(BIGINT), stage(qualification|proposal|negotiation|won|lost), probability, expected_close_date` (QA-0131/0132).
**crm_activities** `tenant_id, opportunity_id|customer_id, type(call|meeting|task|followup), note, due_at, status`.
**customer_contracts** `tenant_id, opportunity_id, period_start, period_end, value, status, approval_request_id` (QA-0133).
**crm_handoffs** `tenant_id, contract_id, tenant_request_id, status` → memicu **tenant provisioning** (M10) (QA-0134).
**crm_tickets** `tenant_id, customer_id, category, priority, owner_id, sla_due_at, status` (customer service).

**Index:** `crm_leads(tenant_id, status)`, `crm_opportunities(tenant_id, stage)`, `crm_activities(tenant_id, due_at)`, `customer_contracts(tenant_id, status)`.

## Pages (no-modal CRUD)
`crm.leads.*`, `crm.opportunities.*` (pipeline + forecast), `crm.activities.*`, `crm.customers.*`, `crm.contracts.*`, `crm.tickets.*`, dashboard pipeline/conversion.

## Business rules (BRD §10.12 + §16)
- Lead tersimpan dgn owner + status New; convert lead → opportunity terhubung lead asal (QA-0130/0131).
- Stage opportunity berubah tercatat (tanggal+alasan) + forecast update (QA-0132).
- **Won** → generate customer contract; **contract approved** → trigger **tenant setup/implementation** (handoff CRM→Platform) (QA-0133/0134, QA-0156).
- Akses data customer by **owner/team/territory/role**; discount/diskon di atas threshold butuh **approval** + audit.
- Ticket pelanggan punya kategori/priority/owner/SLA.
- CRM **tidak mengganggu isolasi** data HR & payroll.

## Workflow / integrasi / notif
Approval: discount/term, contract. Handoff ke M10 (provisioning tenant, INT-008). Notif aktivitas/follow-up/SLA.

## Acceptance (UAT)
QA-0130…QA-0134. E2E: FLOW-008 Lead-to-Tenant (QA-0156). RTM REQ-084…088.
