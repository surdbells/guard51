# Guard51 — Module Testing Guide

> Step-by-step checklist for testing all modules across all user accounts.

## Test Accounts

| Role | Email | Password | Portal |
|------|-------|----------|--------|
| Super Admin | admin@guard51.com | Guard51@Admin2026 | `/admin/dashboard` |
| Company Admin | admin@shieldforce.demo | ShieldForce@2026 | `/dashboard` |
| Guard | guard@shieldforce.demo | ShieldForce@2026 | `/portal` |
| Client | client@shieldforce.demo | ShieldForce@2026 | `/client-portal` |

---

## Phase 1: Authentication & Access Control

### 1.1 Login Flow
- [ ] Login with Super Admin credentials → redirected to `/admin/dashboard`
- [ ] Login with Company Admin → redirected to `/dashboard`
- [ ] Login with Guard → redirected to `/portal`
- [ ] Login with Client → redirected to `/client-portal`
- [ ] Invalid credentials → error toast shown
- [ ] Forgot password → email sent (check ZeptoMail logs)
- [ ] Password reset with valid token → success
- [ ] Token expiry → shows error

### 1.2 Role-Based Access
- [ ] Company Admin sees all 17+ sidebar items
- [ ] Guard only sees Guard Portal items
- [ ] Client only sees Client Portal items
- [ ] Super Admin sees SaaS Admin sidebar
- [ ] Accessing unauthorized route → silent redirect to correct portal

---

## Phase 2: Company Admin — Core Modules

### 2.1 Dashboard
- [ ] KPI cards load with data (or zeros for new tenant)
- [ ] All 4 tabs render: Overview, Operations, Analytics, Finance
- [ ] Charts render (bar, line, donut)
- [ ] "Refresh" button reloads data
- [ ] "Add Site" button navigates to `/sites/new`

### 2.2 Guards Module (`/guards`)
- [ ] Guards list loads with data table
- [ ] Search filters guards by name
- [ ] Status filter works (Active/Inactive/All)
- [ ] "Add Guard" → opens guard form
- [ ] Guard form: fill all 18+ fields, save → success toast
- [ ] Searchable dropdown works for State (37 Nigerian states)
- [ ] Searchable dropdown works for Bank (27 banks)
- [ ] Guard detail page loads with 4 tabs
- [ ] Profile tab: shows personal info, account info, emergency contact
- [ ] Performance tab: shows GPI scores with progress bars
- [ ] Documents tab: upload file (PDF/image) → success
- [ ] Documents tab: preview and download buttons work
- [ ] Edit guard → pre-filled form → save changes

### 2.3 Sites Module (`/sites`)
- [ ] Sites list loads
- [ ] "Add Site" → site form with client searchable dropdown
- [ ] State dropdown is searchable
- [ ] Geofence radius field works
- [ ] Save site → success

### 2.4 Clients Module (`/clients`)
- [ ] Client list loads
- [ ] "Add Client" → form with searchable industry and state dropdowns
- [ ] Billing type dropdown (per_guard, fixed, custom)
- [ ] Contract date fields
- [ ] Save client → success

### 2.5 Scheduling (`/scheduling`)
- [ ] Weekly calendar view loads
- [ ] Site and Guard filter dropdowns are searchable
- [ ] Create shift → site and guard are searchable
- [ ] Shift templates tab → create template
- [ ] Bulk wizard → site dropdown is searchable

### 2.6 Attendance (`/attendance`)
- [ ] Attendance records load
- [ ] Search by guard name works
- [ ] Date filter works
- [ ] Export CSV downloads file

### 2.7 Incidents (`/incidents`)
- [ ] Incidents list loads
- [ ] Severity filter works
- [ ] "Report Incident" → form with searchable site dropdown
- [ ] Incident type dropdown (9 types)
- [ ] File upload (evidence) works
- [ ] Submit → success

### 2.8 Reports (`/reports`)
- [ ] Reports list loads
- [ ] DAR (Daily Activity Report) creation → searchable site dropdown
- [ ] Export CSV works

### 2.9 Visitors (`/visitors`)
- [ ] Visitor log loads
- [ ] Schedule visit → searchable site dropdown
- [ ] Purpose dropdown works
- [ ] Notification checkboxes (Email, SMS)

### 2.10 Invoicing (`/invoices`)
- [ ] Invoice list loads
- [ ] Create invoice → line items (add/remove)
- [ ] Tax rate calculation
- [ ] Invoice detail → payment recording
- [ ] Export/Print works

### 2.11 Tours (`/tours`)
- [ ] Tour routes list loads
- [ ] Create route → searchable site dropdown
- [ ] Checkpoint management

### 2.12 Passdowns (`/passdowns`)
- [ ] Passdown list loads with status filter
- [ ] Create passdown → searchable site dropdown
- [ ] Priority dropdown (Low/Medium/High)

### 2.13 Parking (`/parking`)
- [ ] Parking entries load
- [ ] Log entry → purpose dropdown
- [ ] Entry/exit tracking

### 2.14 Vehicle Patrol (`/vehicle-patrol`)
- [ ] Routes list loads
- [ ] Create route → searchable site dropdown

### 2.15 Licenses (`/licenses`)
- [ ] License list loads with expiry tracking
- [ ] Add license → searchable guard dropdown
- [ ] License type dropdown

### 2.16 Chat (`/chat`)
- [ ] Conversation list loads
- [ ] Create conversation
- [ ] Send/receive messages
- [ ] Unread badge updates

### 2.17 Users / Team Management (`/users`)
- [ ] User list loads
- [ ] Role dropdown per user → change role saves
- [ ] "Invite User" → sends invitation
- [ ] "Roles & Permissions" tab → 5 system roles shown
- [ ] Create custom role → name, color, 20-module permission checkboxes
- [ ] Edit/delete custom roles

### 2.18 Settings (`/settings`)
- [ ] Company Profile tab: edit name, email, phone, state, address
- [ ] Branding tab: logo upload (light/dark), colors, website, support info
- [ ] Notifications tab: toggle email/SMS/push preferences

### 2.19 Profile (`/profile`)
- [ ] Personal Info tab: name, phone displayed correctly
- [ ] Security tab: change password (validate policy)
- [ ] Preferences tab: notification toggles

---

## Phase 3: Guard Portal

- [ ] Login as guard → `/portal`
- [ ] Dashboard shows today's shift info
- [ ] Clock In/Out works
- [ ] Report incident
- [ ] Submit passdown
- [ ] View tour checkpoints
- [ ] Chat accessible

---

## Phase 4: Client Portal

- [ ] Login as client → `/client-portal`
- [ ] Guard Activity tab: shows recent clock activity
- [ ] Reports tab: lists reports for client's sites
- [ ] Invoices tab: lists invoices
- [ ] Incidents tab: lists incidents at client's sites
- [ ] Visitors tab: schedule visit with access code generation
- [ ] Employees tab: add employee with 5 permission checkboxes
- [ ] Attendance tab: shows time clock records

---

## Phase 5: Super Admin (SaaS)

### 5.1 Dashboard (`/admin/dashboard`)
- [ ] 8 KPI cards load
- [ ] Recent companies listed
- [ ] Quick actions work

### 5.2 Company Management (`/admin/tenants`)
- [ ] Tenant list with search/filter/pagination
- [ ] "Provision Company" → modal with company details, admin account, plan
- [ ] Suspend/Reactivate company
- [ ] Subscription update modal
- [ ] Delete company

### 5.3 Subscriptions & Plans (`/admin/subscriptions`)
- [ ] Plans tab: pricing cards with module pills
- [ ] Create/edit/delete plan
- [ ] Active subscriptions table
- [ ] Cancel subscription

### 5.4 Feature Flags (`/admin/features`)
- [ ] Feature toggle table loads
- [ ] Enable/disable features

### 5.5 Support (`/admin/support`)
- [ ] Ticket list with stats
- [ ] Resolve/close tickets

### 5.6 Analytics (`/admin/analytics`)
- [ ] 8 KPIs load
- [ ] Company distribution chart
- [ ] Platform health metrics

### 5.7 App Distribution (`/admin/apps`)
- [ ] Releases list with platform filter
- [ ] Upload new release (file upload)

### 5.8 Settings (`/admin/settings`)
- [ ] 5 tabs load (API, Email, SMS, Payment, General)

---

## Phase 6: Cross-Cutting Concerns

### 6.1 Theme
- [ ] Toggle light → dark → light (header sun/moon icon)
- [ ] Dark mode: all surfaces, text, borders update correctly
- [ ] Persist across page reload

### 6.2 Mobile Responsive
- [ ] Bottom nav bar visible on mobile
- [ ] Hamburger menu opens sidebar overlay
- [ ] "More" button opens bottom sheet with 16 module icons
- [ ] Chat FAB positioned above bottom nav (not overlapping)
- [ ] Tables scroll horizontally
- [ ] Modals are full-width on small screens

### 6.3 Search
- [ ] ⌘K opens command palette
- [ ] Search filters 26 pages
- [ ] Navigate to selected result

### 6.4 Encryption
- [ ] Guard phone numbers display correctly (not `enc:...`)
- [ ] Bank account numbers display correctly
- [ ] Emergency contact phone displays correctly

### 6.5 Toast Notifications
- [ ] Success toast: light green background, visible on both themes
- [ ] Error toast: light red background
- [ ] Dismiss button works

### 6.6 Confirm Dialogs
- [ ] Delete actions show custom confirm dialog (no browser prompt)
- [ ] Cancel button closes without action
- [ ] Confirm button executes action

---

## Phase 7: API Endpoints Smoke Test

```bash
# Login
curl -X POST https://api.guard51.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@shieldforce.demo","password":"ShieldForce@2026"}'

# Dashboard
curl https://api.guard51.com/api/v1/dashboard/stats -H "Authorization: Bearer <token>"

# Guards list
curl https://api.guard51.com/api/v1/guards -H "Authorization: Bearer <token>"

# Health check
curl https://api.guard51.com/api/health
```
