# Guard51 Changelog

## v1.0.0 (April 2026) — Production Launch

### Platform
- Multi-tenant SaaS with row-level isolation (PostgreSQL + Doctrine TenantFilter)
- 145 entities, 41 services, 400+ API endpoints, 261 tests
- AES-256-GCM encryption at rest for PII (phone, bank accounts, addresses)
- JWT authentication with refresh tokens + 2FA (TOTP)
- RBAC with 6 system roles + custom role CRUD with 20-module permission matrix
- Redis queue system for async notifications (email, SMS, push)
- WebSocket server for real-time guard tracking + panic alerts
- FCM push notifications (Android + iOS)
- Rate limiting, CSRF protection, security headers (HSTS, CSP, X-Frame-Options)
- CI/CD pipeline (GitHub Actions) + zero-downtime deploy script
- GDPR/NDPR compliance: data export + account anonymization

### Guard Operations
- GPS live tracking with 15-second intervals
- Geofencing with automatic alerts
- Shift scheduling with templates, conflict detection, swap requests
- Time clock with GPS verification + attendance reconciliation
- Incident reporting (9 types, severity, GPS, evidence upload)
- Panic button with real-time dispatch notification
- Tour checkpoint scanning (QR/NFC/Virtual)
- Passdown logs with priority + site assignment
- Guard Performance Index (GPI)

### Business & Billing
- Client management with contracts, billing types, industry tracking
- Invoicing with line items, tax, PDF export, Paystack integration
- Payroll with overtime multipliers, pay periods, payslips
- Visitor management with appointment scheduling + access codes
- Vehicle patrol with route management + plate reads
- Parking management with entry/exit + occupancy tracking

### Portals
- Company Admin: 32 modules, searchable dropdowns, data tables, export
- Guard Portal: shifts, reports, incidents, passdowns, tours
- Client Portal: guard tracking, reports, invoices, visitor scheduling, employee onboarding
- SaaS Admin: tenant provisioning, plans, features, analytics, support

### SaaS Platform
- Tenant provisioning workflow (company + admin + subscription)
- Subscription plans with module-level access control
- Feature flags with sitewide enable/disable
- Platform analytics: MRR, ARR, growth, distribution
- Support ticket system + help center (8 seeded articles)
- App distribution with file upload

### Mobile & UX
- NativeScript mobile app (login, clock, GPS, panic, incidents, QR scan, offline queue)
- PWA manifest for installable web app
- Searchable dropdown component across 15+ forms
- Custom confirm dialogs (no browser prompts)
- Language dropdown (English + Nigerian Pidgin)
- Toast notifications (light colors, theme-immune)
- Mobile responsive design (tables, grids, modals)
- Dark mode support
