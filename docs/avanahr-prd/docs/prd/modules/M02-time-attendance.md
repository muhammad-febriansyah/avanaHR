# M02 — Time & Attendance / Absensi

**BRD:** §10.2 (BR-TNA-001…008) · **Tier:** Essential+ · **Fase:** 1 · **Depends:** M01 (employee, kalender, branch geofence), M08 (mobile), engine approval.

## Tujuan
Otomasi pencatatan waktu kerja, validasi kehadiran (GPS/face/device), shift, cuti, izin, lembur, timesheet, integrasi biometrik. Output final = input payroll & analytics.

## Scope
Clock-in/out web/mobile/kiosk **+ offline sync** · GPS tagging · face recognition · self-correction (ber-approval) · shift kompleks/roster · overtime · cuti & izin (saldo) · timesheet per proyek · import biometrik.

## Entitas & data dictionary
**shifts** — `tenant_id, code, name, start_time, end_time, break_minutes, is_overnight, late_tolerance_min, grace_min`.
**shift_patterns** — `tenant_id, name, type(fixed|rotating|custom), config(json: pola 2-2-3 dll)`.
**schedules** — `tenant_id, employee_id, date, shift_id, status(planned|published|swapped|off)` · `unique(tenant_id, employee_id, date)`.
**shift_swaps** — `tenant_id, requester_id, target_id, date_a, date_b, status, approval_request_id` (QA-0025).
**attendance_logs** (RAW, immutable) — `tenant_id, employee_id, date, type(in|out), timestamp(UTC), source(web|mobile|kiosk|biometric), latitude, longitude, branch_id, face_confidence, device_id, is_offline, offline_captured_at, suspicious(bool)`.
**attendance_daily** (terkomputasi/final) — `tenant_id, employee_id, date, shift_id, clock_in, clock_out, work_minutes, late_minutes, early_leave_minutes, status(present|late|absent|leave|holiday|off), has_correction`.
**attendance_corrections** — `tenant_id, employee_id, date, requested_in, requested_out, reason, attachment_id, status, approval_request_id` (QA-0022/0023).
**overtime_requests** — `tenant_id, employee_id, date, planned_start, planned_end, planned_minutes, actual_minutes, reason, status, approval_request_id, payroll_component_id` (QA-0029/0030).
**leave_types** — `tenant_id, code, name, is_paid, max_days_year, allow_negative, requires_attachment, accrual_rule(json)`.
**leave_balances** — `tenant_id, employee_id, leave_type_id, year, entitled, used, pending, expired, available` (QA-0095).
**leave_requests** — `tenant_id, employee_id, leave_type_id, start_date, end_date, days, reason, attachment_id, status(pending|approved|rejected|cancelled), approval_request_id`.
**timesheets** — `tenant_id, employee_id, date, project_id/task_ref, hours, note, status`.
**biometric_imports** — `tenant_id, device_id, file, status, total, success, failed, error_report` (QA-0033/0034).

**Index wajib:** `attendance_logs(tenant_id, employee_id, date)`, `attendance_logs(tenant_id, date)`, `attendance_daily(tenant_id, employee_id, date)` unique, `schedules(tenant_id, employee_id, date)` unique, `leave_requests(tenant_id, status)`, `overtime_requests(tenant_id, status)`, `leave_balances(tenant_id, employee_id, leave_type_id, year)` unique.

## Pages / routes (no-modal CRUD)
- `attendance.today` — clock-in/out (web) dengan peta lokasi; status hari ini.
- `attendance.index` — DataTable rekap harian (filter karyawan/tim/tanggal range via **DateRangePicker**), kolom: nama, shift, in/out, telat, status badge, ada koreksi.
- `attendance.corrections.*` — ajukan/approve koreksi (form: tanggal date-picker, jam, alasan, lampiran).
- `schedules.index` + `schedules.generate` — buat/publish jadwal dari shift pattern; bulk change.
- `shifts.*`, `shift-patterns.*` — CRUD.
- `overtime.*` — ajukan (sebelum kerja) + approve; perhitungan aktual vs attendance.
- `leave.index/create` — ESS ajukan cuti (saldo divalidasi, **date picker** range); kalender tim untuk manager (overlap + min-manpower warning).
- `leave-types.*`, `leave-balances.*` — admin.
- `timesheets.*` — input per proyek/task.
- `biometric.import` — upload file mesin + mapping + exception list.

## Business rules (BRD §10.2 + §16)
- **Raw attendance immutable**; koreksi = transaksi terpisah ber-approval; final berubah tanpa ubah raw (RULE-003, QA-0022/0023).
- Clock-in simpan timestamp server, GPS, device, face confidence (≥ threshold default 0.85); face mismatch → tolak + tandai **suspicious** (QA-0017/0018/0019).
- **Offline**: antrian lokal, sync timestamp asli, **cegah duplikasi**, anti-backdate/tamper; konflik dgn input manual diselesaikan sesuai policy (QA-0020/0021/0166).
- Geofence per branch (lat/long/radius). Cek kecocokan jadwal/geofence/face/duplikasi/toleransi → Valid/Warning/Exception.
- **Overtime** valid hanya bila melewati jadwal &/atau approved; aktual dihitung dari attendance aktual & policy (QA-0029/0030).
- **Cuti**: saldo berkurang sementara saat pending, final saat approve; tolak/ubah ke unpaid bila melebihi saldo (kecuali policy allow_negative); pembatalan kembalikan saldo (QA-0026/0027, QA-0150).
- Shift kompleks (rotating/2-2-3) tidak bentrok libur/cuti; shift swap butuh approval kedua pihak + atasan (QA-0024/0025).
- Timesheet tidak melebihi jam kerja (warning sesuai toleransi) (QA-0032).
- Biometrik: data valid masuk, error → exception; cegah duplikat by employee+tanggal+timestamp (QA-0033/0034).
- Perubahan jadwal setelah cut-off = adjustment tercatat.

## Workflow hooks
Koreksi absensi, lembur, cuti, shift swap → ApprovalEngine. `onApproved` update `attendance_daily`/saldo/overtime payable + audit.

## Integrasi & mobile
- Mobile (M08): clock-in face+GPS, offline queue, ajukan cuti/lembur, lihat jadwal.
- Biometrik device via import/API (`/attendance/import`, QA-0138).
- Kirim **attendance final/locked** ke Payroll (M03) saat cut-off (FLOW-002).

## Notifikasi
Pengajuan/approval cuti/lembur/koreksi, reminder jadwal/cut-off, warning manpower kalender.

## Reports / dashboard
Kehadiran harian, keterlambatan, absensi, lembur, cuti, koreksi, timesheet (M09). KPI: rasio absensi valid, jumlah koreksi, SLA approval, overtime cost, keterlambatan.

## Acceptance (UAT)
QA-0017…QA-0034 (T&A), QA-0166 (offline conflict), QA-0162 (concurrent clock-in peak). E2E: FLOW-002 Time-to-Payroll (QA-0148), FLOW (Overtime QA-0149, Leave QA-0150). RTM REQ-008…014, REQ-115.
