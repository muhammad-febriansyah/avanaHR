# M08 — ESS/MSS & Mobile

**BRD:** §10.8 (BR-ESS-001…005) · **Tier:** Essential+ · **Fase:** 1 · **Depends:** M01–M03 (data), engine approval, M06 (06-api). Permukaan utama untuk karyawan & manager (web + Flutter).

## Tujuan
Self-service karyawan (ESS) & manager (MSS): transaksi HR rutin, approval cepat, mobile (full/lite/kiosk), notifikasi (email/push/WA), dashboard personal. Target: ≥80% transaksi rutin via self-service (OBJ-04).

## Scope
**ESS:** update data pribadi (ber-approval bila sensitif), lihat slip gaji & dokumen pajak, saldo cuti, ajukan cuti/klaim/lembur, dokumen, status pengajuan.
**MSS:** approval (termasuk bulk), data tim, jadwal, absensi, cuti, lembur, performance, feedback, delegasi.
**Mobile:** Full / Lite (low-bandwidth) / Kiosk (shared device). Notifikasi & dashboard personal + menu favorit.

## Entitas & data dictionary
Mayoritas memakai entitas modul lain (leave_requests, claims, attendance, payslips, approval_requests). Tambahan:
**user_favorites** — `tenant_id, user_id, menu_key, order` (QA-0102).
**user_dashboard_widgets** — `tenant_id, user_id, widget_key, config(json), order`.
**device_registrations** — `tenant_id, user_id, device_id, platform, fcm_token, biometric_enabled, last_seen`.
**kiosk_devices** — `tenant_id, branch_id, device_id, name, allowed_features(json)`.
Notifikasi & delivery di `notifications` (core).

**Index wajib:** `user_favorites(tenant_id, user_id)`, `device_registrations(tenant_id, user_id)`, `device_registrations(device_id)`.

## Pages / routes
**Web ESS** — `ess.dashboard` (widget + favorit), `ess.profile.edit` (data pribadi, sensitif → approval), `ess.payslips`, `ess.tax-docs`, `ess.leave` (saldo + ajukan), `ess.claims`, `ess.requests` (status pengajuan), `ess.documents`.
**Web MSS** — `mss.dashboard`, `mss.approvals` (DataTable pending + **bulk approve**), `mss.team` (profil tim, field sesuai permission), `mss.team-calendar`, `mss.team-attendance`, `mss.delegations`.
**Mobile (Flutter + GetX)** — Login (Sanctum + passcode/biometric) → Home (dashboard) → Attendance (clock-in face+GPS, offline) → Leave/Claim/Overtime submit → Approvals (MSS) → Payslip → Notifications. Mode Lite & Kiosk.

## Business rules (BRD §10.8 + §16)
- Update data **sensitif** wajib approval + simpan dokumen pendukung (QA-0092).
- Karyawan hanya lihat **slip/dokumen miliknya**; akses lintas user ditolak (QA-0093, QA-0045, IDOR QA-0161).
- Manager hanya lihat data **bawahan sesuai reporting line + permission field** (QA-0098, row-level).
- **Bulk approve**: yang eligible disetujui, yang tidak gagal dengan alasan jelas (QA-0097).
- Saldo cuti tampil available/pending/used/expired benar (QA-0095).
- **Mobile Lite**: fungsi inti jalan di bandwidth rendah, responsif (QA-0099, QA-0168).
- **Kiosk**: pilih employee + PIN/face, hanya fitur disetujui, **tidak buka data pribadi employee lain** (QA-0100).
- **Notifikasi** email/push/WA sesuai channel aktif + **fallback**; preview tidak bocorkan data sensitif (QA-0101, QA-0140).
- Menu favorit tersimpan per user, persist setelah login ulang (QA-0102).
- Mobile minta passcode/biometric sebelum buka data sensitif (QA-0117).

## Workflow hooks
Semua pengajuan ESS → ApprovalEngine; MSS = sisi approver (single + bulk). Delegasi via `approval_delegations`.

## Integrasi & mobile
- API `/api/v1` (06): auth, attendance clock, leave/claim, approvals, payslip, notif. Offline sync attendance.
- FCM push; deep-link approval/slip; WA via WABA + fallback.

## Notifikasi
Approval diminta/diputuskan, reminder cut-off/dokumen, slip tersedia, status pengajuan.

## Reports / dashboard
ESS adoption, MSS approval SLA, mobile active users, jumlah transaksi manual HR, notification success rate (M09).

## Acceptance (UAT)
QA-0092…QA-0102 (ESS/MSS), QA-0117 (biometric mobile), QA-0168 (low bandwidth), QA-0101/0140 (notif). E2E: FLOW-009 Approval (QA-0157). RTM REQ-054…064, REQ-117. UAT mobile inti: clock-in, pengajuan, approval, slip, notifikasi (AC-010).
