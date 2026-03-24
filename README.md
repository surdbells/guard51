# Guard51

**Security Workforce & Operations Management SaaS Platform**

Guard51 is a cloud-based platform designed to help private security companies, state police, neighborhood watch organizations, and government security agencies digitize and automate their operations.

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Slim 4 (PHP 8.3) + Doctrine ORM 3 |
| Database | PostgreSQL 16 + PostGIS |
| Cache / Queue | Redis 7 |
| Real-time | Node.js + Socket.io (WebSocket sidecar) |
| Frontend | Angular 21 + Angular Material |
| Charts | Custom SVG components |
| Mobile | NativeScript + Angular (4 apps + Citizen app) |
| Desktop | Electron |
| Email | ZeptoMail API |
| SMS | Termii API |
| Payments | Paystack |

## Quick Start

```bash
# 1. Clone
git clone https://github.com/surdbells/guard51.git
cd guard51

# 2. Configure environment
cp .env.example .env
# Edit .env with your credentials

# 3. Start all services
docker compose up -d

# 4. Install PHP dependencies
docker compose exec api composer install

# 5. Run database migrations
docker compose exec api php vendor/bin/doctrine-migrations migrate

# 6. Verify
curl http://localhost:8080/api/health
```

## Project Structure

```
guard51/
├── apps/
│   ├── api/              # Slim 4 PHP Backend
│   ├── web/              # Angular 21 Frontend
│   ├── realtime/         # Node.js WebSocket Sidecar
│   └── desktop/          # Electron Shell
├── libs/
│   ├── shared-types/     # Interfaces, enums, DTOs
│   ├── shared-services/  # API, auth, feature services
│   ├── shared-state/     # Signal-based stores
│   ├── shared-ui/        # SVG chart components
│   └── shared-utils/     # Utilities
└── docker/               # Docker configs
```

## Development

```bash
# API logs
docker compose logs -f api

# Run PHP tests
docker compose exec api vendor/bin/phpunit

# Angular dev server (with hot reload)
docker compose --profile dev up web

# Realtime sidecar logs
docker compose logs -f realtime
```

## Implementation Phases

| Phase | Description | Status |
|-------|------------|--------|
| 0A | Project Setup & Dev Environment | 🔄 In Progress |
| 0B | Tenant & Multi-Tenancy Foundation | ⏳ Pending |
| 0C | Authentication & Authorization | ⏳ Pending |
| 0D | Feature Module & Subscription Engine | ⏳ Pending |
| 0E | Tenant Onboarding & Management | ⏳ Pending |
| 0F | App Distribution Platform | ⏳ Pending |
| 0G | Super Admin Console | ⏳ Pending |
| 0H | Admin Angular Shell + SVG Charts | ⏳ Pending |

## License

Proprietary — DOSTHQ Limited
