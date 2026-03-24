# Guard51 GOV Edition — Implementation Plan (v1)

**Date:** March 24, 2026  
**Context:** Companion document to Guard51 Implementation Plan v1  
**Product:** Guard51 GOV — Government Security & Community Safety Operations Platform  
**Target Organizations:** State Police, Neighborhood Watch (LSNW+), Local Government Security Committees, NSCDC  
**Architecture:** Same codebase as Guard51, differentiated by `tenant_type` flag  
**Billing:** Same dual model (Paystack + manual bank transfer)

---

## 1. Architecture: Tenant Type System

### How It Works

Guard51 and Guard51 GOV share a single codebase, database schema, and deployment. Every tenant is classified by type, which controls module availability, terminology, UI layout, and role hierarchy.

### Tenant Entity (Updated)

```
Tenant
  id              UUID PK
  name            VARCHAR(200)
  tenant_type     ENUM(
                    'private_security',     -- Guard51 (original)
                    'state_police',         -- Guard51 GOV: State Police
                    'neighborhood_watch',   -- Guard51 GOV: LSNW and equivalents
                    'lg_security',          -- Guard51 GOV: LG Security Committees
                    'nscdc'                 -- Guard51 GOV: Civil Defence
                  )
  org_subtype     VARCHAR(100) NULLABLE    -- further classification (e.g., "zonal_command", "state_command")
  ... (existing fields)
```

### What Tenant Type Controls

| Aspect | Private Security | State Police | Neighborhood Watch | LG Security | NSCDC |
|--------|-----------------|-------------|-------------------|-------------|-------|
| **Terminology: Field Personnel** | Guards | Officers | Members/Volunteers | Marshals | Officers |
| **Terminology: Locations** | Sites / Posts | Beats / Stations | Zones / Wards | Wards / Councils | Zones / Commands |
| **Terminology: Clients** | Clients | Citizens / Public | Communities | Residents | Civilians |
| **Hierarchy Depth** | Flat (2-3 levels) | Deep (10+ ranks) | Medium (4-5 levels) | Medium (3-4 levels) | Deep (8+ ranks) |
| **Finance Model** | Invoice clients | Budget allocation | Volunteer stipends | Budget allocation | Budget allocation |
| **Public-facing** | No (client portal only) | Yes (citizen app) | Yes (citizen app) | Yes (citizen app) | Yes (citizen app) |
| **Weapons Tracking** | Optional (armed guards) | Required | No | No | Required |
| **Case Management** | No | Required | No | No | Required |
| **Crime Statistics** | No | Required | Optional | Optional | Required |

### Terminology Engine

All user-facing labels are driven by a `TerminologyService` that maps generic terms to tenant-type-specific labels:

```typescript
// TerminologyService
getLabel(key: string): string {
  const map = TERMINOLOGY[this.tenantType];
  return map[key] || TERMINOLOGY.default[key];
}

// Usage in templates
{{ terminologyService.getLabel('field_personnel') }}     // "Guards" or "Officers" or "Members"
{{ terminologyService.getLabel('location') }}             // "Site" or "Beat" or "Zone"
{{ terminologyService.getLabel('field_personnel_plural') }} // "Guards" or "Officers" or "Members"
```

```typescript
const TERMINOLOGY = {
  private_security: {
    field_personnel: 'Guard',
    field_personnel_plural: 'Guards',
    location: 'Site',
    location_plural: 'Sites',
    client: 'Client',
    client_plural: 'Clients',
    assignment: 'Post',
    shift_unit: 'Shift',
    report: 'Daily Activity Report',
    org_unit: 'Company',
    command_center: 'Control Room',
    patrol_area: 'Post Site',
  },
  state_police: {
    field_personnel: 'Officer',
    field_personnel_plural: 'Officers',
    location: 'Beat',
    location_plural: 'Beats',
    client: 'Citizen',
    client_plural: 'Citizens',
    assignment: 'Duty Post',
    shift_unit: 'Tour of Duty',
    report: 'Duty Report',
    org_unit: 'Command',
    command_center: 'Control Room',
    patrol_area: 'Patrol Sector',
  },
  neighborhood_watch: {
    field_personnel: 'Member',
    field_personnel_plural: 'Members',
    location: 'Zone',
    location_plural: 'Zones',
    client: 'Community',
    client_plural: 'Communities',
    assignment: 'Watch Post',
    shift_unit: 'Watch',
    report: 'Watch Report',
    org_unit: 'Chapter',
    command_center: 'Coordination Center',
    patrol_area: 'Watch Area',
  },
  lg_security: {
    field_personnel: 'Marshal',
    field_personnel_plural: 'Marshals',
    location: 'Ward',
    location_plural: 'Wards',
    client: 'Resident',
    client_plural: 'Residents',
    assignment: 'Station',
    shift_unit: 'Duty',
    report: 'Activity Report',
    org_unit: 'Committee',
    command_center: 'Operations Center',
    patrol_area: 'Ward Area',
  },
  nscdc: {
    field_personnel: 'Officer',
    field_personnel_plural: 'Officers',
    location: 'Zone',
    location_plural: 'Zones',
    client: 'Civilian',
    client_plural: 'Civilians',
    assignment: 'Deployment',
    shift_unit: 'Tour of Duty',
    report: 'Duty Report',
    org_unit: 'Commandant Office',
    command_center: 'Control Room',
    patrol_area: 'Area of Responsibility',
  },
};
```

### Feature Module Gating by Tenant Type

The existing FeatureModule system gains a `tenant_types` column:

```
FeatureModule (updated)
  id              UUID PK
  module_key      VARCHAR(100)
  name            VARCHAR(200)
  tenant_types    JSONB DEFAULT '["private_security","state_police","neighborhood_watch","lg_security","nscdc"]'
  ... (existing fields)
```

GOV-specific modules set `tenant_types` to only include government types. Private security modules exclude government types. Shared modules include all types.

---

## 2. GOV-Specific Modules

These modules are **only available** to GOV tenant types. They extend the base Guard51 platform.

### Module Registry (GOV-Only)

| Category | Module | Available To | Dependencies |
|----------|--------|-------------|-------------|
| **Hierarchy** | Rank & Promotion System | State Police, NSCDC | Guard |
| **Hierarchy** | Organizational Structure (Commands/Divisions) | State Police, NSCDC | — |
| **Hierarchy** | Chapter & Zone Structure | Neighborhood Watch, LG Security | — |
| **Jurisdiction** | Jurisdictional Boundaries | All GOV | Site |
| **Jurisdiction** | Beat/Patrol Sector Mapping | State Police, NSCDC | Jurisdiction |
| **Public Safety** | Citizen App (Public Reporting) | All GOV | — |
| **Public Safety** | Public Complaint System | All GOV | Citizen App |
| **Public Safety** | Community Tip-Off Line | All GOV | Citizen App |
| **Public Safety** | Safety Alerts & Advisories | All GOV | Citizen App |
| **Investigation** | Case Management | State Police, NSCDC | Incident |
| **Investigation** | Evidence & Exhibit Tracking | State Police, NSCDC | Case Management |
| **Investigation** | Suspect/Person of Interest Registry | State Police, NSCDC | Case Management |
| **Investigation** | Witness Management | State Police, NSCDC | Case Management |
| **Assets** | Weapons & Firearms Registry | State Police, NSCDC | Guard |
| **Assets** | Ammunition Tracking | State Police, NSCDC | Weapons |
| **Assets** | Equipment Checkout/Return | All GOV | Guard |
| **Assets** | Fleet Management (Patrol Vehicles) | State Police, NSCDC | Vehicle Patrol |
| **Finance** | Budget Allocation & Tracking | All GOV | — |
| **Finance** | Volunteer Stipend Management | Neighborhood Watch, LG Security | Guard |
| **Community** | Community Policing Module | All GOV | — |
| **Community** | Town Hall / Meeting Tracker | All GOV | Community Policing |
| **Community** | Volunteer Management | Neighborhood Watch, LG Security | Guard |
| **Community** | Training & Certification Tracker | All GOV | Guard |
| **Analytics** | Crime Statistics Dashboard | State Police, NSCDC | Incident |
| **Analytics** | Response Time Analytics | All GOV | Dispatch |
| **Analytics** | Public Safety Transparency Dashboard | All GOV | Crime Statistics |
| **Compliance** | Government Reporting (State/Federal) | All GOV | Analytics |
| **Compliance** | Use of Force Reporting | State Police, NSCDC | Incident |
| **Inter-Agency** | Cross-Agency Information Sharing | All GOV | — |

**Total GOV-only modules: 28**  
**Combined with base Guard51: 52 + 28 = 80 modules**

---

## 3. Rank & Hierarchy System

### State Police Rank Structure

```
Rank
  id              UUID PK
  tenant_id       UUID FK → Tenant
  name            VARCHAR(100)         -- "Commissioner of Police"
  abbreviation    VARCHAR(20)          -- "CP"
  level           INT                  -- 1 = highest (CP), 10 = lowest (Constable)
  grade_level     VARCHAR(20) NULLABLE -- civil service grade equivalent
  is_commissioned BOOLEAN              -- commissioned vs non-commissioned
  insignia_url    VARCHAR(500) NULLABLE
  permissions     JSONB DEFAULT '[]'   -- rank-based permissions
  created_at, updated_at
```

**Default State Police Ranks (seeded on tenant creation):**

| Level | Rank | Abbreviation | Commissioned |
|-------|------|-------------|-------------|
| 1 | Commissioner of Police | CP | Yes |
| 2 | Deputy Commissioner of Police | DCP | Yes |
| 3 | Assistant Commissioner of Police | ACP | Yes |
| 4 | Chief Superintendent of Police | CSP | Yes |
| 5 | Superintendent of Police | SP | Yes |
| 6 | Deputy Superintendent of Police | DSP | Yes |
| 7 | Assistant Superintendent of Police | ASP | Yes |
| 8 | Inspector of Police | IP | No |
| 9 | Sergeant | SGT | No |
| 10 | Corporal | CPL | No |
| 11 | Constable | CONST | No |

**NSCDC Ranks (seeded separately):**

| Level | Rank | Abbreviation |
|-------|------|-------------|
| 1 | Commandant General | CG |
| 2 | Deputy Commandant General | DCG |
| 3 | Assistant Commandant General | ACG |
| 4 | Commandant | CMD |
| 5 | Deputy Commandant | DC |
| 6 | Assistant Commandant | AC |
| 7 | Chief Inspector | CI |
| 8 | Inspector | INSP |
| 9 | Assistant Inspector | AI |
| 10 | Corps Assistant | CA |

**Neighborhood Watch Levels (configurable):**

| Level | Title |
|-------|-------|
| 1 | State Coordinator |
| 2 | Zonal Coordinator |
| 3 | LGA Coordinator |
| 4 | Ward Leader |
| 5 | Unit Leader |
| 6 | Member |

### Guard Entity (Extended for GOV)

```
Guard (updated fields for GOV)
  ...existing fields...
  rank_id         UUID FK → Rank NULLABLE         -- GOV only
  service_number  VARCHAR(50) NULLABLE             -- e.g., "SP/LAG/2026/001234"
  date_of_enlistment DATE NULLABLE
  years_of_service INT NULLABLE
  current_command  VARCHAR(200) NULLABLE           -- "Lagos State Police Command"
  current_division VARCHAR(200) NULLABLE           -- "Ikeja Division"
  current_station  VARCHAR(200) NULLABLE           -- "Alausa Station"
  is_armed         BOOLEAN DEFAULT FALSE
  blood_group      VARCHAR(5) NULLABLE
  genotype         VARCHAR(5) NULLABLE
  next_of_kin_name VARCHAR(200) NULLABLE
  next_of_kin_phone VARCHAR(50) NULLABLE
  next_of_kin_relationship VARCHAR(50) NULLABLE
```

### Promotion Tracking

```
PromotionRecord
  id              UUID PK
  tenant_id       UUID FK → Tenant
  guard_id        UUID FK → Guard
  from_rank_id    UUID FK → Rank
  to_rank_id      UUID FK → Rank
  promotion_date  DATE
  effective_date  DATE
  promotion_type  ENUM(regular, merit, accelerated, posthumous)
  authority       VARCHAR(200)         -- "State Police Service Commission"
  gazette_ref     VARCHAR(100) NULLABLE -- official reference number
  notes           TEXT NULLABLE
  approved_by     UUID FK → User
  created_at      TIMESTAMP
```

### Organizational Structure

```
OrganizationalUnit
  id              UUID PK
  tenant_id       UUID FK → Tenant
  parent_id       UUID FK → OrganizationalUnit NULLABLE  -- hierarchical
  name            VARCHAR(200)         -- "Lagos State Command", "Ikeja Division", "Alausa Station"
  unit_type       ENUM(
                    -- State Police
                    'state_command', 'zonal_command', 'area_command', 'division', 'station', 'outpost',
                    -- Neighborhood Watch
                    'state_chapter', 'zone', 'lga_chapter', 'ward_unit',
                    -- LG Security
                    'lg_committee', 'ward_committee',
                    -- NSCDC
                    'state_commandant', 'zonal_office', 'divisional_office', 'unit'
                  )
  code            VARCHAR(50) NULLABLE -- "LAG/IKJ/ALS"
  head_id         UUID FK → Guard NULLABLE  -- commanding officer / coordinator
  address         TEXT NULLABLE
  latitude        DECIMAL(10,8) NULLABLE
  longitude       DECIMAL(11,8) NULLABLE
  phone           VARCHAR(50) NULLABLE
  email           VARCHAR(255) NULLABLE
  jurisdiction_boundary GEOMETRY NULLABLE  -- PostGIS polygon
  status          ENUM(active, inactive)
  created_at, updated_at
```

This allows modeling:
- **State Police:** State Command → Zonal Commands → Area Commands → Divisions → Stations → Outposts
- **Neighborhood Watch:** State Chapter → Zones → LGA Chapters → Ward Units
- **LG Security:** LG Committee → Ward Committees
- **NSCDC:** State Commandant → Zonal Offices → Divisional Offices → Units

---

## 4. Citizen App & Public Safety

### Citizen Mobile App (NativeScript)

A **fifth mobile app** added to the Guard51 app roster, available only for GOV tenants. This is a public-facing app for residents to interact with their local security apparatus.

| App | Users | Key Features | Delivery |
|-----|-------|-------------|----------|
| **Citizen App** | General public | Report incidents, receive alerts, contact officers, track complaints | Google Play Store + APK sideload |

**Note:** Unlike the other 4 apps, the Citizen App **should** be on Google Play Store and Apple App Store for maximum public accessibility. It can also be available via APK sideload for areas with restricted app store access.

### Citizen App Features

**Report an Incident:**
1. Citizen opens app, taps "Report Incident"
2. Selects category (theft, assault, suspicious activity, noise, vandalism, fire, medical, traffic, other)
3. Describes incident (text + optional photo/video/voice recording)
4. App auto-captures GPS location (or citizen pins location on map)
5. Optional: citizen provides name and phone (or reports anonymously)
6. Submits → system creates a public incident ticket
7. Citizen receives a tracking number (e.g., "CIT/LAG/2026/003456")
8. System routes to appropriate unit based on location and type

**Track My Complaint:**
1. Citizen enters tracking number or views their submission history (if registered)
2. Sees status: Received → Assigned → Investigating → Resolved / Closed
3. Receives push notifications on status changes
4. Can add follow-up information or photos

**Safety Alerts:**
- Push notifications for active incidents in their area (configurable radius)
- Weather warnings, curfew notices, road closures
- Missing person alerts
- Community advisories

**Contact Local Officers:**
- View officers assigned to their beat/zone (name, photo, phone — configurable visibility)
- Direct call or message (routed through system, not personal numbers)
- "Beat Officer" feature: who's patrolling my area right now?

**Community Engagement:**
- Upcoming town hall meetings
- Community policing events
- Safety tips and awareness content
- Feedback on local security (anonymous ratings)

### Citizen App Entities

```
CitizenUser
  id              UUID PK
  phone           VARCHAR(50)          -- primary identifier
  first_name      VARCHAR(100) NULLABLE
  last_name       VARCHAR(100) NULLABLE
  email           VARCHAR(255) NULLABLE
  photo_url       VARCHAR(500) NULLABLE
  home_location_lat DECIMAL(10,8) NULLABLE
  home_location_lng DECIMAL(11,8) NULLABLE
  home_area       VARCHAR(200) NULLABLE  -- "Lekki Phase 1"
  lga             VARCHAR(100) NULLABLE
  state           VARCHAR(100) NULLABLE
  is_verified     BOOLEAN DEFAULT FALSE  -- phone OTP verified
  alert_radius_km INT DEFAULT 5          -- safety alert radius
  is_active       BOOLEAN DEFAULT TRUE
  created_at, updated_at

CitizenReport
  id              UUID PK
  tenant_id       UUID FK → Tenant
  tracking_number VARCHAR(30)            -- "CIT/LAG/2026/003456"
  citizen_id      UUID FK → CitizenUser NULLABLE  -- NULL = anonymous
  is_anonymous    BOOLEAN DEFAULT FALSE
  category        ENUM(theft, assault, robbery, suspicious_activity, noise, vandalism,
                       fire, medical_emergency, traffic, domestic, missing_person,
                       drug_activity, environmental, corruption, other)
  severity        ENUM(emergency, urgent, non_urgent, informational)
  title           VARCHAR(300)
  description     TEXT
  latitude        DECIMAL(10,8)
  longitude       DECIMAL(11,8)
  address_text    VARCHAR(500) NULLABLE
  attachments     JSONB DEFAULT '[]'     -- [{url, type, name, thumbnail}]
  status          ENUM(received, reviewed, assigned, investigating, resolved, closed, rejected)
  assigned_unit_id UUID FK → OrganizationalUnit NULLABLE
  assigned_officer_id UUID FK → Guard NULLABLE
  priority_override ENUM(critical, high, medium, low) NULLABLE
  resolution      TEXT NULLABLE
  resolution_category ENUM(arrest_made, warning_issued, referred, no_action, false_report, other) NULLABLE
  resolved_at     TIMESTAMP NULLABLE
  resolved_by     UUID FK → User NULLABLE
  citizen_rating  INT NULLABLE           -- 1-5 satisfaction rating
  citizen_feedback TEXT NULLABLE
  auto_routed     BOOLEAN DEFAULT FALSE  -- was it auto-assigned by location?
  created_at, updated_at

CitizenReportUpdate
  id              UUID PK
  report_id       UUID FK → CitizenReport
  update_type     ENUM(status_change, note, citizen_followup, assignment)
  content         TEXT
  is_public       BOOLEAN DEFAULT TRUE   -- visible to citizen?
  updated_by      UUID FK → User NULLABLE
  created_at      TIMESTAMP

SafetyAlert
  id              UUID PK
  tenant_id       UUID FK → Tenant
  alert_type      ENUM(active_incident, missing_person, weather, curfew, road_closure, advisory, community_event)
  severity        ENUM(critical, warning, info)
  title           VARCHAR(300)
  body            TEXT
  latitude        DECIMAL(10,8) NULLABLE
  longitude       DECIMAL(11,8) NULLABLE
  radius_km       INT DEFAULT 10         -- broadcast radius
  target_area     VARCHAR(200) NULLABLE  -- "Victoria Island", "Ikeja LGA"
  media_url       VARCHAR(500) NULLABLE
  is_active       BOOLEAN DEFAULT TRUE
  expires_at      TIMESTAMP NULLABLE
  published_by    UUID FK → User
  published_at    TIMESTAMP
  created_at      TIMESTAMP

CommunityEvent
  id              UUID PK
  tenant_id       UUID FK → Tenant
  org_unit_id     UUID FK → OrganizationalUnit NULLABLE
  title           VARCHAR(300)         -- "Town Hall Meeting - Ikeja Ward C"
  description     TEXT
  event_type      ENUM(town_hall, community_patrol, training, awareness_campaign, feedback_session, other)
  venue           VARCHAR(300)
  latitude        DECIMAL(10,8) NULLABLE
  longitude       DECIMAL(11,8) NULLABLE
  event_date      DATE
  start_time      TIME
  end_time        TIME
  max_attendees   INT NULLABLE
  is_public       BOOLEAN DEFAULT TRUE  -- visible on citizen app
  organizer_id    UUID FK → User
  created_at, updated_at

CommunityEventAttendance
  id              UUID PK
  event_id        UUID FK → CommunityEvent
  citizen_id      UUID FK → CitizenUser NULLABLE
  guard_id        UUID FK → Guard NULLABLE   -- officers attending
  registered_at   TIMESTAMP
  attended        BOOLEAN DEFAULT FALSE
  created_at      TIMESTAMP
```

### Citizen Report Routing Engine

When a citizen submits a report, the system automatically routes it based on:

```
1. GPS coordinates → find containing OrganizationalUnit (PostGIS ST_Contains)
2. Category → determine which unit type handles it:
   - theft/robbery/assault → nearest station/division
   - fire → also notify fire service (external alert)
   - medical → also notify ambulance (external alert)
   - traffic → traffic division (if separate)
   - missing_person → divisional level
3. Severity: emergency → instant push + SMS to duty officer
            urgent → push notification to unit commander
            non_urgent → queued for review
4. Auto-assign to nearest on-duty officer (GPS proximity) for emergency reports
```

### Citizen App API

```
# Authentication (phone + OTP via Termii)
POST   /api/citizen/auth/request-otp      -- send OTP to phone
POST   /api/citizen/auth/verify-otp       -- verify and get JWT
PUT    /api/citizen/auth/profile           -- update profile

# Reports
POST   /api/citizen/reports                -- submit incident report
GET    /api/citizen/reports                -- my reports (authenticated)
GET    /api/citizen/reports/:tracking      -- track by number (no auth required)
POST   /api/citizen/reports/:id/followup   -- add follow-up info
POST   /api/citizen/reports/:id/rate       -- rate resolution

# Alerts
GET    /api/citizen/alerts                 -- active alerts near me
GET    /api/citizen/alerts/:id
POST   /api/citizen/alerts/subscribe       -- configure alert preferences

# Community
GET    /api/citizen/events                 -- upcoming events near me
POST   /api/citizen/events/:id/register    -- RSVP for event

# Local Security
GET    /api/citizen/beat-officers           -- officers in my area
GET    /api/citizen/emergency-contacts      -- local emergency numbers
```

### Backend (GOV Admin)

```
# Citizen Reports Management
GET    /api/reports/citizen                        -- all citizen reports
GET    /api/reports/citizen/:id
PUT    /api/reports/citizen/:id                    -- update status, assign
POST   /api/reports/citizen/:id/assign             -- assign to officer/unit
POST   /api/reports/citizen/:id/resolve            -- mark resolved
POST   /api/reports/citizen/:id/notes              -- internal note (not visible to citizen)

# Safety Alerts
GET    /api/alerts
POST   /api/alerts                                 -- create alert
PUT    /api/alerts/:id
DELETE /api/alerts/:id

# Community Events
GET    /api/community/events
POST   /api/community/events
PUT    /api/community/events/:id
GET    /api/community/events/:id/attendees

# Analytics
GET    /api/analytics/citizen-reports              -- report volume, categories, response times
GET    /api/analytics/citizen-satisfaction          -- ratings breakdown
```

---

## 5. Case Management (State Police & NSCDC)

### Overview

Unlike incident reports (single events), cases are ongoing investigations that may span weeks or months, link multiple incidents, track evidence, suspects, witnesses, and investigation progress.

### Entities

```
Case
  id              UUID PK
  tenant_id       UUID FK → Tenant
  case_number     VARCHAR(50)          -- "CR/LAG/IKJ/2026/000123"
  title           VARCHAR(500)
  description     TEXT
  case_type       ENUM(criminal, civil, missing_person, domestic, fraud, narcotics, cybercrime, other)
  status          ENUM(opened, under_investigation, awaiting_evidence, awaiting_prosecution,
                       in_court, closed_arrest, closed_unfounded, closed_other, suspended, referred)
  priority        ENUM(critical, high, medium, low)
  classification  ENUM(unclassified, restricted, confidential, secret) DEFAULT 'unclassified'
  investigating_officer_id UUID FK → Guard
  supervising_officer_id   UUID FK → Guard NULLABLE
  org_unit_id     UUID FK → OrganizationalUnit
  location_description VARCHAR(500) NULLABLE
  latitude        DECIMAL(10,8) NULLABLE
  longitude       DECIMAL(11,8) NULLABLE
  date_occurred   TIMESTAMP NULLABLE
  date_reported   TIMESTAMP
  date_opened     TIMESTAMP
  date_closed     TIMESTAMP NULLABLE
  closure_reason  TEXT NULLABLE
  citizen_report_id UUID FK → CitizenReport NULLABLE  -- linked citizen report
  created_at, updated_at

CaseIncidentLink
  id              UUID PK
  case_id         UUID FK → Case
  incident_id     UUID FK → IncidentReport
  link_type       ENUM(primary, related, pattern)
  notes           TEXT NULLABLE
  linked_by       UUID FK → User
  created_at      TIMESTAMP

CaseNote
  id              UUID PK
  case_id         UUID FK → Case
  author_id       UUID FK → User
  content         TEXT
  note_type       ENUM(investigation_update, interview_summary, evidence_note, court_update, supervisor_review, general)
  is_confidential BOOLEAN DEFAULT FALSE
  attachments     JSONB DEFAULT '[]'
  created_at      TIMESTAMP

Suspect
  id              UUID PK
  tenant_id       UUID FK → Tenant
  first_name      VARCHAR(100)
  last_name       VARCHAR(100)
  alias           VARCHAR(200) NULLABLE
  gender          ENUM(male, female, other) NULLABLE
  age_estimate    VARCHAR(20) NULLABLE   -- "25-30"
  description     TEXT NULLABLE          -- physical description
  photo_url       VARCHAR(500) NULLABLE
  phone           VARCHAR(50) NULLABLE
  address         TEXT NULLABLE
  id_type         VARCHAR(50) NULLABLE
  id_number       VARCHAR(50) NULLABLE
  status          ENUM(wanted, apprehended, released, charged, convicted, cleared)
  created_at, updated_at

CaseSuspectLink
  id              UUID PK
  case_id         UUID FK → Case
  suspect_id      UUID FK → Suspect
  role            ENUM(primary_suspect, accomplice, person_of_interest)
  notes           TEXT NULLABLE
  linked_by       UUID FK → User
  created_at      TIMESTAMP

Witness
  id              UUID PK
  case_id         UUID FK → Case
  first_name      VARCHAR(100)
  last_name       VARCHAR(100)
  phone           VARCHAR(50)
  email           VARCHAR(255) NULLABLE
  address         TEXT NULLABLE
  statement       TEXT
  statement_date  TIMESTAMP
  is_anonymous    BOOLEAN DEFAULT FALSE
  protection_needed BOOLEAN DEFAULT FALSE
  recorded_by     UUID FK → User
  created_at      TIMESTAMP

Evidence
  id              UUID PK
  case_id         UUID FK → Case
  evidence_number VARCHAR(50)          -- "EVD/2026/000456"
  description     TEXT
  evidence_type   ENUM(physical, digital, documentary, photographic, video, audio, forensic, other)
  location_found  VARCHAR(300)
  found_by        UUID FK → Guard
  found_at        TIMESTAMP
  storage_location VARCHAR(200)        -- where it's kept
  chain_of_custody JSONB DEFAULT '[]'  -- [{handler, from, to, timestamp, notes}]
  status          ENUM(collected, in_storage, in_lab, in_court, returned, disposed)
  attachments     JSONB DEFAULT '[]'
  created_at, updated_at
```

### Case Management API

```
# Cases
GET    /api/cases
POST   /api/cases
GET    /api/cases/:id
PUT    /api/cases/:id
POST   /api/cases/:id/close
POST   /api/cases/:id/reopen
POST   /api/cases/:id/assign
POST   /api/cases/:id/link-incident

# Case Notes
GET    /api/cases/:id/notes
POST   /api/cases/:id/notes

# Suspects
GET    /api/suspects
POST   /api/suspects
GET    /api/suspects/:id
PUT    /api/suspects/:id
POST   /api/cases/:id/suspects           -- link suspect to case

# Witnesses
GET    /api/cases/:id/witnesses
POST   /api/cases/:id/witnesses

# Evidence
GET    /api/cases/:id/evidence
POST   /api/cases/:id/evidence
PUT    /api/evidence/:id
POST   /api/evidence/:id/custody          -- update chain of custody

# Dashboard
GET    /api/cases/dashboard               -- open cases, clearance rate, case load by officer
```

---

## 6. Weapons & Equipment Tracking

### Entities

```
Weapon
  id              UUID PK
  tenant_id       UUID FK → Tenant
  weapon_type     ENUM(pistol, rifle, shotgun, smg, taser, baton, pepper_spray, other)
  make            VARCHAR(100)         -- "Beretta"
  model           VARCHAR(100)         -- "92FS"
  serial_number   VARCHAR(100) UNIQUE
  caliber         VARCHAR(50) NULLABLE -- "9mm"
  registration_number VARCHAR(100) NULLABLE
  status          ENUM(in_armory, issued, maintenance, retired, lost, stolen)
  condition       ENUM(serviceable, needs_repair, unserviceable)
  org_unit_id     UUID FK → OrganizationalUnit  -- which armory/station
  assigned_to     UUID FK → Guard NULLABLE
  last_inspection DATE NULLABLE
  notes           TEXT NULLABLE
  created_at, updated_at

WeaponIssuanceLog
  id              UUID PK
  weapon_id       UUID FK → Weapon
  guard_id        UUID FK → Guard
  issued_by       UUID FK → User        -- armory officer
  issued_at       TIMESTAMP
  returned_at     TIMESTAMP NULLABLE
  returned_to     UUID FK → User NULLABLE
  purpose         ENUM(duty, training, operation, escort)
  shift_id        UUID FK → Shift NULLABLE
  ammunition_issued INT DEFAULT 0
  ammunition_returned INT NULLABLE
  ammunition_expended INT NULLABLE
  condition_on_issue ENUM(serviceable, needs_repair)
  condition_on_return ENUM(serviceable, needs_repair) NULLABLE
  notes           TEXT NULLABLE
  created_at      TIMESTAMP

AmmunitionInventory
  id              UUID PK
  tenant_id       UUID FK → Tenant
  org_unit_id     UUID FK → OrganizationalUnit
  caliber         VARCHAR(50)          -- "9mm", "5.56mm"
  quantity        INT
  batch_number    VARCHAR(100) NULLABLE
  manufactured_date DATE NULLABLE
  expiry_date     DATE NULLABLE
  last_counted_at TIMESTAMP
  last_counted_by UUID FK → User
  created_at, updated_at

AmmunitionTransaction
  id              UUID PK
  inventory_id    UUID FK → AmmunitionInventory
  transaction_type ENUM(received, issued, returned, expended, disposed, transferred)
  quantity        INT                  -- positive for received/returned, negative for issued/expended
  guard_id        UUID FK → Guard NULLABLE
  reference       VARCHAR(200) NULLABLE -- "Duty 2026-03-24", "Training Exercise"
  recorded_by     UUID FK → User
  created_at      TIMESTAMP

Equipment
  id              UUID PK
  tenant_id       UUID FK → Tenant
  name            VARCHAR(200)         -- "Body Camera", "Radio", "Handcuffs"
  equipment_type  ENUM(communication, protective, surveillance, restraint, vehicle, tech, uniform, other)
  serial_number   VARCHAR(100) NULLABLE
  asset_tag       VARCHAR(50) NULLABLE
  status          ENUM(available, issued, maintenance, retired, lost)
  org_unit_id     UUID FK → OrganizationalUnit
  assigned_to     UUID FK → Guard NULLABLE
  purchase_date   DATE NULLABLE
  warranty_expiry DATE NULLABLE
  notes           TEXT NULLABLE
  created_at, updated_at

EquipmentCheckoutLog
  id              UUID PK
  equipment_id    UUID FK → Equipment
  guard_id        UUID FK → Guard
  checked_out_by  UUID FK → User
  checked_out_at  TIMESTAMP
  expected_return TIMESTAMP NULLABLE
  returned_at     TIMESTAMP NULLABLE
  returned_to     UUID FK → User NULLABLE
  condition_out   ENUM(good, fair, damaged)
  condition_in    ENUM(good, fair, damaged) NULLABLE
  notes           TEXT NULLABLE
  created_at      TIMESTAMP

UseOfForceReport
  id              UUID PK
  tenant_id       UUID FK → Tenant
  guard_id        UUID FK → Guard      -- officer involved
  incident_id     UUID FK → IncidentReport NULLABLE
  case_id         UUID FK → Case NULLABLE
  force_type      ENUM(verbal, physical_restraint, baton, pepper_spray, taser, firearm_drawn, firearm_discharged, other)
  weapon_id       UUID FK → Weapon NULLABLE
  rounds_fired    INT DEFAULT 0
  subject_name    VARCHAR(200) NULLABLE
  subject_injury  ENUM(none, minor, serious, fatal) NULLABLE
  officer_injury  ENUM(none, minor, serious) NULLABLE
  justification   TEXT
  witnesses       TEXT NULLABLE
  supervisor_id   UUID FK → Guard
  supervisor_review TEXT NULLABLE
  review_status   ENUM(pending_review, justified, under_investigation, unjustified)
  reviewed_at     TIMESTAMP NULLABLE
  attachments     JSONB DEFAULT '[]'
  created_at      TIMESTAMP
```

---

## 7. Budget & Finance (GOV)

### Entities

```
BudgetAllocation
  id              UUID PK
  tenant_id       UUID FK → Tenant
  fiscal_year     VARCHAR(10)          -- "2026", "2026/2027"
  org_unit_id     UUID FK → OrganizationalUnit NULLABLE
  category        ENUM(personnel, equipment, operations, training, maintenance, fuel, communication, other)
  allocated_amount DECIMAL(14,2)
  spent_amount    DECIMAL(14,2) DEFAULT 0
  committed_amount DECIMAL(14,2) DEFAULT 0  -- approved but not yet spent
  balance         DECIMAL(14,2)        -- calculated
  approved_by     VARCHAR(200) NULLABLE
  approval_reference VARCHAR(100) NULLABLE
  notes           TEXT NULLABLE
  created_at, updated_at

BudgetExpenditure
  id              UUID PK
  allocation_id   UUID FK → BudgetAllocation
  description     TEXT
  amount          DECIMAL(12,2)
  expenditure_date DATE
  payment_method  ENUM(transfer, cash, cheque)
  reference       VARCHAR(100) NULLABLE
  receipt_url     VARCHAR(500) NULLABLE
  approved_by     UUID FK → User
  recorded_by     UUID FK → User
  created_at      TIMESTAMP

VolunteerStipend
  id              UUID PK
  tenant_id       UUID FK → Tenant
  guard_id        UUID FK → Guard      -- volunteer/member
  period_start    DATE
  period_end      DATE
  amount          DECIMAL(10,2)
  status          ENUM(pending, approved, paid)
  payment_method  ENUM(bank_transfer, cash, mobile_money)
  payment_reference VARCHAR(100) NULLABLE
  approved_by     UUID FK → User NULLABLE
  paid_at         TIMESTAMP NULLABLE
  notes           TEXT NULLABLE
  created_at, updated_at
```

---

## 8. Crime Statistics & Analytics

### Dashboard Components

**Crime Statistics Dashboard (State Police / NSCDC):**
- **SVG heatmap:** crime density by location and time
- **SVG bar chart:** crimes by category (monthly comparison)
- **SVG line chart:** crime trend over 12 months
- **SVG donut chart:** case clearance rate
- **SVG gauge chart:** average response time
- **SVG stacked bar chart:** case status breakdown by division

**Public Safety Transparency Dashboard (public-facing, configurable):**
- Crime statistics by LGA/ward (anonymized, no victim data)
- Response time metrics
- Community policing event attendance
- Citizen satisfaction ratings
- Published as a public web page (no login required, link shared by government)

### Crime Statistics Entities

```
CrimeStatistic
  id              UUID PK
  tenant_id       UUID FK → Tenant
  period_type     ENUM(daily, weekly, monthly, quarterly, annual)
  period_start    DATE
  period_end      DATE
  org_unit_id     UUID FK → OrganizationalUnit NULLABLE
  category        VARCHAR(100)         -- "robbery", "assault", etc.
  total_reported  INT DEFAULT 0
  total_resolved  INT DEFAULT 0
  total_arrests   INT DEFAULT 0
  avg_response_minutes DECIMAL(6,2) NULLABLE
  clearance_rate  DECIMAL(5,2) NULLABLE -- percentage
  year_over_year_change DECIMAL(5,2) NULLABLE -- percentage
  generated_at    TIMESTAMP
  created_at      TIMESTAMP
```

---

## 9. GOV Subscription Plans

| Plan | Price (Monthly) | Officers/Members | Beats/Zones | Key Modules |
|------|----------------|-----------------|-------------|------------|
| **GOV Starter** | ₦50,000 | 50 | 10 | Core + GPS tracking, scheduling, attendance, citizen app, basic reporting |
| **GOV Professional** | ₦150,000 | 200 | 50 | + Geofencing, case management, weapons tracking, crime stats, community policing |
| **GOV Command** | ₦350,000 | 500 | 100 | + Full analytics, use of force reporting, evidence tracking, inter-agency, budget mgmt |
| **GOV Enterprise** | Custom | Unlimited | Unlimited | + All modules, dedicated support, custom integrations, state-wide deployment |

Plans are created by super admin using the same custom plan builder from Phase 0D, with `tenant_types` restricted to GOV types.

---

## 10. Mobile Apps (Updated Roster)

| # | App | Users | Tenant Types | Delivery |
|---|-----|-------|-------------|----------|
| 1 | **Guard/Officer App** | Field guards/officers/members | All | APK sideload |
| 2 | **Client App** | Private security clients | Private Security | APK sideload |
| 3 | **Supervisor App** | Supervisors / Station commanders | All | APK sideload |
| 4 | **Dispatcher App** | Control room / Dispatch | All | APK sideload |
| 5 | **Citizen App** | General public | GOV only | Google Play + App Store + APK |

**Desktop:** Electron app available to all tenant types.

**Total: 5 mobile apps + 1 desktop app**

---

## 11. GOV Phased Implementation

GOV modules are implemented as **Phase 9 and Phase 10**, following the base Guard51 phases. This ensures the core platform is solid before adding government-specific complexity.

### PHASE 9: GOV Foundation (Weeks 43-50)

**Goal:** Tenant type system, terminology engine, rank/hierarchy, organizational structure, citizen app, budget management.

#### Phase 9A: Tenant Type System & Terminology Engine
- Add `tenant_type` to Tenant entity
- Build `TerminologyService` with language maps for all 5 tenant types
- Update all frontend labels to use `TerminologyService`
- Update feature module gating to support `tenant_types` filter
- Seed GOV-specific feature modules (28 modules)
- GOV subscription plans

**Deliverables:**
- ✅ Tenant type selection during onboarding
- ✅ All UI labels dynamic based on tenant type
- ✅ GOV modules visible only to GOV tenants

#### Phase 9B: Rank & Promotion System
- Rank entity + default seeds (State Police, NSCDC, Neighborhood Watch, LG Security)
- Guard entity extended with rank, service number, enlistment date
- PromotionRecord entity
- Rank-based permission overrides
- Rank management UI (admin)
- Promotion history tracking

**Deliverables:**
- ✅ Rank hierarchy per organization type
- ✅ Promotion tracking with gazette references
- ✅ Rank-based permissions

#### Phase 9C: Organizational Structure
- OrganizationalUnit entity (hierarchical, self-referencing)
- PostGIS jurisdictional boundaries per unit
- Org chart visualization (tree + map view)
- Personnel assignment to units
- Auto-routing based on jurisdiction (for citizen reports and dispatch)

**Deliverables:**
- ✅ Full organizational hierarchy (command → division → station)
- ✅ Map-based jurisdictional boundaries
- ✅ Personnel assigned to units

#### Phase 9D: Citizen App & Public Reporting
- CitizenUser entity + phone OTP auth (Termii)
- CitizenReport entity + submission flow
- Auto-routing engine (GPS → jurisdiction → unit)
- Report status tracking + citizen notifications
- SafetyAlert entity + broadcasting (push to citizens in radius)
- CommunityEvent entity + RSVP
- Citizen app NativeScript build
- Backend APIs for citizen endpoints

**Deliverables:**
- ✅ Citizen app (NativeScript) with incident reporting
- ✅ Anonymous and identified reporting
- ✅ Auto-routing of reports to correct unit
- ✅ Safety alerts with geo-targeting
- ✅ Community events calendar

#### Phase 9E: Budget & Volunteer Stipend Management
- BudgetAllocation + BudgetExpenditure entities
- Budget dashboard by fiscal year and org unit
- Expenditure recording and receipt upload
- VolunteerStipend entity (for Neighborhood Watch / LG Security)
- Stipend approval and payment tracking

**Deliverables:**
- ✅ Budget allocation and tracking
- ✅ Expenditure recording
- ✅ Volunteer stipend management

#### Phase 9F: GOV Admin Dashboard
- GOV-specific dashboard widgets:
  - Officers/members on duty
  - Active citizen reports (pending, investigating)
  - Crime statistics summary
  - Response time metrics
  - Community events upcoming
  - Budget utilization gauge
- **SVG charts:** adapted for GOV metrics

**Phase 9 Deliverables:**
- ✅ Tenant type system with terminology engine
- ✅ Rank hierarchy and promotion tracking
- ✅ Organizational structure with jurisdictional boundaries
- ✅ Citizen app with public incident reporting
- ✅ Safety alerts and community events
- ✅ Budget management
- ✅ **GOV organizations can onboard and operate**

---

### PHASE 10: GOV Advanced (Weeks 51-58)

**Goal:** Case management, weapons/equipment tracking, crime statistics, use of force reporting, public transparency dashboard, inter-agency coordination.

#### Phase 10A: Case Management
- Case, CaseNote, Suspect, CaseSuspectLink, Witness, Evidence entities
- Case lifecycle management (open → investigate → close)
- Link incidents and citizen reports to cases
- Evidence chain of custody tracking
- Case assignment and workload balancing
- Case dashboard with clearance rate metrics

**Deliverables:**
- ✅ Full case management lifecycle
- ✅ Evidence tracking with chain of custody
- ✅ Suspect and witness management

#### Phase 10B: Weapons & Equipment Tracking
- Weapon, WeaponIssuanceLog entities
- AmmunitionInventory, AmmunitionTransaction entities
- Equipment, EquipmentCheckoutLog entities
- Armory management (issue/return with shift linking)
- Ammunition inventory with transaction history
- Equipment checkout/return workflow
- Alerts for overdue returns, low ammunition, inspection due

**Deliverables:**
- ✅ Firearms registry with serial tracking
- ✅ Ammunition inventory management
- ✅ Equipment checkout/return
- ✅ Full audit trail

#### Phase 10C: Use of Force Reporting
- UseOfForceReport entity
- Mandatory reporting after firearm discharge or physical force
- Supervisor review workflow
- Link to incident and case
- Analytics: force type frequency, officer patterns, review outcomes

**Deliverables:**
- ✅ Use of force reporting and review
- ✅ Compliance analytics

#### Phase 10D: Crime Statistics & Public Dashboard
- CrimeStatistic entity (auto-generated aggregates)
- Crime stats dashboard (internal)
- Public transparency dashboard (configurable, no login required)
- Export: PDF reports for government submission
- Year-over-year comparison

**Deliverables:**
- ✅ Crime statistics with trend analysis
- ✅ Public-facing transparency dashboard
- ✅ Government compliance reports

#### Phase 10E: Inter-Agency Coordination
- Cross-tenant information sharing (controlled, permission-based)
- Shared incident alerts across agencies in same state
- Joint operation coordination (multiple agencies on one operation)
- Referral system (route cases between agencies)
- Shared "wanted persons" registry

**Deliverables:**
- ✅ Cross-agency information sharing
- ✅ Joint operation coordination
- ✅ Inter-agency referral system

**Phase 10 Deliverables:**
- ✅ Full case management with evidence tracking
- ✅ Weapons and equipment registry
- ✅ Use of force compliance reporting
- ✅ Crime statistics and public transparency
- ✅ Inter-agency coordination
- ✅ **GOV platform fully operational for all 4 organization types**

---

## 12. Updated Timeline Summary (Guard51 + GOV)

| Phase | What You Get | Weeks | Cumulative |
|-------|-------------|-------|-----------|
| **0** | SaaS platform, billing, features, super admin, SVG charts | 6 | 6 |
| **1** | Guards, sites, clients, guard web portal, dashboard | 5 | 11 |
| **2** | Scheduling, time clock, attendance, passdown logs | 4 | 15 |
| **3** | Guard mobile app, live GPS, geofencing, site tours, panic button | 6 | 21 |
| **4** | Reporting, incidents, dispatcher console, tasks | 5 | 26 |
| **5** | Invoicing, payroll, pay rates | 4 | 30 |
| **6** | Client portal, client app, messenger, notifications | 5 | 35 |
| **7** | Vehicle patrol, visitors, parking, supervisor + dispatcher apps, desktop | 7 | 42 |
| **8** | Analytics, AI, multi-language, offline, WhatsApp (ongoing) | Ongoing | — |
| **9** | **GOV:** Tenant types, ranks, org structure, citizen app, budget | 8 | 50 |
| **10** | **GOV:** Cases, weapons, crime stats, use of force, inter-agency | 8 | 58 |

**Guard51 (Private Security) complete: ~42 weeks (~10.5 months)**  
**Guard51 + GOV complete: ~58 weeks (~14.5 months)**

---

## 13. Project Structure (Updated for GOV)

New additions to the monorepo:

```
guard51/
├── apps/
│   ├── api/
│   │   └── src/
│   │       └── Module/
│   │           ├── ... (existing modules)
│   │           ├── TenantType/           # Terminology engine, tenant type logic
│   │           ├── Rank/                 # Rank & promotion system (GOV)
│   │           ├── OrgStructure/         # Organizational units & hierarchy (GOV)
│   │           ├── CitizenReport/        # Public incident reporting (GOV)
│   │           ├── SafetyAlert/          # Safety alerts broadcasting (GOV)
│   │           ├── Community/            # Community events & policing (GOV)
│   │           ├── CaseManagement/       # Case lifecycle (GOV: Police/NSCDC)
│   │           ├── Evidence/             # Evidence & exhibit tracking (GOV)
│   │           ├── Suspect/              # Suspect/POI registry (GOV)
│   │           ├── Weapons/              # Firearms & ammunition (GOV)
│   │           ├── EquipmentTracking/    # Equipment checkout (GOV)
│   │           ├── UseOfForce/           # Use of force reporting (GOV)
│   │           ├── CrimeStatistics/      # Crime stats & analytics (GOV)
│   │           ├── Budget/               # Budget allocation (GOV)
│   │           ├── Stipend/              # Volunteer stipends (GOV)
│   │           └── InterAgency/          # Cross-agency coordination (GOV)
│   │
│   ├── web/
│   │   └── src/app/features/
│   │       ├── ... (existing features)
│   │       ├── ranks/                    # GOV
│   │       ├── org-structure/            # GOV
│   │       ├── citizen-reports/          # GOV
│   │       ├── safety-alerts/            # GOV
│   │       ├── community/               # GOV
│   │       ├── cases/                    # GOV
│   │       ├── evidence/                 # GOV
│   │       ├── weapons/                  # GOV
│   │       ├── equipment/               # GOV
│   │       ├── use-of-force/            # GOV
│   │       ├── crime-stats/             # GOV
│   │       ├── budget/                  # GOV
│   │       ├── stipends/               # GOV
│   │       └── public-dashboard/        # GOV (public transparency)
│   │
│   ├── mobile-citizen/                  # NativeScript + Angular (GOV only)
│   └── ... (existing apps)
│
└── libs/
    ├── shared-types/
    │   └── terminology/                 # Terminology maps per tenant type
    └── ... (existing libs)
```

---

## 14. Implementation Rules (GOV Additions)

1. **Base Guard51 phases (0-7) complete before GOV phases (9-10).** GOV builds on top of a stable core.
2. **Terminology engine built in Phase 9A is non-breaking.** Existing private security tenants see zero UI changes.
3. **Feature gates enforce tenant type.** GOV modules literally do not exist for private security tenants.
4. **Citizen app goes on app stores.** Unlike other apps, this needs public accessibility.
5. **PostGIS jurisdiction boundaries are critical.** Auto-routing depends on spatial queries.
6. **Evidence chain of custody is immutable.** Append-only — no edits, no deletes.
7. **Weapons logs are audit-critical.** Full logging, no soft deletes.
8. **Public dashboard data is aggregated only.** No PII or case details exposed publicly.
9. **Seed data per organization type.** State Police gets rank seeds, NSCDC gets different ranks, Neighborhood Watch gets level seeds.
10. **Phase 8 (Analytics/AI) runs in parallel** with GOV phases since it's ongoing work.

---

## 15. Next Step

Complete **Guard51 Phases 0-7** first, then begin **Phase 9A: Tenant Type System & Terminology Engine**.
