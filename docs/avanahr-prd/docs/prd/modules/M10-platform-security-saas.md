# M10 — Platform, Security & SaaS

**BRD:** §10.10 (BR-PLT-001…008) · **Tier:** semua (fondasi) · **Fase:** 1 (multi-tenant/RBAC/audit/auth/encryption) → 4 (app builder, advanced). **Dibangun PERTAMA.**

## Tujuan
Fondasi enterprise: multi-tenant aman & terisolasi, configurable (no-code), API + sandbox, RBAC granular, audit log, SSO/MFA, enkripsi, fleksibel deployment.

## Scope
Multi-tenancy & tenant provisioning · no-code configuration (workflow/form/field/permission/layout/report) · app builder + conflict detection · API & sandbox · RBAC + audit log · SSO/MFA + mobile passcode/biometric · enkripsi + backup/restore · cloud/private/on-prem.

## Entitas & data dictionary
**tenants, tenant_settings, tenant_subscriptions** (core, `04`).
**tenant_provisions** — `tenant_id, status, default_config_applied, created_by, admin_user_id` (QA-0112).
**configurations** — `tenant_id, scope(workflow|form|field|permission|layout|report), key, value(json), version, status(draft|published), published_at` (promosi staging→prod + versioning + rollback, QA-0119/0122).
**custom_fields / custom_field_values** (core) — no-code field (QA-0120).
**app_builder_changes** — `tenant_id, type, spec(json), status, conflict_report(json)` (conflict detection saat update, QA-0121).
**api_clients** — `tenant_id, name, environment(sandbox|production), scopes(json), status`.
**api_tokens** — `client_id, token_hash, scopes, expires_at` (QA-0135/0136).
**audit_logs** (core) — sensitive changes (QA-0114).
**security_events** — `tenant_id, user_id, type(failed_login|lockout|idor_attempt|cross_tenant|export), meta(json), ip, ua, at` (QA-0045/0111/0160/0161).
**sso_configurations** — `tenant_id, protocol(saml|oidc), metadata, attribute_mapping(json), mfa_required`.
**mfa_settings** — per user (core users).
**backups** — `tenant_id|global, type, location, status, created_at`.

**Index wajib:** `configurations(tenant_id, scope, key)`, `api_tokens(token_hash)`, `security_events(tenant_id, type, at)`, `tenant_subscriptions(tenant_id)`.

## Pages / routes
- `admin.tenants.*` (super-admin) — provisioning tenant (profile/locale/timezone/lang + admin tenant + config default + **data terisolasi**) (QA-0112).
- `admin.subscription` — tier & feature flags per tenant.
- `settings.roles.*` / `settings.permissions.*` — custom role + permission granular (menu/action/field) (QA-0113).
- `settings.workflow.*` — no-code workflow (mis. ubah jumlah approver cuti) → berlaku request baru tanpa deploy (QA-0119).
- `settings.fields.*` — custom field (tipe, mandatory) muncul di form/report/API (QA-0120).
- `settings.layout.*`, `settings.reports.*` — konfigurasi layout/report.
- `settings.integrations.*` — SSO/MFA, API client/token (sandbox/prod), webhook, bank/WA/email gateway.
- `settings.app-builder.*` — custom feature/field + **conflict report** saat update sistem (QA-0121).
- `settings.security.*` — session timeout, password policy, lockout, audit log viewer, security events.
- `settings.deployment` — parameter deployment (storage/integration endpoint) tanpa hardcode (QA-0122).

## Business rules (BRD §10.10 + §16)
- **Tenant isolation** wajib & teruji: tenant A tidak akses data/API tenant B (QA-0111); IDOR/direct object reference ditolak (QA-0161); akses lintas tenant dicatat security event.
- Tenant baru aktif dengan config default & data kosong terisolasi (QA-0112).
- **RBAC granular** menu/action/field; view tanpa edit dipatuhi (QA-0113). Perubahan role/permission **diaudit** (QA-0114).
- **Audit log** catat old/new, actor, timestamp, IP/device untuk perubahan sensitif (QA-0114).
- **SSO** login + role mapping (QA-0115); **MFA** tolak OTP salah, terima valid (QA-0116); mobile passcode/biometric (QA-0117).
- **Enkripsi** at-rest + TLS in-transit; field sensitif **masked** sesuai role (QA-0118).
- **Session timeout** & **brute-force lockout** (QA-0159/0160).
- **No-code config** berlaku tanpa coding/deploy; perubahan kritikal butuh approval + version history; promosi staging→production dengan rollback (QA-0119/0121/0122).
- **App builder** deteksi konflik & tidak menimpa custom tanpa konfirmasi (upgrade-safe) (QA-0121).
- **API**: production wajib auth + rate limit; token sandbox hanya sandbox; idempotency (QA-0135/0136/0165).
- Backup/restore sesuai kebijakan.

## Integrasi
SSO/IdP (SAML/OIDC, INT-006), API consumer (sandbox/prod), gateway notif (email/WA/push, INT-007). Deployment cloud/private/on-prem tanpa endpoint hardcoded.

## Acceptance (UAT — banyak Security/Critical)
QA-0111…QA-0122 (Platform), QA-0159/0160/0161 (security regression), QA-0135/0136/0142 (API/SSO), QA-0165 (idempotency). RTM REQ-070…078, REQ-089/095, REQ-110. AC-008 (audit), AC-009 (tenant isolation), AC-005 (RBAC). Security cases = 10 (Dashboard UAT).
