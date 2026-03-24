# Guard51 — Full Implementation Plan (v1)

**Date:** March 24, 2026  
**Context:** Solo developer, pre-launch, quality-over-speed, clean-slate build  
**Product:** Guard51 — Security Workforce & Operations Management SaaS  
**Target Market:** Nigerian Private Security Companies, Facility Managers, Corporate Security Departments  
**Reference:** Architecture and methodology adapted from Serve JOY v4 rebuild plan

---

## 1. Tech Stack

### Backend: Slim 4 + Doctrine ORM + PostgreSQL

| Layer | Technology | Purpose |
|-------|-----------|---------|
| **Framework** | Slim 4 | HTTP routing, middleware pipeline, PSR-7/15 |
| **ORM** | Doctrine ORM 3 + DBAL 4 | Entity mapping, migrations, repositories |
| **Database** | PostgreSQL 16 | Primary store (UUIDs, JSONB, RLS) |
| **Auth** | firebase/php-jwt | JWT access/refresh tokens |
| **Validation** | symfony/validator | Attribute-based DTO validation |
| **DI Container** | PHP-DI | Autowiring, interface binding |
| **Cache** | Redis (predis/predis) | Sessions, rate limits, feature flags, GPS data buffer |
| **Transactional Email** | ZeptoMail API (by Zoho) | Shift alerts, incident notifications, invoices, onboarding |
| **SMS** | Termii API | OTP, panic alerts, shift reminders (Nigeria) |
| **Subscription Billing** | Paystack | SaaS subscription payments |
| **Client Payments** | Paystack + Manual bank transfer + staff confirmation | Dual payment model for security company clients |
| **Queue** | Symfony Messenger + Redis | Background jobs (GPS processing, report generation, notifications) |
| **File Storage** | Flysystem (S3/local) | Documents, photos, reports, app binaries (APK/IPA/EXE) |
| **PDF** | Dompdf | Invoices, payslips, incident reports, DAR exports |
| **API Docs** | swagger-php (zircote) | Auto-generated OpenAPI |
| **Logging** | Monolog | Structured logging |
| **Testing** | PHPUnit 11 | Unit + integration |
| **WebSocket** | Ratchet (PHP) or Node.js sidecar | Real-time GPS tracking, live map, chat |

### Real-Time Layer: Node.js Sidecar + Redis

| Layer | Technology | Purpose |
|-------|-----------|---------|
| **WebSocket Server** | Node.js + Socket.io | Live GPS map, chat, dispatch alerts |
| **Message Broker** | Redis Pub/Sub | Bridge between PHP backend and WebSocket server |
| **GPS Buffer** | Redis Sorted Sets | Buffer GPS pings before batch-writing to PostgreSQL |
| **Fallback** | HTTP Polling (every 15-60s) | For guards with poor connectivity |

### Frontend: Angular 21

| Layer | Technology | Purpose |
|-------|-----------|---------|
| **Framework** | Angular 21 (standalone components) | Web + PWA |
| **UI** | Angular Material 21 | Component library |
| **State** | Angular Signals + lightweight services | Reactivity (no NgRx) |
| **Charts** | Custom SVG components (no Chart.js) | All data visualizations |
| **Maps** | Google Maps API / OpenStreetMap + Leaflet | Live tracking, geofencing, site maps |
| **i18n** | @ngx-translate | English + Nigerian Pidgin (expandable) |
| **Real-time** | Socket.io-client | Live GPS map, chat, dispatch updates |
| **Offline** | Dexie.js (IndexedDB) | PWA offline data |

### Desktop: Electron

| Layer | Technology | Purpose |
|-------|-----------|---------|
| **Shell** | Electron | Wraps Angular web app for Windows/macOS/Linux |
| **Auto-update** | Custom (self-hosted, no app store) | Version check against API, download from platform |
| **Build** | electron-builder | Generates .exe (Windows), .dmg (macOS), .AppImage (Linux) |

### Mobile: NativeScript + Angular (4 apps)

| App | Users | Key Features | Delivery |
|-----|-------|-------------|----------|
| **Guard App** | Field security guards | Clock in/out, GPS tracking, site tours, panic button, reports, messaging | NativeScript (APK sideload) |
| **Client App** | Security company clients | Live guard tracking, reports, invoices, messaging | NativeScript (APK sideload) |
| **Supervisor App** | Field supervisors | Guard monitoring, attendance, incident management, dispatch | NativeScript (APK sideload) |
| **Dispatcher App** | Control room staff | Dispatch console, panic alerts, guard assignment, live map | NativeScript (APK sideload, tablet) |

### App Distribution Strategy (No App Stores)

All apps are **self-distributed** via the SaaS admin panel — no Google Play or Apple App Store.

**Android (APK):** Security companies download APK directly from admin dashboard, sideload onto guard devices. Android allows this natively with "Install from Unknown Sources" enabled.

**iOS (IPA):** Distributed via Apple Enterprise Developer Program or Ad Hoc provisioning profiles. Companies register device UDIDs, admin generates provisioned IPA.

**Desktop (Electron):** Companies download installer (.exe/.dmg/.AppImage) from admin dashboard. Auto-update checks the API for new versions on launch.

**Version management:** Every binary (APK, IPA, installer) is tracked with version number, release notes, upload date, and minimum API compatibility version. Companies see available updates in their dashboard.

---

## 2. SVG Chart Architecture

### No External Chart Libraries

All charts are custom Angular standalone components rendering `<svg>` elements directly. Zero runtime dependencies. Fully themeable with white-label branding colors.

### Chart Components

```
libs/shared-ui/
  └── charts/
      ├── svg-line-chart/          # Time series (attendance trends, incident frequency)
      ├── svg-bar-chart/           # Comparisons (guards per site, incidents by type)
      ├── svg-stacked-bar-chart/   # Composition over time (hours by shift type)
      ├── svg-pie-chart/           # Distribution (incident types, payment methods)
      ├── svg-donut-chart/         # Distribution with center metric (shift compliance %)
      ├── svg-gauge-chart/         # Single KPI (attendance rate, patrol completion)
      ├── svg-sparkline/           # Inline mini trend (stats card trend indicators)
      ├── svg-heatmap/             # Grid intensity (guard coverage by day/hour, site activity)
      ├── svg-area-chart/          # Filled time series (revenue cumulative)
      └── chart-utils/             # Shared: scales, axes, tooltips, responsive, animations
```

### Chart Component API (Example)

```typescript
<app-svg-line-chart
  [data]="attendanceTrends()"
  [xKey]="'date'"
  [yKeys]="['on_time', 'late', 'absent']"
  [colors]="brandingService.chartColors()"
  [height]="300"
  [showGrid]="true"
  [animate]="true"
  [tooltipFormat]="'number'"
/>

<app-svg-gauge-chart
  [value]="shiftComplianceRate()"
  [max]="100"
  [colors]="brandingService.chartColors()"
  [label]="'Shift Compliance'"
  [format]="'percentage'"
  [height]="200"
/>
```

### Key Design Decisions

- **Responsive:** SVG viewBox scales to container width, components use ResizeObserver
- **Accessible:** ARIA labels, keyboard navigation, screen reader descriptions
- **Animated:** CSS transitions on `<path>`, `<rect>`, `<circle>` elements (GPU-accelerated)
- **Themed:** Colors pulled from `BrandingService` (white-label tenant colors)
- **Interactive:** Hover tooltips via Angular overlay, click events emit data points
- **Shared across web + NativeScript:** NativeScript uses identical SVG rendering
- **Lightweight:** Each chart component is ~200-400 lines, total chart library <3KB gzipped

---

## 3. Payment Model (Dual: Paystack + Manual)

### SaaS Subscription Payments (Paystack)

Security companies subscribe to Guard51 plans via Paystack. This is the primary revenue model.

1. **Company selects plan** during onboarding or upgrade
2. **Paystack checkout** processes card/bank payment
3. **Webhook confirms** payment → subscription activated
4. **Auto-renewal** via Paystack recurring billing

### Manual Bank Transfer (Alternative for SaaS subscriptions)

Some Nigerian security companies prefer bank transfers for SaaS payments:

1. **Company selects plan** and chooses "Pay via Bank Transfer"
2. **System generates a pending subscription** with invoice showing Guard51 bank account details
3. **Company pays via bank transfer**
4. **Guard51 super admin manually confirms payment** in the admin panel:
   - Enter amount received
   - Enter transfer reference
   - Upload proof of payment (optional)
   - Activate subscription
5. **System activates subscription** and sends confirmation email

### Guard51 Bank Account Configuration

Super admin configures Guard51's receiving bank accounts (set once in Platform Settings):

```
PlatformBankAccount
  id              UUID PK
  bank_name       VARCHAR(100)     -- e.g., "Guaranty Trust Bank"
  account_number  VARCHAR(20)      -- e.g., "0123456789"
  account_name    VARCHAR(200)     -- e.g., "DOSTHQ Limited"
  bank_code       VARCHAR(10)      -- CBN bank code
  is_primary      BOOLEAN DEFAULT TRUE
  is_active       BOOLEAN DEFAULT TRUE
  created_at, updated_at
```

### Client Billing (Security Companies → Their Clients)

Security companies bill their own clients (the properties they protect). This uses the **Invoice module**:

1. **Security company creates invoice** for client (based on hours worked, contract terms)
2. **Invoice shows** the security company's bank account details
3. **Client pays** via bank transfer or cash
4. **Security company staff confirms payment** in Guard51
5. **System updates invoice status** (partial/full payment)

### Security Company Bank Account Configuration

Each tenant (security company) stores their own bank account details for client-facing invoices:

```
TenantBankAccount
  id              UUID PK
  tenant_id       UUID FK → Tenant
  bank_name       VARCHAR(100)
  account_number  VARCHAR(20)
  account_name    VARCHAR(200)
  bank_code       VARCHAR(10)
  is_primary      BOOLEAN DEFAULT TRUE
  is_active       BOOLEAN DEFAULT TRUE
  created_at, updated_at
```

---

## 4. GPS Tracking Architecture

### How It Works

Guard51's GPS tracking system uses a **dual approach**: WebSocket for real-time live map updates, and HTTP polling as a fallback for guards with poor connectivity.

### Guard App → Backend Flow

```
1. Guard app collects GPS coordinates every 15 seconds
2. Primary: sends via WebSocket to Node.js sidecar
   Fallback: batches and sends via HTTP POST every 60 seconds
3. Node.js sidecar:
   a. Publishes to Redis Pub/Sub → live map subscribers get instant update
   b. Buffers in Redis Sorted Set (key: guard:{id}:locations, score: timestamp)
4. Background worker (PHP, every 60 seconds):
   a. Reads buffered locations from Redis
   b. Batch-inserts into PostgreSQL GuardLocation table
   c. Runs geofence violation checks
   d. Runs idle detection checks
   e. Clears processed entries from Redis buffer
```

### Live Map (Admin Dashboard)

```
1. Admin opens Live Tracker page
2. Frontend connects to WebSocket (Socket.io)
3. Subscribes to channel: tenant:{id}:guard-locations
4. Receives real-time GPS updates → moves guard markers on map
5. Guard markers show: name, status (patrolling/idle/off-site), last update time
6. Fallback: if WebSocket disconnects, polls GET /api/tracker/live every 15 seconds
```

### Geofence Engine

```
1. Each site has a geofence polygon (or circle with radius)
2. On every GPS ping, system checks: is guard inside assigned site's geofence?
3. If guard exits geofence:
   a. Create GeofenceAlert record
   b. Push notification to supervisor
   c. Highlight guard on live map in red
4. Geofence check uses PostgreSQL PostGIS extension:
   ST_Contains(site_geofence, ST_MakePoint(lng, lat))
```

### Idle Detection

```
1. Background worker checks: has guard's GPS moved < 10 meters in last X minutes?
2. X is configurable per site (default: 15 minutes)
3. If idle threshold exceeded:
   a. Create IdleAlert record
   b. Push notification to supervisor
   c. Mark guard as "idle" on live map
```

### Offline GPS Buffering (Guard App)

```
1. Guard app stores GPS pings in local SQLite/IndexedDB when offline
2. When connectivity returns, app sends buffered pings in batch
3. Backend processes with original timestamps (not arrival time)
4. Guard's tracking history shows complete path even through dead zones
```

---

## 5. Modular Feature Architecture

### Module Categories & Tiers

| Category | Module | Tier | Core? | Dependencies |
|----------|--------|------|-------|-------------|
| **Core** | Authentication & Authorization | All | ✅ | — |
| **Core** | Guard Management | All | ✅ | — |
| **Core** | Client Management | All | ✅ | — |
| **Core** | Site/Post Management | All | ✅ | — |
| **Core** | Basic Dashboard | All | ✅ | — |
| **Core** | Post Orders | All | ✅ | Site |
| **Tracking** | Live GPS Tracker | All | ✅ | Guard, Site |
| **Tracking** | Geo-Fencing & Alerts | Starter+ | ❌ | Live Tracker, Site |
| **Tracking** | Idle Detection | Starter+ | ❌ | Live Tracker |
| **Tracking** | Patrol History & Replay | Starter+ | ❌ | Live Tracker |
| **Scheduling** | Shift Scheduling | All | ✅ | Guard, Site |
| **Scheduling** | Shift Templates | Starter+ | ❌ | Scheduling |
| **Scheduling** | Open Shifts | Starter+ | ❌ | Scheduling |
| **Scheduling** | Shift Swap & Exchange | Professional+ | ❌ | Scheduling |
| **Scheduling** | Guard Availability | Professional+ | ❌ | Scheduling |
| **Attendance** | Time Clock (Check-In/Out) | All | ✅ | Guard, Site |
| **Attendance** | Geofence-Based Clock In/Out | Starter+ | ❌ | Time Clock, Geo-Fencing |
| **Attendance** | Attendance Reconciliation | Professional+ | ❌ | Time Clock |
| **Attendance** | Break Management | Professional+ | ❌ | Time Clock |
| **Attendance** | Time-Off / Leave Management | Professional+ | ❌ | Guard |
| **Operations** | Site Tours (NFC/QR/Virtual) | Starter+ | ❌ | Guard, Site |
| **Operations** | Passdown Logs | Starter+ | ❌ | Guard, Site |
| **Operations** | Task Management | Starter+ | ❌ | Guard, Site |
| **Operations** | Guard Web Portal | All | ✅ | Guard, Scheduling |
| **Reporting** | Daily Activity Reports (DAR) | All | ✅ | Guard, Site |
| **Reporting** | Incident Reporting | All | ✅ | Guard, Site |
| **Reporting** | Custom Report Builder | Professional+ | ❌ | Reporting |
| **Reporting** | Watch Mode (Video/Photo Logs) | Professional+ | ❌ | Guard |
| **Reporting** | Automated Report Sharing (Client) | Professional+ | ❌ | Reporting, Client |
| **Emergency** | Panic Button | All | ✅ | Guard |
| **Emergency** | Dispatcher Console | Starter+ | ❌ | Guard, Site |
| **Emergency** | Incident Escalation Workflow | Professional+ | ❌ | Incident Reporting |
| **Vehicle** | Vehicle Patrol Management | Professional+ | ❌ | Guard, Site |
| **Vehicle** | Vehicle Patrol Reports | Professional+ | ❌ | Vehicle Patrol |
| **Visitor** | Visitor Management | Professional+ | ❌ | Site |
| **Parking** | Parking Manager | Professional+ | ❌ | Site |
| **Finance** | Invoice & Estimate Management | Starter+ | ❌ | Client |
| **Finance** | Payroll Generation | Professional+ | ❌ | Time Clock, Guard |
| **Finance** | Pay Rate Multiplier (Overtime) | Professional+ | ❌ | Payroll |
| **Communication** | Messenger / Chat | Starter+ | ❌ | Guard |
| **Communication** | In-App Notifications | All | ✅ | — |
| **Communication** | SMS Alerts (Termii) | Starter+ | ❌ | — |
| **Communication** | Email Notifications (ZeptoMail) | All | ✅ | — |
| **Client Experience** | Client Web Portal | Starter+ | ❌ | Client |
| **Client Experience** | Client Mobile App | Professional+ | ❌ | Client Portal |
| **Analytics** | Basic Analytics | All | ❌ | — |
| **Analytics** | Advanced Analytics & BI | Business+ | ❌ | Basic Analytics |
| **Analytics** | Custom Reports (Export) | Business+ | ❌ | Basic Analytics |
| **Security** | Audit Logging | Business+ | ❌ | — |
| **Security** | 2FA / Advanced Security | Business+ | ❌ | Auth |
| **Security** | Guard License Management | Professional+ | ❌ | Guard |
| **Customization** | White-Label Branding | Enterprise | ❌ | — |
| **Customization** | Multi-Property Support | Enterprise | ❌ | — |
| **Customization** | Dark Mode | All | ❌ | — |
| **Integrations** | Multi-Language (EN, Pidgin, Yoruba, Hausa) | Enterprise | ❌ | — |
| **Integrations** | Offline Mode (Guard App) | All | ❌ | — |
| **Platform** | App Distribution Platform | All | ✅ | — |
| **Platform** | Desktop Electron App | All | ❌ | — |

**Total: 52 modules**

---

## 6. App Distribution Platform

### Overview

All mobile and desktop apps are managed, versioned, and distributed from the **Super Admin panel**. Security companies download apps directly — no Google Play or Apple App Store.

### Entities

```
AppRelease
  id              UUID PK
  app_key         ENUM(guard, client, supervisor, dispatcher, desktop_windows, desktop_mac, desktop_linux)
  version         VARCHAR(20)           -- semver: "1.2.3"
  version_code    INT                   -- incrementing integer (Android versionCode)
  platform        ENUM(android, ios, windows, macos, linux)
  release_type    ENUM(stable, beta, alpha)
  min_api_version VARCHAR(20)           -- minimum backend API version required
  file_url        VARCHAR(500)          -- S3/storage path to binary
  file_size_bytes BIGINT
  file_hash_sha256 VARCHAR(64)          -- integrity verification
  release_notes   TEXT                  -- markdown changelog
  is_mandatory    BOOLEAN DEFAULT FALSE -- force update if true
  is_active       BOOLEAN DEFAULT TRUE  -- can be deactivated to pull a broken release
  uploaded_by     UUID FK → User
  uploaded_at     TIMESTAMP
  created_at      TIMESTAMP

AppDownloadLog
  id              UUID PK
  release_id      UUID FK → AppRelease
  tenant_id       UUID FK → Tenant NULLABLE
  downloaded_by   UUID FK → User NULLABLE
  ip_address      VARCHAR(45)
  user_agent      TEXT
  downloaded_at   TIMESTAMP

TenantAppConfig
  id              UUID PK
  tenant_id       UUID FK → Tenant
  app_key         VARCHAR(50)
  auto_update     BOOLEAN DEFAULT TRUE
  pinned_version  VARCHAR(20) NULLABLE  -- override: lock to specific version
  settings        JSONB DEFAULT '{}'     -- app-specific tenant config
  created_at, updated_at
```

### Super Admin: App Management Pages

- **App Releases Dashboard:** Grid of all 7 app types (4 mobile + 3 desktop platforms), current stable version, total downloads, last updated
- **Upload New Release:** Select app type + platform, upload binary, enter version + release notes, set release type, toggle mandatory update
- **Release History:** Version history per app with download counts, deactivate broken releases, rollback
- **Download Analytics:** Downloads per app, per tenant, per time period

### Company Admin: App Downloads Page

Companies see this in their **Settings → Apps & Downloads** page:

- **Available Apps** section showing each app relevant to their plan
- Download buttons with QR codes for mobile (staff scan with their phone)
- Installation instructions per platform
- "What's New" expandable with release notes
- Desktop installers for Windows/macOS/Linux

### App Auto-Update Flow

```
1. App launches → calls GET /api/apps/check-update?app=guard&platform=android&current_version=1.2.0
2. API responds:
   {
     "update_available": true,
     "latest_version": "1.3.0",
     "is_mandatory": false,
     "download_url": "https://storage.../guard-1.3.0.apk",
     "release_notes": "GPS accuracy improvements, bug fixes...",
     "file_size": 18234567,
     "file_hash": "abc123..."
   }
3. App shows update prompt (mandatory = force, optional = dismiss)
4. App downloads binary, verifies hash, installs
```

---

## 7. Phased Implementation Plan

---

### PHASE 0: SaaS Platform & Foundation (Weeks 1-6)

**Goal:** Complete SaaS business layer — tenant onboarding, Paystack + manual bank transfer subscription billing, feature toggles, usage metering, white-label branding, app distribution platform, super admin console, and Angular shell.

#### Phase 0A: Project Setup & Dev Environment

**Tasks:**
- Nx monorepo: `apps/api`, `apps/web`, `apps/desktop`, `apps/realtime` (Node.js sidecar), `libs/shared-types`, `libs/shared-services`, `libs/shared-ui` (SVG charts)
- Slim 4 skeleton: PHP-DI, Monolog, CORS, error handling, request ID
- Doctrine ORM: PostgreSQL config + PostGIS extension, migration tooling, tenant filter
- Docker Compose: PostgreSQL 16 + PostGIS, Redis 7, PHP 8.3-FPM + Nginx, Node.js sidecar, Angular dev server
- ZeptoMail SDK setup and email template structure
- Environment config, Git

**Deliverables:**
- `docker-compose up` boots full stack
- `GET /api/health` returns OK
- ZeptoMail sends test email
- PostGIS enabled and functional

#### Phase 0B: Tenant & Multi-Tenancy Foundation

**Entities:** Tenant, User, TenantBankAccount, PlatformBankAccount, RefreshToken, AuditLog

**Key:** TenantBankAccount for security company's own bank details (displayed on client invoices). PlatformBankAccount for Guard51's receiving account (displayed on subscription invoices for manual bank transfer payments).

**Deliverables:** Multi-tenant foundation, seed data, tenant filter

#### Phase 0C: Authentication & Authorization

**Backend:** AuthController (login, register, refresh, logout, me, forgot/reset password), AuthMiddleware, TenantMiddleware, RoleMiddleware, FeatureMiddleware, RateLimitMiddleware. Argon2ID hashing.

**Frontend:** AuthService, AuthStore (signals), AuthInterceptor, AuthGuard, RoleGuard, login page.

**Email:** Welcome email and password reset email via ZeptoMail.

**Roles defined:**
1. Super Admin (Guard51 platform team)
2. Company Admin (security company owner/manager)
3. Supervisor (field supervisor)
4. Guard (field operative)
5. Client (property owner viewing portal)
6. Dispatcher (control room staff)

#### Phase 0D: Feature Module & Subscription Engine

**Entities:** FeatureModule (52 modules), TenantFeatureModule, SubscriptionPlan (custom + default), Subscription, SubscriptionInvoice, TenantUsageMetric

**Key features:**
- **Dual billing:** Paystack auto-charge + manual bank transfer confirmation
- Super admin can **create custom subscription plans** with any combination of modules, limits, and pricing
- Plan includes: name, tier, monthly/annual price, max_guards, max_sites, max_clients, included_modules (JSONB array of module_keys), custom feature flags
- Manual bank transfer flow: pending subscription → super admin confirms payment → subscription activated

**Custom Plan Creation Flow (Super Admin):**
1. Enter plan name and pricing (monthly + annual, in NGN)
2. Set limits (guards, sites, clients)
3. Checkboxes to select which of the 52 modules are included
4. Dependency auto-resolution (selecting `payroll` auto-enables `time_clock`)
5. Save → plan appears in public pricing page + onboarding flow
6. Can create tenant-specific private plans (visible only to one tenant)

**Subscription Plans (Default):**

| Plan | Price (Monthly) | Guards | Sites | Key Modules |
|------|----------------|--------|-------|------------|
| **Starter** | ₦25,000 | 25 | 5 | Core + GPS tracking, basic scheduling, time clock, DAR, panic button |
| **Professional** | ₦75,000 | 100 | 20 | + Geo-fencing, site tours, vehicle patrol, visitor mgmt, invoicing, custom reports |
| **Business** | ₦150,000 | 300 | 50 | + Payroll, analytics, audit logging, 2FA, WhatsApp |
| **Enterprise** | Custom | Unlimited | Unlimited | + White-label, multi-property, multi-language, all modules |

**API Endpoints:**
```
# Feature Modules
GET    /api/features/modules
GET    /api/features/tenant
POST   /api/features/tenant/enable/:moduleKey
POST   /api/features/tenant/disable/:moduleKey

# Subscription Plans (Super Admin)
GET    /api/admin/plans
POST   /api/admin/plans
PUT    /api/admin/plans/:id
DELETE /api/admin/plans/:id
POST   /api/admin/plans/:id/duplicate

# Subscriptions (Public + Tenant)
GET    /api/subscriptions/plans
GET    /api/subscriptions/current
POST   /api/subscriptions/initialize          -- Paystack checkout
POST   /api/subscriptions/verify
POST   /api/subscriptions/webhook             -- Paystack webhook
POST   /api/subscriptions/bank-transfer       -- initiate manual bank transfer
POST   /api/admin/subscriptions/:id/confirm-payment  -- super admin confirms
POST   /api/subscriptions/upgrade
POST   /api/subscriptions/cancel

# Usage
GET    /api/usage/current
GET    /api/usage/history
GET    /api/usage/limits
```

#### Phase 0E: Tenant Onboarding & Management

**Entities:** TenantInvitation

**Backend:**
- Self-service onboarding wizard (7 steps):
  1. Company info (name, RC number, address, phone)
  2. Admin account (name, email, password)
  3. Select subscription plan
  4. Payment method (Paystack card/bank OR manual bank transfer)
  5. Set up company bank account (for client invoices)
  6. Add first site/post (name, address, geofence)
  7. Invite first guards
- Staff invitation flow (email via ZeptoMail)
- White-label branding (logo upload, colors, custom domain)

#### Phase 0F: App Distribution Platform

**Entities:** AppRelease, AppDownloadLog, TenantAppConfig

**Backend:**
- Super admin: upload/manage app binaries (APK, IPA, .exe, .dmg, .AppImage)
- Version tracking with semver + release notes
- Signed download URLs (time-limited)
- Version check endpoint for auto-update
- App heartbeat endpoint
- Download analytics

**Frontend (Super Admin):**
- App releases dashboard (7 app types grid)
- Upload new release wizard
- Release history with download counts

**Frontend (Company Admin):**
- Apps & Downloads page with QR codes
- Installation instructions

#### Phase 0G: Super Admin Console (Frontend)

**Pages:**
- **Platform Dashboard:** tenants, MRR, signups, revenue trends (SVG charts), churn
- **Tenant Management:** list, detail, suspend/reactivate, impersonate, usage
- **Subscription Management:** plan CRUD, active subscriptions, failed payments, pending bank transfers, revenue by plan (SVG pie chart)
- **Payment Confirmation:** list of pending bank transfer payments, confirm/reject with reference
- **Feature Module Management:** module list, tier assignment, dependency visualization
- **App Management:** releases, uploads, downloads, version tracking
- **Usage Analytics:** platform-wide + per-tenant breakdown
- **Platform Settings:** ZeptoMail config, Termii config, Paystack keys, Guard51 bank accounts, trial duration, global flags

#### Phase 0H: Company Admin Angular Shell + SVG Charts

**Frontend:**
- Dynamic sidebar (feature-gated: only shows enabled modules)
- Header with property/site switcher
- Angular Material 21 theme with white-label
- Core services: ApiService, ToastService, ConfirmDialogService, FeatureService, BrandingService, MapService
- Shared UI: DataTable, StatsCard, PageHeader, EmptyState, FeatureGate
- **SVG Chart library:** Line, Bar, Stacked Bar, Pie, Donut, Gauge, Sparkline, Heatmap, Area (built in this sub-phase)
- Lazy routes with feature guards
- Error + Auth interceptors

**Phase 0 Deliverables:**
- ✅ Self-service tenant onboarding with Paystack + bank transfer billing
- ✅ 52 feature modules with dependency-aware toggles
- ✅ Custom subscription plan creation
- ✅ App distribution platform (APK, IPA, desktop binaries)
- ✅ White-label branding
- ✅ Super Admin console with SVG charts
- ✅ Dynamic sidebar (only shows enabled modules)
- ✅ ZeptoMail transactional emails
- ✅ Company bank account setup for client invoices
- ✅ **Deployable SaaS platform**

---

### PHASE 1: Core Guard & Site Management (Weeks 7-11)

**Goal:** Guards, sites, clients, post orders, guard web portal, dashboard. Core modules on ALL plans. After this phase, a security company can manage their basic operations.

#### Phase 1A: Site/Post Management

**Entities:**
```
Site
  id              UUID PK
  tenant_id       UUID FK → Tenant
  client_id       UUID FK → Client NULLABLE
  name            VARCHAR(200)         -- "Lekki Phase 1 Estate"
  address         TEXT
  city            VARCHAR(100)
  state           VARCHAR(100)
  latitude        DECIMAL(10,8)
  longitude       DECIMAL(11,8)
  geofence_radius INT DEFAULT 100      -- meters (for circular geofence)
  geofence_polygon GEOMETRY NULLABLE   -- PostGIS polygon (for complex shapes)
  geofence_type   ENUM(circle, polygon) DEFAULT 'circle'
  contact_name    VARCHAR(200) NULLABLE
  contact_phone   VARCHAR(50) NULLABLE
  contact_email   VARCHAR(255) NULLABLE
  timezone        VARCHAR(50) DEFAULT 'Africa/Lagos'
  status          ENUM(active, inactive, suspended)
  notes           TEXT NULLABLE
  created_at, updated_at

PostOrder
  id              UUID PK
  tenant_id       UUID FK → Tenant
  site_id         UUID FK → Site
  title           VARCHAR(200)
  instructions    TEXT                 -- rich text instructions for guards
  priority        ENUM(critical, high, medium, low)
  category        ENUM(general, access_control, patrol, emergency, visitor, parking)
  effective_from  DATE
  effective_to    DATE NULLABLE        -- NULL = no expiry
  is_active       BOOLEAN DEFAULT TRUE
  created_by      UUID FK → User
  last_updated_by UUID FK → User
  created_at, updated_at
```

**Backend:**
- Site CRUD with plan limit enforcement (max_sites)
- Geofence drawing (circle or polygon via PostGIS)
- Post order CRUD with versioning
- Site assignment to clients

**Frontend:**
- Site list with map overview (all sites plotted on map)
- Site detail page (info, geofence on map, assigned guards, post orders)
- Add/edit site with map-based geofence drawing
- Post orders list and editor (rich text)
- **SVG heatmap:** site activity by day/hour

#### Phase 1B: Guard Management

**Entities:**
```
Guard
  id              UUID PK
  tenant_id       UUID FK → Tenant
  user_id         UUID FK → User       -- links to auth user
  employee_number VARCHAR(50)
  first_name      VARCHAR(100)
  last_name       VARCHAR(100)
  phone           VARCHAR(50)
  email           VARCHAR(255) NULLABLE
  date_of_birth   DATE NULLABLE
  gender          ENUM(male, female, other) NULLABLE
  address         TEXT NULLABLE
  city            VARCHAR(100) NULLABLE
  state           VARCHAR(100) NULLABLE
  photo_url       VARCHAR(500) NULLABLE
  emergency_contact_name   VARCHAR(200)
  emergency_contact_phone  VARCHAR(50)
  hire_date       DATE
  status          ENUM(active, inactive, suspended, terminated)
  pay_type        ENUM(hourly, daily, monthly)
  pay_rate        DECIMAL(10,2) NULLABLE
  bank_name       VARCHAR(100) NULLABLE
  bank_account_number VARCHAR(20) NULLABLE
  bank_account_name VARCHAR(200) NULLABLE
  notes           TEXT NULLABLE
  created_at, updated_at

GuardSkill
  id              UUID PK
  tenant_id       UUID FK → Tenant
  name            VARCHAR(100)         -- "Armed", "Unarmed", "VIP Detail", "K9", "Driver"
  description     TEXT NULLABLE
  created_at      TIMESTAMP

GuardSkillAssignment
  guard_id        UUID FK → Guard
  skill_id        UUID FK → GuardSkill
  certified_at    DATE NULLABLE
  expires_at      DATE NULLABLE

GuardDocument
  id              UUID PK
  guard_id        UUID FK → Guard
  document_type   ENUM(license, id_card, certificate, medical, contract, other)
  title           VARCHAR(200)
  file_url        VARCHAR(500)
  issue_date      DATE NULLABLE
  expiry_date     DATE NULLABLE
  is_verified     BOOLEAN DEFAULT FALSE
  notes           TEXT NULLABLE
  created_at      TIMESTAMP
```

**Backend:**
- Guard CRUD with plan limit enforcement (max_guards)
- Guard profile with skills, documents, site assignments
- Guard status management (active/inactive/suspended/terminated)
- Document upload with expiry tracking
- Bulk guard import (CSV)

**Frontend:**
- Guard directory (searchable, filterable by status, skill, site)
- Guard profile page (info, skills, documents, assignment history, attendance summary)
- Add/edit guard form
- Document management (upload, expiry alerts)
- Skills management

#### Phase 1C: Client Management

**Entities:**
```
Client
  id              UUID PK
  tenant_id       UUID FK → Tenant
  company_name    VARCHAR(200)
  contact_name    VARCHAR(200)
  contact_email   VARCHAR(255)
  contact_phone   VARCHAR(50)
  address         TEXT NULLABLE
  city            VARCHAR(100) NULLABLE
  state           VARCHAR(100) NULLABLE
  contract_start  DATE NULLABLE
  contract_end    DATE NULLABLE
  billing_rate    DECIMAL(10,2) NULLABLE
  billing_type    ENUM(hourly, daily, monthly, contract)
  status          ENUM(active, inactive, suspended)
  notes           TEXT NULLABLE
  created_at, updated_at

ClientContact
  id              UUID PK
  client_id       UUID FK → Client
  name            VARCHAR(200)
  role            VARCHAR(100)
  email           VARCHAR(255)
  phone           VARCHAR(50)
  is_primary      BOOLEAN DEFAULT FALSE
  created_at      TIMESTAMP
```

**Backend:**
- Client CRUD with plan limit enforcement (max_clients)
- Client contacts management
- Client-site association
- Client portal user creation (for Phase 6)

**Frontend:**
- Client directory
- Client detail page (info, contacts, sites, invoices, guards assigned)
- Add/edit client form

#### Phase 1D: Guard Web Portal

**Purpose:** Lightweight web portal for guards who may not have smartphones or prefer web access.

**Frontend (separate lazy-loaded route group):**
- Guard login (same auth, role-restricted views)
- My schedule (upcoming shifts)
- Clock in/out button (GPS-validated)
- Submit daily report (text + photo upload)
- View post orders for current site
- View assigned tasks
- Request shift swap
- Update attendance status

#### Phase 1E: Dashboard

**Entities:**
```
DailySnapshot
  id              UUID PK
  tenant_id       UUID FK → Tenant
  snapshot_date   DATE
  total_guards    INT
  guards_on_duty  INT
  guards_late     INT
  guards_absent   INT
  total_sites     INT
  sites_covered   INT
  incidents_count INT
  shifts_total    INT
  shifts_filled   INT
  created_at      TIMESTAMP
```

**Frontend:**
- Stats cards with **SVG sparklines**: guards on duty, attendance rate, incidents today, sites covered
- **SVG line chart:** attendance trends (7/30/90 days)
- **SVG donut chart:** guard status distribution (on duty, off duty, late, absent)
- **SVG bar chart:** incidents by type
- Activity feed (recent clock-ins, incidents, reports)
- Quick actions (create shift, add guard, view live map)

**Phase 1 Deliverables:**
- ✅ Site management with geofence drawing
- ✅ Guard management with skills, documents, assignments
- ✅ Client management with contacts
- ✅ Post orders per site
- ✅ Guard web portal for non-smartphone guards
- ✅ Dashboard with SVG charts
- ✅ **Security company can manage guards, sites, and clients**

---

### PHASE 2: Scheduling & Attendance (Weeks 12-15)

**Goal:** Shift scheduling, time clock, attendance tracking, passdown logs.

#### Phase 2A: Shift Scheduling

**Entities:**
```
ShiftTemplate
  id              UUID PK
  tenant_id       UUID FK → Tenant
  name            VARCHAR(100)         -- "Day Shift", "Night Shift", "Weekend"
  start_time      TIME
  end_time        TIME
  days_of_week    JSONB                -- [0,1,2,3,4] (Mon-Fri)
  is_active       BOOLEAN DEFAULT TRUE
  created_at, updated_at

Shift
  id              UUID PK
  tenant_id       UUID FK → Tenant
  site_id         UUID FK → Site
  template_id     UUID FK → ShiftTemplate NULLABLE
  guard_id        UUID FK → Guard NULLABLE   -- NULL = unassigned/open shift
  shift_date      DATE
  start_time      TIMESTAMP
  end_time        TIMESTAMP
  status          ENUM(draft, published, confirmed, in_progress, completed, missed, cancelled)
  is_open         BOOLEAN DEFAULT FALSE      -- open for guards to claim
  notes           TEXT NULLABLE
  created_by      UUID FK → User
  confirmed_at    TIMESTAMP NULLABLE
  confirmed_by    UUID FK → Guard NULLABLE
  created_at, updated_at

ShiftSwapRequest
  id              UUID PK
  tenant_id       UUID FK → Tenant
  requesting_guard_id UUID FK → Guard
  target_guard_id     UUID FK → Guard
  shift_id            UUID FK → Shift
  reason              TEXT
  status          ENUM(pending, approved, rejected, cancelled)
  reviewed_by     UUID FK → User NULLABLE
  reviewed_at     TIMESTAMP NULLABLE
  created_at      TIMESTAMP
```

**Backend:**
- Shift template CRUD
- Shift creation (manual + from template)
- Bulk shift generation (weekly/monthly)
- Shift publishing (draft → published → guards notified)
- Open shift management (guards claim available shifts)
- Shift confirmation by guards
- Shift swap request workflow (guard → admin approval)
- Conflict detection (double-booking, overtime limits)
- Skill-based scheduling (match guard skills to site requirements)

**Frontend:**
- Schedule calendar view (week/month, filterable by site/guard)
- Shift template manager
- Bulk shift creation wizard
- Open shifts board (guards can view and claim)
- Shift swap request/approval interface
- **SVG stacked bar chart:** shift status breakdown (confirmed/open/missed)

#### Phase 2B: Time Clock & Attendance

**Entities:**
```
TimeClock
  id              UUID PK
  tenant_id       UUID FK → Tenant
  guard_id        UUID FK → Guard
  shift_id        UUID FK → Shift NULLABLE
  site_id         UUID FK → Site
  clock_in_time   TIMESTAMP
  clock_in_lat    DECIMAL(10,8)
  clock_in_lng    DECIMAL(11,8)
  clock_in_method ENUM(app_gps, app_qr, web_portal, manual_admin)
  clock_in_photo_url VARCHAR(500) NULLABLE
  clock_out_time  TIMESTAMP NULLABLE
  clock_out_lat   DECIMAL(10,8) NULLABLE
  clock_out_lng   DECIMAL(11,8) NULLABLE
  clock_out_method ENUM(app_gps, app_qr, web_portal, manual_admin) NULLABLE
  total_hours     DECIMAL(5,2) NULLABLE  -- calculated on clock-out
  status          ENUM(clocked_in, clocked_out, auto_clocked_out, reconciled)
  is_within_geofence_in  BOOLEAN
  is_within_geofence_out BOOLEAN NULLABLE
  notes           TEXT NULLABLE
  created_at, updated_at

AttendanceRecord
  id              UUID PK
  tenant_id       UUID FK → Tenant
  guard_id        UUID FK → Guard
  shift_id        UUID FK → Shift
  site_id         UUID FK → Site
  attendance_date DATE
  status          ENUM(present, late, absent, excused, on_leave)
  scheduled_start TIMESTAMP
  actual_start    TIMESTAMP NULLABLE
  scheduled_end   TIMESTAMP
  actual_end      TIMESTAMP NULLABLE
  late_minutes    INT DEFAULT 0
  early_leave_minutes INT DEFAULT 0
  total_worked_hours DECIMAL(5,2) DEFAULT 0
  reconciled      BOOLEAN DEFAULT FALSE
  reconciled_by   UUID FK → User NULLABLE
  notes           TEXT NULLABLE
  created_at, updated_at

BreakConfig
  id              UUID PK
  tenant_id       UUID FK → Tenant
  name            VARCHAR(100)         -- "Lunch", "Short Break"
  break_type      ENUM(paid, unpaid)
  duration_minutes INT
  auto_start      BOOLEAN DEFAULT FALSE
  auto_start_after_minutes INT NULLABLE
  can_end_early   BOOLEAN DEFAULT TRUE
  is_active       BOOLEAN DEFAULT TRUE
  created_at, updated_at

BreakLog
  id              UUID PK
  time_clock_id   UUID FK → TimeClock
  break_config_id UUID FK → BreakConfig
  start_time      TIMESTAMP
  end_time        TIMESTAMP NULLABLE
  duration_minutes INT NULLABLE
  created_at      TIMESTAMP
```

**Backend:**
- Clock in/out with GPS validation (geofence check)
- QR code-based clock in (scan site QR)
- Auto attendance record generation from time clock data
- Late/absent detection based on shift schedule
- Attendance reconciliation (admin adjusts records)
- Break management (start/end break logging)
- Bulk attendance reconciliation (threshold-based auto-approve)

**Frontend:**
- Time clock dashboard (who's on shift, who's late, who's on break)
- Attendance report (daily/weekly/monthly, by guard/site)
- Reconciliation interface (admin approves/adjusts)
- Break configuration
- **SVG gauge chart:** daily attendance rate
- **SVG heatmap:** attendance patterns by guard and day

#### Phase 2C: Passdown Logs

**Entities:**
```
PassdownLog
  id              UUID PK
  tenant_id       UUID FK → Tenant
  site_id         UUID FK → Site
  guard_id        UUID FK → Guard      -- outgoing guard
  shift_id        UUID FK → Shift NULLABLE
  incoming_guard_id UUID FK → Guard NULLABLE
  content         TEXT                 -- rich text notes
  priority        ENUM(normal, important, urgent)
  attachments     JSONB DEFAULT '[]'   -- [{url, type, name}]
  acknowledged_at TIMESTAMP NULLABLE
  acknowledged_by UUID FK → Guard NULLABLE
  created_at      TIMESTAMP
```

**Backend:**
- Passdown log CRUD
- Attach multimedia (photos, voice notes)
- Acknowledgment flow (incoming guard confirms receipt)
- Auto-prompt: when guard clocks out, prompt to create passdown

**Frontend:**
- Passdown log feed (by site, chronological)
- Create passdown form (text + attachments)
- Acknowledgment interface

**Phase 2 Deliverables:**
- ✅ Shift scheduling with templates and open shifts
- ✅ Time clock with GPS/QR validation
- ✅ Attendance tracking and reconciliation
- ✅ Break management
- ✅ Shift swap workflow
- ✅ Passdown logs for shift handover
- ✅ **Guards can clock in/out, view schedules, hand over shifts**

---

### PHASE 3: Guard Mobile App & Live Tracking (Weeks 16-21)

**Goal:** NativeScript guard mobile app, live GPS tracking with WebSocket, geofencing, site tours, panic button.

#### Phase 3A: Guard NativeScript App (Core)

**NativeScript + Angular app with:**
- Login (JWT auth, biometric for returning users)
- Home screen: current shift, clock in/out, quick actions
- Schedule view (upcoming shifts, confirm/claim)
- Clock in/out with GPS + optional selfie photo
- View post orders for current site
- View and complete tasks
- Passdown log creation
- Notifications (shift changes, new tasks, alerts)
- Offline mode (cache schedule, queue clock events, sync when online)
- Background GPS tracking (when clocked in)
- **APK distributed via App Distribution Platform**

#### Phase 3B: Live GPS Tracker

**Backend:**
- Node.js WebSocket sidecar setup
- Redis Pub/Sub for GPS event broadcasting
- GPS ping ingestion (WebSocket + HTTP fallback)
- GuardLocation bulk insert worker
- Tracking history query API (replay guard path by time range)

**Entities:**
```
GuardLocation
  id              UUID PK
  tenant_id       UUID FK → Tenant
  guard_id        UUID FK → Guard
  site_id         UUID FK → Site NULLABLE
  latitude        DECIMAL(10,8)
  longitude       DECIMAL(11,8)
  accuracy        DECIMAL(6,2)         -- GPS accuracy in meters
  speed           DECIMAL(6,2) NULLABLE -- m/s
  heading         DECIMAL(5,2) NULLABLE -- degrees
  altitude        DECIMAL(8,2) NULLABLE
  battery_level   INT NULLABLE         -- guard device battery %
  is_moving       BOOLEAN DEFAULT TRUE
  source          ENUM(websocket, http_poll, offline_sync)
  recorded_at     TIMESTAMP            -- time on device
  received_at     TIMESTAMP            -- time on server
```

**Frontend (Admin Dashboard):**
- Live map page (Google Maps / Leaflet with OpenStreetMap)
- All active guards as markers (color-coded by status)
- Guard marker shows: name, site, status, last update, battery level
- Click guard → path replay for today
- Filter by site, status, guard
- Auto-refresh via WebSocket
- Fallback polling if WebSocket disconnects

#### Phase 3C: Geo-Fencing & Idle Alerts

**Entities:**
```
GeofenceAlert
  id              UUID PK
  tenant_id       UUID FK → Tenant
  guard_id        UUID FK → Guard
  site_id         UUID FK → Site
  alert_type      ENUM(exit, entry_unauthorized, extended_absence)
  latitude        DECIMAL(10,8)
  longitude       DECIMAL(11,8)
  message         TEXT
  is_acknowledged BOOLEAN DEFAULT FALSE
  acknowledged_by UUID FK → User NULLABLE
  acknowledged_at TIMESTAMP NULLABLE
  created_at      TIMESTAMP

IdleAlert
  id              UUID PK
  tenant_id       UUID FK → Tenant
  guard_id        UUID FK → Guard
  site_id         UUID FK → Site
  idle_start_at   TIMESTAMP
  idle_duration_minutes INT
  last_known_lat  DECIMAL(10,8)
  last_known_lng  DECIMAL(11,8)
  is_acknowledged BOOLEAN DEFAULT FALSE
  acknowledged_by UUID FK → User NULLABLE
  created_at      TIMESTAMP
```

**Backend:**
- PostGIS geofence violation detection (on every GPS batch)
- Configurable idle threshold per site
- Alert generation + push notification to supervisor
- Alert acknowledgment workflow

**Frontend:**
- Geofence alerts panel on live map
- Alert history and statistics
- Geofence violation replay (show guard path crossing boundary)

#### Phase 3D: Site Tours (NFC/QR Checkpoints)

**Entities:**
```
TourCheckpoint
  id              UUID PK
  tenant_id       UUID FK → Tenant
  site_id         UUID FK → Site
  name            VARCHAR(200)         -- "Main Gate", "Parking Lot B", "Server Room"
  checkpoint_type ENUM(nfc, qr, virtual)
  qr_code_value   VARCHAR(255) NULLABLE  -- unique identifier in QR
  nfc_tag_id      VARCHAR(255) NULLABLE
  latitude        DECIMAL(10,8) NULLABLE -- for virtual checkpoints
  longitude       DECIMAL(11,8) NULLABLE
  virtual_radius  INT NULLABLE           -- meters (for virtual checkpoints)
  sequence_order  INT DEFAULT 0          -- order in tour route
  is_required     BOOLEAN DEFAULT TRUE
  is_active       BOOLEAN DEFAULT TRUE
  created_at, updated_at

TourSession
  id              UUID PK
  tenant_id       UUID FK → Tenant
  guard_id        UUID FK → Guard
  site_id         UUID FK → Site
  shift_id        UUID FK → Shift NULLABLE
  started_at      TIMESTAMP
  completed_at    TIMESTAMP NULLABLE
  status          ENUM(in_progress, completed, incomplete, missed)
  total_checkpoints INT
  scanned_checkpoints INT DEFAULT 0
  created_at      TIMESTAMP

TourCheckpointScan
  id              UUID PK
  session_id      UUID FK → TourSession
  checkpoint_id   UUID FK → TourCheckpoint
  scanned_at      TIMESTAMP
  scan_method     ENUM(nfc, qr, virtual_gps)
  latitude        DECIMAL(10,8)
  longitude       DECIMAL(11,8)
  notes           TEXT NULLABLE
  photo_url       VARCHAR(500) NULLABLE
  created_at      TIMESTAMP
```

**Guard App features:**
- Scan NFC tag or QR code at checkpoints
- Virtual checkpoint: auto-scan when GPS enters radius
- Tour progress indicator (X of Y checkpoints)
- Photo capture at checkpoints
- Missed checkpoint alerts

**Backend:**
- Tour checkpoint CRUD per site
- Tour session management
- Missed checkpoint detection + notification
- Tour compliance reporting

**Frontend:**
- Checkpoint management per site (map-based placement)
- Tour reports (completion rates, missed checkpoints)
- **SVG bar chart:** tour completion by guard

#### Phase 3E: Panic Button & Emergency

**Entities:**
```
PanicAlert
  id              UUID PK
  tenant_id       UUID FK → Tenant
  guard_id        UUID FK → Guard
  site_id         UUID FK → Site NULLABLE
  latitude        DECIMAL(10,8)
  longitude       DECIMAL(11,8)
  message         TEXT NULLABLE
  audio_url       VARCHAR(500) NULLABLE   -- voice recording
  status          ENUM(triggered, acknowledged, responding, resolved, false_alarm)
  acknowledged_by UUID FK → User NULLABLE
  acknowledged_at TIMESTAMP NULLABLE
  resolved_by     UUID FK → User NULLABLE
  resolved_at     TIMESTAMP NULLABLE
  resolution_notes TEXT NULLABLE
  created_at      TIMESTAMP
```

**Guard App:**
- One-tap panic button (always visible when clocked in)
- Sends GPS location + optional voice recording
- Vibration/sound confirmation that alert was sent
- Continues sending GPS every 5 seconds during active panic

**Backend:**
- Panic alert creation → immediate WebSocket push to all admins/supervisors/dispatchers
- SMS alert to configured emergency contacts (Termii)
- Email alert via ZeptoMail
- Status workflow: triggered → acknowledged → responding → resolved

**Frontend:**
- Panic alert banner on all admin pages (when active)
- Map zooms to guard location
- Response workflow interface

**Phase 3 Deliverables:**
- ✅ Guard NativeScript app with offline support
- ✅ Real-time live GPS tracking with WebSocket
- ✅ Geofence violation detection and alerts
- ✅ Idle detection
- ✅ NFC/QR site tour system
- ✅ One-tap panic button with voice recording
- ✅ **Guards are fully equipped in the field**

---

### PHASE 4: Reporting & Dispatch (Weeks 22-26)

**Goal:** Comprehensive reporting system, incident management, dispatcher console, task management.

#### Phase 4A: Online Reporting System

**Entities:**
```
DailyActivityReport
  id              UUID PK
  tenant_id       UUID FK → Tenant
  guard_id        UUID FK → Guard
  site_id         UUID FK → Site
  shift_id        UUID FK → Shift NULLABLE
  report_date     DATE
  content         TEXT                 -- structured report content
  weather         VARCHAR(50) NULLABLE
  status          ENUM(draft, submitted, reviewed, approved)
  submitted_at    TIMESTAMP NULLABLE
  reviewed_by     UUID FK → User NULLABLE
  reviewed_at     TIMESTAMP NULLABLE
  attachments     JSONB DEFAULT '[]'
  created_at, updated_at

CustomReportTemplate
  id              UUID PK
  tenant_id       UUID FK → Tenant
  name            VARCHAR(200)
  description     TEXT NULLABLE
  fields          JSONB                -- dynamic form field definitions
  is_active       BOOLEAN DEFAULT TRUE
  created_by      UUID FK → User
  created_at, updated_at

CustomReportSubmission
  id              UUID PK
  template_id     UUID FK → CustomReportTemplate
  tenant_id       UUID FK → Tenant
  guard_id        UUID FK → Guard
  site_id         UUID FK → Site
  data            JSONB                -- submitted form data
  attachments     JSONB DEFAULT '[]'
  status          ENUM(submitted, reviewed, approved)
  submitted_at    TIMESTAMP
  reviewed_by     UUID FK → User NULLABLE
  created_at      TIMESTAMP

WatchModeLog
  id              UUID PK
  tenant_id       UUID FK → Tenant
  guard_id        UUID FK → Guard
  site_id         UUID FK → Site
  media_type      ENUM(photo, video)
  media_url       VARCHAR(500)
  caption         TEXT NULLABLE
  latitude        DECIMAL(10,8) NULLABLE
  longitude       DECIMAL(11,8) NULLABLE
  recorded_at     TIMESTAMP
  created_at      TIMESTAMP
```

**Guard App features:**
- Submit DAR from mobile (text + photos + video)
- Autosave drafts while on patrol
- Watch mode: take photos/videos that auto-upload to admin dashboard
- Custom report forms (dynamic fields from templates)

**Backend:**
- DAR CRUD with review/approval workflow
- Custom report template builder (admin defines fields)
- Watch mode log ingestion
- Report auto-sharing to clients (configurable per site)
- Report export (PDF)

**Frontend:**
- Reports dashboard (all report types)
- DAR list with filters (date, guard, site, status)
- DAR detail with photo/video attachments
- Custom report template builder (drag-and-drop field config)
- Watch mode feed (chronological media feed by site)
- Report sharing settings per client

#### Phase 4B: Incident Reporting & Escalation

**Entities:**
```
IncidentReport
  id              UUID PK
  tenant_id       UUID FK → Tenant
  guard_id        UUID FK → Guard
  site_id         UUID FK → Site
  incident_type   ENUM(theft, trespass, vandalism, assault, fire, medical, suspicious_activity, equipment_failure, other)
  severity        ENUM(critical, high, medium, low)
  title           VARCHAR(300)
  description     TEXT
  location_detail VARCHAR(200) NULLABLE  -- "Near main gate", "Parking lot B"
  latitude        DECIMAL(10,8) NULLABLE
  longitude       DECIMAL(11,8) NULLABLE
  occurred_at     TIMESTAMP
  reported_at     TIMESTAMP
  attachments     JSONB DEFAULT '[]'
  status          ENUM(reported, acknowledged, investigating, escalated, resolved, closed)
  assigned_to     UUID FK → User NULLABLE
  resolution      TEXT NULLABLE
  resolved_at     TIMESTAMP NULLABLE
  resolved_by     UUID FK → User NULLABLE
  client_notified BOOLEAN DEFAULT FALSE
  created_at, updated_at

IncidentEscalation
  id              UUID PK
  incident_id     UUID FK → IncidentReport
  escalated_to    UUID FK → User
  escalated_by    UUID FK → User
  reason          TEXT
  escalated_at    TIMESTAMP
```

**Backend:**
- Incident report submission (guard app + web)
- Severity-based auto-escalation workflow
- Incident assignment and status tracking
- Client notification (optional, configurable)
- Incident statistics and trends

**Frontend:**
- Incident list with severity color coding
- Incident detail with timeline (status changes, escalations)
- Incident statistics dashboard
- **SVG pie chart:** incidents by type
- **SVG line chart:** incident trends over time

#### Phase 4C: Dispatcher Console

**Entities:**
```
DispatchCall
  id              UUID PK
  tenant_id       UUID FK → Tenant
  caller_name     VARCHAR(200)
  caller_phone    VARCHAR(50) NULLABLE
  client_id       UUID FK → Client NULLABLE
  site_id         UUID FK → Site NULLABLE
  call_type       ENUM(emergency, routine, complaint, information, panic_response)
  priority        ENUM(critical, high, medium, low)
  description     TEXT
  status          ENUM(received, dispatched, in_progress, resolved, cancelled)
  received_at     TIMESTAMP
  dispatched_at   TIMESTAMP NULLABLE
  resolved_at     TIMESTAMP NULLABLE
  resolution      TEXT NULLABLE
  created_by      UUID FK → User        -- dispatcher
  created_at, updated_at

DispatchAssignment
  id              UUID PK
  dispatch_id     UUID FK → DispatchCall
  guard_id        UUID FK → Guard
  assigned_at     TIMESTAMP
  acknowledged_at TIMESTAMP NULLABLE
  arrived_at      TIMESTAMP NULLABLE
  completed_at    TIMESTAMP NULLABLE
  status          ENUM(assigned, acknowledged, en_route, on_scene, completed)
  notes           TEXT NULLABLE
  created_at      TIMESTAMP
```

**Backend:**
- Dispatch call logging
- Nearest available guard suggestion (based on GPS proximity)
- Guard assignment with real-time status
- Dispatch summary generation

**Frontend:**
- Dispatcher console (full-screen, optimized for control room)
- Incoming calls queue
- Live map with available guards
- One-click assign nearest guard
- Dispatch history and reports
- **SVG bar chart:** dispatch response times

#### Phase 4D: Task Management

**Entities:**
```
Task
  id              UUID PK
  tenant_id       UUID FK → Tenant
  site_id         UUID FK → Site
  assigned_to     UUID FK → Guard
  assigned_by     UUID FK → User
  title           VARCHAR(300)
  description     TEXT
  priority        ENUM(critical, high, medium, low)
  due_date        TIMESTAMP NULLABLE
  status          ENUM(pending, in_progress, completed, cancelled, overdue)
  completed_at    TIMESTAMP NULLABLE
  completion_notes TEXT NULLABLE
  attachments     JSONB DEFAULT '[]'
  created_at, updated_at
```

**Backend:**
- Task CRUD
- Task assignment to guards
- Status tracking with notifications
- Overdue task detection

**Frontend:**
- Task board (Kanban or list view)
- Task assignment interface
- Guard app: task list, mark complete with notes/photos

**Phase 4 Deliverables:**
- ✅ Daily activity reports with multimedia
- ✅ Custom report templates
- ✅ Watch mode (photo/video feeds)
- ✅ Incident reporting with escalation
- ✅ Dispatcher console for control room
- ✅ Task management
- ✅ **Full operational reporting and emergency response**

---

### PHASE 5: Finance & Billing (Weeks 27-30)

**Goal:** Client invoicing, payroll generation, overtime management.

#### Phase 5A: Invoice & Estimate Management

**Entities:**
```
Invoice
  id              UUID PK
  tenant_id       UUID FK → Tenant
  client_id       UUID FK → Client
  invoice_number  VARCHAR(50)          -- auto-generated
  type            ENUM(invoice, estimate)
  status          ENUM(draft, sent, viewed, partially_paid, paid, overdue, cancelled)
  issue_date      DATE
  due_date        DATE
  subtotal        DECIMAL(12,2)
  tax_rate        DECIMAL(5,2) DEFAULT 7.5  -- Nigeria VAT
  tax_amount      DECIMAL(12,2)
  total           DECIMAL(12,2)
  amount_paid     DECIMAL(12,2) DEFAULT 0
  balance_due     DECIMAL(12,2)
  currency        VARCHAR(3) DEFAULT 'NGN'
  notes           TEXT NULLABLE
  payment_terms   TEXT NULLABLE
  created_by      UUID FK → User
  sent_at         TIMESTAMP NULLABLE
  created_at, updated_at

InvoiceItem
  id              UUID PK
  invoice_id      UUID FK → Invoice
  description     VARCHAR(500)         -- "Security Guard Services - Lekki Estate - March 2026"
  quantity        DECIMAL(10,2)        -- hours or days
  unit_price      DECIMAL(10,2)        -- per hour or per day rate
  amount          DECIMAL(12,2)
  is_taxable      BOOLEAN DEFAULT TRUE
  created_at      TIMESTAMP

InvoicePayment
  id              UUID PK
  invoice_id      UUID FK → Invoice
  amount          DECIMAL(12,2)
  payment_method  ENUM(cash, bank_transfer, pos_card, cheque)
  reference       VARCHAR(100) NULLABLE
  proof_url       VARCHAR(500) NULLABLE
  received_by     UUID FK → User
  payment_date    TIMESTAMP
  notes           TEXT NULLABLE
  created_at      TIMESTAMP
```

**Backend:**
- Invoice/estimate CRUD
- Auto-generate invoice from time clock data (hours × billing rate)
- Invoice prominently displays tenant bank account details
- Payment recording (manual: cash/bank transfer/POS/cheque)
- Partial payment tracking
- Invoice PDF generation (branded, with bank account details)
- Email invoice via ZeptoMail
- Estimate → invoice conversion on acceptance
- Overdue invoice detection + reminders

**Frontend:**
- Invoice list with status filters
- Create invoice (manual or auto-generate from timesheet)
- Invoice detail with payment history
- Record payment form
- Invoice PDF preview
- Estimate management
- **SVG bar chart:** revenue by client
- **SVG line chart:** monthly revenue trend

#### Phase 5B: Payroll & Pay Rate Management

**Entities:**
```
PayrollPeriod
  id              UUID PK
  tenant_id       UUID FK → Tenant
  period_start    DATE
  period_end      DATE
  status          ENUM(draft, calculating, review, approved, paid)
  total_gross     DECIMAL(12,2) DEFAULT 0
  total_deductions DECIMAL(12,2) DEFAULT 0
  total_net       DECIMAL(12,2) DEFAULT 0
  approved_by     UUID FK → User NULLABLE
  approved_at     TIMESTAMP NULLABLE
  created_at, updated_at

PayrollItem
  id              UUID PK
  payroll_period_id UUID FK → PayrollPeriod
  guard_id        UUID FK → Guard
  regular_hours   DECIMAL(6,2) DEFAULT 0
  overtime_hours  DECIMAL(6,2) DEFAULT 0
  holiday_hours   DECIMAL(6,2) DEFAULT 0
  regular_rate    DECIMAL(10,2)
  overtime_rate   DECIMAL(10,2)
  holiday_rate    DECIMAL(10,2)
  gross_pay       DECIMAL(10,2)
  deductions      JSONB DEFAULT '{}'    -- {paye, pension, nhf, other}
  net_pay         DECIMAL(10,2)
  status          ENUM(pending, approved, paid)
  notes           TEXT NULLABLE
  created_at, updated_at

PayRateMultiplier
  id              UUID PK
  tenant_id       UUID FK → Tenant
  name            VARCHAR(100)         -- "Overtime 1.5x", "Holiday 2x", "Night Shift 1.25x"
  multiplier      DECIMAL(4,2)         -- 1.5, 2.0, 1.25
  applies_to      ENUM(overtime, holiday, night, weekend)
  is_active       BOOLEAN DEFAULT TRUE
  created_at, updated_at

Payslip
  id              UUID PK
  payroll_item_id UUID FK → PayrollItem
  guard_id        UUID FK → Guard
  period_start    DATE
  period_end      DATE
  gross_pay       DECIMAL(10,2)
  deductions_breakdown JSONB
  net_pay         DECIMAL(10,2)
  pdf_url         VARCHAR(500) NULLABLE
  emailed_at      TIMESTAMP NULLABLE
  created_at      TIMESTAMP
```

**Backend:**
- Payroll period CRUD
- Auto-calculate payroll from time clock records
- Overtime detection and multiplier application
- Nigeria PAYE tax calculation (CRA, brackets, pension, NHF)
- Payroll review/approval workflow (draft → calculate → review → approve)
- Payslip PDF generation
- Payslip email via ZeptoMail
- Payroll export (CSV, PDF)

**Frontend:**
- Payroll dashboard
- Generate payroll wizard (select period, review)
- Payroll detail (per-guard breakdown)
- Pay rate multiplier configuration
- Payslip viewer
- **SVG bar chart:** payroll by department/site

**Phase 5 Deliverables:**
- ✅ Client invoicing with auto-generation from timesheets
- ✅ Manual payment confirmation (cash/bank transfer/POS/cheque)
- ✅ VAT-compliant PDF invoices with bank account details
- ✅ Payroll with overtime and Nigeria PAYE
- ✅ Payslip generation and email
- ✅ **Security company can bill clients and pay guards**

---

### PHASE 6: Client Experience & Communication (Weeks 31-35)

**Goal:** Client web portal, client mobile app, messenger/chat.

#### Phase 6A: Client Web Portal

**Entities:**
```
ClientUser
  id              UUID PK
  tenant_id       UUID FK → Tenant
  client_id       UUID FK → Client
  user_id         UUID FK → User       -- links to auth user with 'client' role
  can_view_reports    BOOLEAN DEFAULT TRUE
  can_view_tracking   BOOLEAN DEFAULT TRUE
  can_view_invoices   BOOLEAN DEFAULT TRUE
  can_view_incidents  BOOLEAN DEFAULT TRUE
  can_message         BOOLEAN DEFAULT TRUE
  created_at, updated_at
```

**Frontend (separate route group, client role):**
- Client dashboard: guards on duty at their sites, incidents today, recent reports
- Live guard tracking for their sites (map view)
- Reports viewer (DAR, incidents, site tours, custom reports)
- Invoice viewer with payment history
- Guard check-in/out history
- Attendance summary for their sites
- Post order viewer

#### Phase 6B: Client Mobile App (NativeScript)

**NativeScript + Angular app with:**
- Login (JWT auth, role-restricted to client data)
- Dashboard: guards currently on duty, incidents today
- Live map: guard positions at their sites (real-time via WebSocket)
- Reports feed (DAR, incidents, site tour reports)
- Invoice list with status
- Notifications (incidents, guard clock-ins, new reports)
- Messaging with security company
- **APK distributed via App Distribution Platform**

#### Phase 6C: Messenger / Chat

**Entities:**
```
ChatConversation
  id              UUID PK
  tenant_id       UUID FK → Tenant
  type            ENUM(direct, group, site_channel)
  name            VARCHAR(200) NULLABLE  -- for groups/channels
  site_id         UUID FK → Site NULLABLE -- for site channels
  created_by      UUID FK → User
  last_message_at TIMESTAMP NULLABLE
  created_at, updated_at

ChatParticipant
  id              UUID PK
  conversation_id UUID FK → ChatConversation
  user_id         UUID FK → User
  role            ENUM(admin, member)
  joined_at       TIMESTAMP
  left_at         TIMESTAMP NULLABLE
  last_read_at    TIMESTAMP NULLABLE

ChatMessage
  id              UUID PK
  conversation_id UUID FK → ChatConversation
  sender_id       UUID FK → User
  content         TEXT
  message_type    ENUM(text, image, video, voice, file, location)
  media_url       VARCHAR(500) NULLABLE
  latitude        DECIMAL(10,8) NULLABLE
  longitude       DECIMAL(11,8) NULLABLE
  is_deleted      BOOLEAN DEFAULT FALSE
  created_at      TIMESTAMP

Notification
  id              UUID PK
  tenant_id       UUID FK → Tenant
  user_id         UUID FK → User
  type            ENUM(shift_assigned, shift_change, clock_reminder, incident, panic, dispatch, report, message, invoice, system)
  title           VARCHAR(300)
  body            TEXT
  data            JSONB DEFAULT '{}'    -- contextual data (IDs, links)
  channel         ENUM(push, sms, email, in_app)
  is_read         BOOLEAN DEFAULT FALSE
  read_at         TIMESTAMP NULLABLE
  sent_at         TIMESTAMP NULLABLE
  created_at      TIMESTAMP

DeviceToken
  id              UUID PK
  user_id         UUID FK → User
  token           VARCHAR(500)
  platform        ENUM(android, ios, web)
  is_active       BOOLEAN DEFAULT TRUE
  created_at, updated_at
```

**Backend:**
- Chat via WebSocket (same Node.js sidecar as GPS)
- Direct messages, group chats, site-specific channels
- Multimedia attachments (photos, voice, files, location sharing)
- Guards can only chat when checked in to a site (focus enforcement)
- Push notifications via Firebase Cloud Messaging
- SMS notifications via Termii (configurable per notification type)
- Email notifications via ZeptoMail

**Frontend:**
- Chat interface (web dashboard + mobile apps)
- Conversation list with unread counts
- Site channel auto-join (guards join site channel on check-in)
- Notification center (in-app + push)

**Phase 6 Deliverables:**
- ✅ Client web portal with reports, tracking, invoices
- ✅ Client NativeScript mobile app
- ✅ Real-time messenger (WebSocket)
- ✅ Multi-channel notifications (push, SMS, email, in-app)
- ✅ **Clients have full visibility and communication**

---

### PHASE 7: Operations & Extended Apps (Weeks 36-42)

**Goal:** Vehicle patrol, visitor management, parking manager, supervisor app, dispatcher app, desktop app.

#### Phase 7A: Vehicle Patrol

**Entities:**
```
PatrolVehicle
  id              UUID PK
  tenant_id       UUID FK → Tenant
  vehicle_name    VARCHAR(100)
  plate_number    VARCHAR(20)
  vehicle_type    ENUM(car, suv, motorcycle, van)
  status          ENUM(active, maintenance, retired)
  assigned_guard_id UUID FK → Guard NULLABLE
  notes           TEXT NULLABLE
  created_at, updated_at

VehiclePatrolRoute
  id              UUID PK
  tenant_id       UUID FK → Tenant
  name            VARCHAR(200)
  description     TEXT NULLABLE
  sites           JSONB                -- array of site_ids in patrol order
  expected_hits_per_day INT DEFAULT 1
  reset_time      TIME DEFAULT '00:00'  -- daily hit counter reset
  is_active       BOOLEAN DEFAULT TRUE
  created_at, updated_at

VehiclePatrolHit
  id              UUID PK
  tenant_id       UUID FK → Tenant
  route_id        UUID FK → VehiclePatrolRoute
  vehicle_id      UUID FK → PatrolVehicle
  guard_id        UUID FK → Guard
  site_id         UUID FK → Site
  hit_number      INT                  -- which hit today
  latitude        DECIMAL(10,8)
  longitude       DECIMAL(11,8)
  notes           TEXT NULLABLE
  photo_url       VARCHAR(500) NULLABLE
  recorded_at     TIMESTAMP
  created_at      TIMESTAMP
```

**Backend:**
- Vehicle CRUD
- Patrol route definition (sequence of sites)
- Hit tracking and completion monitoring
- Missed patrol alerts
- Vehicle patrol reports

**Frontend:**
- Vehicle management
- Route builder (map-based)
- Patrol activity dashboard
- Vehicle patrol reports

#### Phase 7B: Visitor Management

**Entities:**
```
Visitor
  id              UUID PK
  tenant_id       UUID FK → Tenant
  site_id         UUID FK → Site
  first_name      VARCHAR(100)
  last_name       VARCHAR(100)
  phone           VARCHAR(50) NULLABLE
  email           VARCHAR(255) NULLABLE
  company         VARCHAR(200) NULLABLE
  purpose         VARCHAR(300)
  host_name       VARCHAR(200) NULLABLE
  id_type         ENUM(national_id, drivers_license, passport, company_id, other) NULLABLE
  id_number       VARCHAR(50) NULLABLE
  photo_url       VARCHAR(500) NULLABLE
  vehicle_plate   VARCHAR(20) NULLABLE
  status          ENUM(checked_in, checked_out)
  check_in_at     TIMESTAMP
  check_out_at    TIMESTAMP NULLABLE
  checked_in_by   UUID FK → Guard
  checked_out_by  UUID FK → Guard NULLABLE
  notes           TEXT NULLABLE
  created_at      TIMESTAMP

VisitorVehicle
  id              UUID PK
  visitor_id      UUID FK → Visitor
  plate_number    VARCHAR(20)
  make            VARCHAR(50) NULLABLE
  model           VARCHAR(50) NULLABLE
  color           VARCHAR(30) NULLABLE
  created_at      TIMESTAMP
```

**Backend:**
- Visitor check-in/out (guard app + web)
- Visitor search (returning visitors auto-fill)
- Visitor log per site
- Visitor reports (shared with clients)

**Frontend:**
- Visitor log (by site, date)
- Quick check-in form (guard app optimized)
- Visitor profile (for returning visitors)
- Visitor reports

#### Phase 7C: Parking Manager

**Entities:**
```
ParkingArea
  id              UUID PK
  tenant_id       UUID FK → Tenant
  site_id         UUID FK → Site
  name            VARCHAR(200)
  total_spaces    INT
  status          ENUM(active, inactive)
  created_at, updated_at

ParkingLot
  id              UUID PK
  parking_area_id UUID FK → ParkingArea
  name            VARCHAR(100)         -- "Lot A", "VIP Section"
  capacity        INT
  lot_type        ENUM(regular, vip, reserved, disabled)
  created_at, updated_at

ParkingVehicle
  id              UUID PK
  tenant_id       UUID FK → Tenant
  site_id         UUID FK → Site
  parking_lot_id  UUID FK → ParkingLot NULLABLE
  plate_number    VARCHAR(20)
  make            VARCHAR(50) NULLABLE
  model           VARCHAR(50) NULLABLE
  color           VARCHAR(30) NULLABLE
  owner_name      VARCHAR(200) NULLABLE
  owner_phone     VARCHAR(50) NULLABLE
  owner_type      ENUM(resident, visitor, staff, unknown)
  status          ENUM(parked, departed, violation)
  entry_time      TIMESTAMP
  exit_time       TIMESTAMP NULLABLE
  logged_by       UUID FK → Guard
  notes           TEXT NULLABLE
  created_at      TIMESTAMP

ParkingIncident
  id              UUID PK
  tenant_id       UUID FK → Tenant
  site_id         UUID FK → Site
  vehicle_id      UUID FK → ParkingVehicle NULLABLE
  incident_type_id UUID FK → ParkingIncidentType
  description     TEXT
  attachments     JSONB DEFAULT '[]'
  reported_by     UUID FK → Guard
  status          ENUM(reported, resolved)
  created_at, updated_at

ParkingIncidentType
  id              UUID PK
  tenant_id       UUID FK → Tenant
  name            VARCHAR(100)         -- "Unauthorized Parking", "Damage", "Towing Required"
  form_fields     JSONB                -- custom form fields
  is_active       BOOLEAN DEFAULT TRUE
  created_at, updated_at
```

**Backend:**
- Parking area/lot CRUD
- Vehicle logging (entry/exit)
- Parking violation reporting
- Incident types with custom forms
- Parking reports (client-visible)

**Frontend:**
- Parking area management
- Vehicle log
- Incident reporting
- Parking occupancy dashboard

#### Phase 7D: Supervisor Mobile App (NativeScript)

**NativeScript + Angular app with:**
- Dashboard: guards on duty, late, absent (for supervised sites)
- Live map: guard locations at assigned sites
- Attendance management: view/approve clock-ins
- Incident management: review, escalate
- Report review and approval
- Dispatch alerts reception
- Guard performance overview
- **APK distributed via App Distribution Platform**

#### Phase 7E: Dispatcher Mobile App (NativeScript)

**NativeScript + Angular app (tablet-optimized):**
- Incoming call log
- Live map: all guards with availability status
- One-tap assign nearest guard
- Panic alert real-time feed
- Dispatch status tracking
- Dispatch history and reports
- **APK distributed via App Distribution Platform**

#### Phase 7F: Desktop Electron App

- Electron shell wrapping Angular web app
- Auto-update from App Distribution Platform
- Windows (.exe), macOS (.dmg), Linux (.AppImage)
- Native notifications, system tray
- Persistent login, startup launch option
- **Binaries uploaded to App Distribution Platform**

**Phase 7 Deliverables:**
- ✅ Vehicle patrol with route management
- ✅ Visitor management with check-in/out
- ✅ Parking manager with incident types
- ✅ Supervisor NativeScript app
- ✅ Dispatcher NativeScript app (tablet)
- ✅ Desktop Electron app
- ✅ **All apps distributed via self-hosted platform**

---

### PHASE 8: Advanced Features (Weeks 43+)

| Sub-phase | Features | Module Tier |
|-----------|----------|-------------|
| 8A | Analytics & BI (response times, compliance rates, guard performance index, custom reports with SVG charts) | Business+ / Enterprise |
| 8B | AI-based patrol anomaly detection | Enterprise |
| 8C | Guard license management (upload, expiry alerts, compliance tracking) | Professional+ |
| 8D | Advanced security (2FA, audit logging, IP whitelisting) | Business+ |
| 8E | Multi-language (EN, Pidgin, Yoruba, Hausa) | Enterprise |
| 8F | Offline-first PWA + mobile (IndexedDB/SQLite sync queues) | All |
| 8G | WhatsApp integration (Termii/360dialog) for client notifications | Business+ |
| 8H | IoT integration (CCTV, biometrics, smart sensors) | Enterprise |
| 8I | White-label branding (custom domain, logo, colors) | Enterprise |
| 8J | Multi-property support (companies managing multiple branches) | Enterprise |

---

## 8. Project Structure

```
guard51/
├── apps/
│   ├── api/                          # Slim 4 PHP Backend
│   │   ├── src/
│   │   │   ├── Entity/
│   │   │   ├── Repository/
│   │   │   ├── Module/
│   │   │   │   ├── Auth/
│   │   │   │   ├── Tenant/
│   │   │   │   ├── Subscription/
│   │   │   │   ├── Feature/
│   │   │   │   ├── Usage/
│   │   │   │   ├── Onboarding/
│   │   │   │   ├── AppDistribution/
│   │   │   │   ├── Guard/
│   │   │   │   ├── Client/
│   │   │   │   ├── Site/
│   │   │   │   ├── PostOrder/
│   │   │   │   ├── Scheduling/
│   │   │   │   ├── TimeClock/
│   │   │   │   ├── Attendance/
│   │   │   │   ├── Passdown/
│   │   │   │   ├── Tracker/
│   │   │   │   ├── Geofence/
│   │   │   │   ├── SiteTour/
│   │   │   │   ├── Panic/
│   │   │   │   ├── Report/
│   │   │   │   ├── Incident/
│   │   │   │   ├── Dispatcher/
│   │   │   │   ├── Task/
│   │   │   │   ├── Invoice/
│   │   │   │   ├── Payroll/
│   │   │   │   ├── VehiclePatrol/
│   │   │   │   ├── Visitor/
│   │   │   │   ├── Parking/
│   │   │   │   ├── Chat/
│   │   │   │   ├── Notification/
│   │   │   │   └── Dashboard/
│   │   │   ├── Middleware/
│   │   │   ├── DTO/
│   │   │   ├── Service/
│   │   │   │   ├── ZeptoMailService.php
│   │   │   │   ├── TermiiService.php
│   │   │   │   ├── PaystackService.php
│   │   │   │   ├── PdfService.php
│   │   │   │   ├── FileStorageService.php
│   │   │   │   ├── GpsService.php
│   │   │   │   ├── GeofenceService.php
│   │   │   │   └── QueueService.php
│   │   │   ├── Event/
│   │   │   ├── EventListener/
│   │   │   ├── Exception/
│   │   │   └── Helper/
│   │   ├── migrations/
│   │   └── tests/
│   │
│   ├── realtime/                     # Node.js WebSocket Sidecar
│   │   ├── src/
│   │   │   ├── server.ts             # Socket.io server
│   │   │   ├── handlers/
│   │   │   │   ├── gps.handler.ts    # GPS ping ingestion
│   │   │   │   ├── chat.handler.ts   # Chat messages
│   │   │   │   ├── dispatch.handler.ts # Dispatch alerts
│   │   │   │   └── panic.handler.ts  # Panic alert broadcasting
│   │   │   ├── services/
│   │   │   │   ├── redis.service.ts
│   │   │   │   └── auth.service.ts   # JWT validation
│   │   │   └── types/
│   │   └── package.json
│   │
│   ├── web/                          # Angular 21
│   │   └── src/app/
│   │       ├── core/
│   │       ├── shared/
│   │       ├── features/
│   │       │   ├── auth/
│   │       │   ├── super-admin/
│   │       │   │   ├── dashboard/
│   │       │   │   ├── tenants/
│   │       │   │   ├── subscriptions/
│   │       │   │   ├── payment-confirmations/
│   │       │   │   ├── features/
│   │       │   │   ├── apps/
│   │       │   │   ├── usage/
│   │       │   │   └── settings/
│   │       │   ├── dashboard/
│   │       │   ├── guards/
│   │       │   ├── clients/
│   │       │   ├── sites/
│   │       │   ├── scheduling/
│   │       │   ├── attendance/
│   │       │   ├── tracker/
│   │       │   ├── site-tours/
│   │       │   ├── reports/
│   │       │   ├── incidents/
│   │       │   ├── dispatcher/
│   │       │   ├── tasks/
│   │       │   ├── finance/
│   │       │   ├── payroll/
│   │       │   ├── vehicle-patrol/
│   │       │   ├── visitors/
│   │       │   ├── parking/
│   │       │   ├── messenger/
│   │       │   ├── notifications/
│   │       │   ├── settings/
│   │       │   │   └── apps-downloads/
│   │       │   ├── guard-portal/      # Guard web portal
│   │       │   └── client-portal/     # Client web portal
│   │       └── app.routes.ts
│   │
│   ├── desktop/                      # Electron shell
│   │   ├── main.ts
│   │   ├── preload.ts
│   │   ├── auto-updater.ts
│   │   ├── electron-builder.yml
│   │   └── package.json
│   │
│   ├── mobile-guard/                 # NativeScript + Angular
│   ├── mobile-client/
│   ├── mobile-supervisor/
│   └── mobile-dispatcher/
│
├── libs/
│   ├── shared-types/                 # Interfaces, enums, DTOs
│   ├── shared-services/              # API, auth, feature, map services
│   ├── shared-state/                 # Signal-based stores
│   ├── shared-ui/                    # SVG chart components + shared UI
│   │   └── charts/
│   │       ├── svg-line-chart/
│   │       ├── svg-bar-chart/
│   │       ├── svg-stacked-bar-chart/
│   │       ├── svg-pie-chart/
│   │       ├── svg-donut-chart/
│   │       ├── svg-gauge-chart/
│   │       ├── svg-sparkline/
│   │       ├── svg-heatmap/
│   │       ├── svg-area-chart/
│   │       └── chart-utils/
│   └── shared-utils/
│
├── docker-compose.yml
├── nx.json
└── package.json
```

---

## 9. Email Templates (ZeptoMail)

All transactional emails sent via ZeptoMail API. Templates stored in code, rendered with tenant branding.

| Email | Trigger | Recipient |
|-------|---------|-----------|
| Welcome | Tenant onboarding complete | Company admin |
| Staff Invitation | Admin invites guard/supervisor | Staff email |
| Password Reset | Forgot password request | User |
| Client Portal Invitation | Admin invites client user | Client email |
| Shift Assigned | Guard assigned to shift | Guard |
| Shift Change | Schedule modified | Affected guard |
| Shift Reminder | 1 hour before shift start | Guard |
| Missed Clock-In Alert | Guard didn't clock in | Supervisor |
| Incident Report | New incident created | Supervisor + Client (if configured) |
| Panic Alert | Guard triggers panic button | All admins + supervisors |
| Daily Activity Report | DAR submitted | Supervisor + Client (if configured) |
| Invoice Created | Invoice generated | Client |
| Payment Reminder | Overdue invoice | Client |
| Payslip | Payroll approved | Guard |
| Guard License Expiring | 30/7 days before expiry | Guard + Admin |
| Subscription Activated | Paystack/bank transfer confirmed | Company admin |
| Subscription Expiring | 7 days before period end | Company admin |
| Payment Failed | Paystack subscription payment failed | Company admin |
| Bank Transfer Pending | Manual payment awaiting confirmation | Company admin |
| App Update Available | New app version uploaded | Company admin |

---

## 10. Timeline Summary

| Phase | What You Get | Apps | Weeks |
|-------|-------------|------|-------|
| **0** | SaaS platform: tenancy, dual billing, features, onboarding, app distribution, super admin, SVG charts | Web | 6 |
| **1** | Core: guards, sites, clients, post orders, guard web portal, dashboard | Web | 5 |
| **2** | Scheduling: shifts, time clock, attendance, passdown logs | Web | 4 |
| **3** | Tracking: guard mobile app, live GPS, geofencing, site tours, panic button | Web + Guard App | 6 |
| **4** | Reporting: DAR, incidents, dispatcher console, tasks | Web + Guard App | 5 |
| **5** | Finance: invoicing, payroll, pay rates | Web | 4 |
| **6** | Client experience: client portal, client app, messenger, notifications | Web + Client App | 5 |
| **7** | Operations: vehicle patrol, visitors, parking, supervisor app, dispatcher app, desktop | Web + 2 NS Apps + Electron | 7 |
| **8** | Analytics, AI, multi-language, offline, WhatsApp, IoT, white-label | All apps | Ongoing |

**Deployable SaaS platform (Phase 0): ~6 weeks**  
**Usable security management system (Phases 0-2): ~15 weeks**  
**Full field operations with mobile app (Phases 0-3): ~21 weeks**  
**Complete platform with all apps: ~42 weeks (~10.5 months)**

---

## 11. Implementation Rules

1. **One sub-phase at a time.** Don't start 0C until 0B is complete.
2. **Backend first, then frontend.** Swagger-test APIs before building UI.
3. **Web first, then mobile.** NativeScript apps after web equivalent works.
4. **Seed data every phase.** Realistic demo data for every new module.
5. **No speculative code.** Only build what the current phase requires.
6. **Feature gates from day one.** Every non-core module is gated.
7. **Git branch per sub-phase.** `phase-0f/app-distribution`, `phase-3b/live-tracker`.
8. **Deploy after each phase.** Staging updated continuously.
9. **SVG charts built once in Phase 0H.** Reused across all dashboards in later phases.
10. **All app builds uploaded to App Distribution Platform.** No app store submissions.
11. **PostGIS from day one.** Geospatial queries are core to the product.
12. **WebSocket sidecar tested in Phase 0A.** Used heavily from Phase 3 onward.

---

## 12. Next Step

When ready, we begin **Phase 0A: Project Setup & Dev Environment**.
