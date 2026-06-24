# 01 — Architecture

## 1. Stack
**Web:** Laravel 13 (PHP 8.3+) · Inertia 2 · React 18 + TypeScript · Vite · Tailwind v4 · shadcn/ui (Radix) · TanStack Table · react-day-picker · Sonner · lucide-react.
**Data:** PostgreSQL 16 (utama; MySQL 8 didukung) · Redis (cache/queue/session) · Laravel Horizon.
**Auth:** Laravel Sanctum (web session + mobile personal access token). SSO SAML/OIDC opsional via `socialite`/`saml2` (enterprise).
**Mobile:** Flutter + GetX · Dio · hive/get_storage (offline) · camera + face ML · geolocator · firebase_messaging.
**Storage:** S3-compatible (private, signed URL, prefix per-tenant).
**Async:** Redis queue + Horizon untuk payroll run, export, bulk import, notifikasi (idempotent).

## 2. Layering (Service–Repository–Action)
```
Request → Controller(thin) → FormRequest(validate) → Policy(authorize)
        → Action(use-case) / Service(orchestration) → Repository(data) → Model
        → Event → Listener → Queued Job (notif, recalc, export)
        → Response: Inertia render (web)  |  API Resource (mobile/api)
```
- **Action** = satu use-case (mis. `RunPayrollAction`, `ApproveLeaveAction`). Mengembalikan DTO.
- **Service** = orkestrasi beberapa action / proses panjang (mis. `PayrollEngine`, `ApprovalEngine`).
- **Repository** = satu-satunya tempat query + eager loading suatu domain (hindari query tersebar).
- **DTO** = `spatie/laravel-data` untuk in/out yang konsisten.
- **Enum** = status & tipe sebagai PHP enum (di-cast di model).

## 3. Multi-Tenancy (KEPUTUSAN INTI)
**Single database, row-level tenancy** untuk MVP.

- Setiap tabel tenant-scoped punya `tenant_id` (BIGINT, indexed).
- **`BelongsToTenant` trait** memasang **global scope** `TenantScope` (auto-filter `where tenant_id = current`) + auto-set `tenant_id` saat create.
- Tenant aktif di-resolve dari user terautentikasi (atau subdomain `tenant.avanahr.id`) via middleware `ResolveTenant` → simpan di container singleton `CurrentTenant`.
- **RBAC per tenant** pakai `spatie/laravel-permission` **teams mode** (`team_id = tenant_id`).
- **Cache & queue keys** selalu di-prefix tenant: `cache()->tags(["tenant:{$id}"])`, job membawa `tenant_id` dan re-bind tenant di handle().
- **Storage** prefix `tenants/{id}/...`.
- **Super-admin platform** (cross-tenant) pakai connection/role khusus yang melepas global scope secara eksplisit (audited).

**Isolation tests wajib:** tenant A tidak bisa baca data/API tenant B; IDOR/direct object reference ditolak. (UAT QA-0111, QA-0161.)

**Graduation path:** untuk enterprise/on-prem yang butuh isolasi fisik → **database-per-tenant** (tenancy bootstrapper switch koneksi). Desain repository & model harus tetap kompatibel (jangan hardcode nama DB).

## 4. Struktur Project (lihat juga /CLAUDE.md §2)
Backend `app/{Actions,Services,Repositories,Models,Data,Http,Policies,Enums,Support}`.
Frontend `resources/js/{Pages,components,layouts,lib,types}`.
Tiap modul punya namespace domain konsisten (mis. `App\Actions\Payroll\*`, `resources/js/Pages/Payroll/*`).

## 5. Mobile (Flutter + GetX)
- Arsitektur: **GetX** (Controller + Binding + Service) per fitur; `Repository` memanggil `ApiClient` (Dio).
- Mode: **Full** (ESS+MSS lengkap) / **Lite** (low-bandwidth: clock-in, cuti, slip, notif) / **Kiosk** (shared device, pilih employee + PIN/face, hanya clock-in).
- **Offline-first attendance**: antrian lokal (hive) → sync saat online, timestamp asli dipertahankan, **anti-backdate/tamper**, konflik diselesaikan sesuai policy. (UAT QA-0020, QA-0166, QA-0168.)
- Auth: Sanctum token + secure storage; lock dengan passcode/biometric sebelum buka data sensitif. (QA-0117.)
- Notif: FCM push; deep-link ke approval/slip. Preview notif **tidak** menampilkan data sensitif penuh.

## 6. Strategi Performa (lihat detail di 02 & 03)
- **DB**: index FK + komposit filter; pagination/cursor server-side; query select kolom seperlunya; chunk untuk batch; materialized summary table untuk dashboard.
- **App**: eager loading wajib (`preventLazyLoading` non-prod); cache master data & permission map (tenant-tagged); queue proses berat; rate limit API.
- **Frontend**: Inertia partial reload (`only`), deferred/lazy props untuk tabel berat, `WhenVisible` below-fold, prefetch-on-hover nav, code-split route, skeleton loader, debounce search 300ms.
- **Target acuan** (validasi): list & approval responsif (<1s p95 beban normal), tahan beban cut-off/payroll & concurrent clock-in (UAT QA-0162), report besar tidak timeout (QA-0164).

## 7. Lingkungan & Deployment
- Env: local → staging → production. **Konfigurasi tenant dapat dipromosikan staging → production** dengan versioning + rollback (BRD §10.10, QA-0122).
- Deployment: cloud (default), private-cloud, on-prem — **tanpa endpoint hardcoded** (semua via config/env).
- Observability: log terstruktur, Horizon dashboard, audit log aplikasi, health checks.

## 8. Security baseline
RBAC granular + field masking · enkripsi at-rest (DB/disk) & in-transit (TLS) · MFA/SSO · session timeout & brute-force lockout (QA-0159, QA-0160) · audit untuk perubahan sensitif/akses/export · proteksi PII sesuai **UU PDP**. Detail di `05-rbac-and-workflow.md` & `06-api-and-integration.md`.
