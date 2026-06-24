# 06 — API & Integration

> Konvensi API publik/mobile + integrasi eksternal. Acuan: Integration Matrix UAT (INT-001…008) & QA-0135…0144.

## 1. API Conventions (`/api/v1`)
- Auth: **Sanctum** personal access token (mobile) + opsi OAuth/OIDC (enterprise). Scope per token (sandbox vs production). (QA-0135/0136.)
- Versioned prefix `/api/v1`. **Resource classes** untuk output; field sensitif dimasking sesuai scope.
- **Error envelope** konsisten: `{ "message": "...", "errors": { field: [..] }, "code": "..." }`. Status HTTP benar (401/403/404/422/409/429).
- **Rate limiting** semua endpoint production; **API key + auth wajib** (BR-PLT-004).
- **Idempotency**: endpoint mutasi menerima header `Idempotency-Key`; request sama tidak buat transaksi ganda, balikan status request awal (QA-0165).
- **Pagination**: cursor/limit-offset, metadata konsisten.
- **Sandbox & staging** terpisah dari production; token sandbox hanya untuk env sandbox (QA-0135).
- Dokumentasi: OpenAPI + developer console (generate token, lihat scope).
- Webhook keluar **ditandatangani** (HMAC) + retry.

### Endpoint inti (mobile/integrasi)
- `POST /auth/token`, `POST /auth/logout`, `GET /me`
- `GET /employees`, `POST /employees` (QA-0137), `GET /employees/{id}`
- `POST /attendance/clock` (web/mobile/device), `POST /attendance/import` (biometrik, QA-0138)
- `GET /payslips`, `GET /leave/balance`, `POST /leave`, `POST /claims`, `GET /approvals`, `POST /approvals/{id}/action`
- `POST /webhooks/bank-callback` (status disbursement, QA-0139)

## 2. Integrasi eksternal
| ID | Arah | Tujuan | Validasi kunci | Negative test |
|----|------|--------|----------------|---------------|
| INT-001 Bank | out (file/API) | Disbursement gaji multi-bank | total net pay, format bank, rekening valid | rekening invalid / transfer duplikat (QA-0050/0051/0139) |
| INT-002 BPJS | out (report/file) | Pelaporan BPJS | konsistensi iuran | nomor BPJS hilang (QA-0109) |
| INT-003 Pajak | out (report/file) | PPh21 TER + tahunan | TER/recalc | PTKP/NPWP invalid (QA-0108/0038/0039) |
| INT-004 Biometrik | in (API/file) | Capture absensi | cegah duplikat, mapping employee | employee ID tak dikenal (QA-0033/0034/0138) |
| INT-005 Mobile | in/out (API) | ESS/attendance mobile | auth, offline sync | token expired / konflik offline (QA-0020/0166) |
| INT-006 SSO/IdP | in (SAML/OIDC) | Autentikasi enterprise | role mapping, MFA | assertion expired (QA-0115/0142) |
| INT-007 Notif | out (email/WA/push) | Approval/reminder/slip | template, delivery, fallback | channel down (QA-0101/0140/0141) |
| INT-008 CRM→Tenant | workflow/API | Onboarding customer→tenant | provisioning | customer/tenant duplikat (QA-0134/0156) |

### Catatan implementasi
- **Bank file**: template per bank (BCA/Mandiri/BNI/BRI/...) sebagai strategy class; hasil = file format bank, total = net pay final, rekening invalid masuk exception (tidak ikut file). Status pembayaran update via callback/result file.
- **PPh21 TER**: parameter tarif TER + PTKP **configurable & effective-dated**; rekalkulasi Desember (QA-0039). Jangan hardcode tarif.
- **BPJS**: rate, plafon, basis upah configurable per tenant; pro-rate saat eligibility berubah mid-period (QA-0041).
- **SSO**: SAML2/OIDC, mapping atribut IdP → role; provisioning user otomatis (QA-0142); MFA enforcement (QA-0116).
- **WhatsApp**: WABA resmi + template approved; **fallback** ke email/push bila WA gagal/nonaktif (QA-0140). Preview tidak bocorkan data sensitif.
- **Bulk import**: template + validasi per baris; baris valid masuk, invalid ditolak dengan pesan (QA-0143). Data migration historis (mutasi/promosi) tetap kronologis by effective date (QA-0167).
- **Scheduled report**: kirim sesuai jadwal + permission penerima; export sensitif **diaudit** (QA-0144, QA-0107).

## 3. Keamanan integrasi
Semua API production: auth + rate limit + audit log. Token punya scope & masa berlaku. Idempotency untuk reliabilitas. Webhook signed. (BRD §10.10 aturan bisnis.)
