# Guard51 Deployment Runbook & Production Guide
## Domain: guard51.com | Company: DOSTHQ Limited

---

## Table of Contents

1. Architecture Overview
2. Server Requirements
3. aaPanel Setup (Backend)
4. Cloudflare Setup (Frontend)
5. Database Setup (PostgreSQL + PostGIS)
6. Redis Setup
7. Node.js Realtime Server
8. CI/CD Pipeline (GitHub Actions)
9. SSL & Domain Configuration
10. Environment Variables
11. Production Checklist
12. Competitor Analysis (vs securityguard.app)
13. Nigeria Market Readiness

---

## 1. Architecture Overview

```
guard51.com (Cloudflare Pages)
    ├── app.guard51.com     → Angular 19 Web App (Cloudflare Pages)
    ├── api.guard51.com     → Slim 4 PHP API (aaPanel / Nginx + PHP-FPM)
    ├── realtime.guard51.com → Node.js WebSocket (PM2)
    └── cdn.guard51.com     → File storage / uploads

Ubuntu Server (aaPanel)
    ├── Nginx (reverse proxy)
    ├── PHP 8.3-FPM
    ├── PostgreSQL 16 + PostGIS
    ├── Redis 7
    ├── Node.js 20 (PM2)
    └── Certbot SSL
```

---

## 2. Server Requirements

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| CPU | 2 vCPU | 4 vCPU |
| RAM | 4 GB | 8 GB |
| Storage | 40 GB SSD | 80 GB SSD |
| OS | Ubuntu 22.04 / 24.04 LTS | Ubuntu 24.04 LTS |
| Bandwidth | 1 TB/mo | Unmetered |

**Recommended Nigerian VPS providers:** DigitalOcean (Lagos region), Contabo, Hetzner

---

## 3. aaPanel Setup (Backend API)

### 3.1 Install aaPanel
```bash
# SSH into your server
wget -O install.sh https://www.aapanel.com/script/install_7.0_en.sh && bash install.sh aapanel
```

### 3.2 Install Software via aaPanel
- **Nginx** 1.24+
- **PHP** 8.3 (with extensions: pdo_pgsql, pgsql, redis, mbstring, json, openssl, curl, gd, zip, intl, bcmath)
- **PostgreSQL** 16
- **Redis** 7
- **PM2** (Node.js process manager)

### 3.3 Create Website in aaPanel
1. Go to **Website → Add Site**
2. Domain: `api.guard51.com`
3. PHP Version: 8.3
4. Root directory: `/www/wwwroot/guard51/apps/api/public`

### 3.4 Nginx Configuration for API
```nginx
server {
    listen 80;
    listen 443 ssl http2;
    server_name api.guard51.com;

    root /www/wwwroot/guard51/apps/api/public;
    index index.php;

    # SSL (managed by aaPanel / Certbot)
    ssl_certificate /www/server/panel/vhost/cert/api.guard51.com/fullchain.pem;
    ssl_certificate_key /www/server/panel/vhost/cert/api.guard51.com/privkey.pem;

    # CORS Headers
    add_header Access-Control-Allow-Origin "https://app.guard51.com" always;
    add_header Access-Control-Allow-Methods "GET, POST, PUT, PATCH, DELETE, OPTIONS" always;
    add_header Access-Control-Allow-Headers "Authorization, Content-Type, X-Tenant-ID, X-Request-ID" always;
    add_header Access-Control-Allow-Credentials "true" always;

    # Handle OPTIONS preflight
    if ($request_method = OPTIONS) {
        return 204;
    }

    # PHP-FPM
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/tmp/php-cgi-83.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Rate limiting
    limit_req_zone $binary_remote_addr zone=api:10m rate=30r/s;
    limit_req zone=api burst=50 nodelay;

    # Max upload size (for photos, documents)
    client_max_body_size 50M;

    # Deny access to sensitive files
    location ~ /\.(env|git|htaccess) { deny all; }
    location ~ ^/(config|migrations|tests|vendor) { deny all; }
}
```

### 3.5 Deploy Backend Code
```bash
cd /www/wwwroot
git clone https://github.com/surdbells/guard51.git
cd guard51/apps/api

# Install dependencies
composer install --no-dev --optimize-autoloader

# Copy environment file
cp .env.example .env
# EDIT .env with production values!
nano .env

# Set permissions
chown -R www:www /www/wwwroot/guard51
chmod -R 755 /www/wwwroot/guard51
chmod 600 /www/wwwroot/guard51/apps/api/.env

# Run migrations
php vendor/bin/doctrine-migrations migrate --no-interaction

# Run seeders (initial data)
php bin/seed.php
```

---

## 4. Cloudflare Setup (Frontend)

### 4.1 Domain Configuration
1. Add `guard51.com` to Cloudflare
2. Update nameservers at your domain registrar

### 4.2 DNS Records

| Type | Name | Content | Proxy |
|------|------|---------|-------|
| A | api | YOUR_SERVER_IP | Yes (Orange cloud) |
| A | realtime | YOUR_SERVER_IP | No (Grey cloud) |
| CNAME | app | guard51-web.pages.dev | Yes |
| CNAME | www | app.guard51.com | Yes |
| MX | @ | (your email provider) | - |

### 4.3 Cloudflare Pages Deployment
```bash
# In the Angular web app directory
cd apps/web

# Build for production
ng build --configuration=production

# Deploy to Cloudflare Pages
npx wrangler pages deploy dist/guard51-web --project-name=guard51-web
```

### 4.4 Cloudflare Pages Settings
- **Build command:** `ng build --configuration=production`
- **Build output directory:** `dist/guard51-web`
- **Root directory:** `apps/web`
- **Environment variables:**
  - `API_URL` = `https://api.guard51.com/api/v1`
  - `WS_URL` = `wss://realtime.guard51.com`

### 4.5 Redirect Rules
```
# Page Rules
guard51.com/* → 301 redirect → https://app.guard51.com/$1
www.guard51.com/* → 301 redirect → https://app.guard51.com/$1
```

---

## 5. Database Setup

### 5.1 PostgreSQL via aaPanel
```bash
# Create database and user
sudo -u postgres psql

CREATE DATABASE guard51;
CREATE USER guard51 WITH ENCRYPTED PASSWORD 'YOUR_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON DATABASE guard51 TO guard51;
\c guard51
CREATE EXTENSION IF NOT EXISTS postgis;
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
GRANT ALL ON SCHEMA public TO guard51;
\q
```

### 5.2 Run Migrations
```bash
cd /www/wwwroot/guard51/apps/api
php vendor/bin/doctrine-migrations migrate --no-interaction
```

### 5.3 Backup Script
```bash
#!/bin/bash
# /root/scripts/backup-db.sh
DATE=$(date +%Y%m%d_%H%M)
BACKUP_DIR=/www/backup/database
mkdir -p $BACKUP_DIR

pg_dump -U guard51 guard51 | gzip > $BACKUP_DIR/guard51_$DATE.sql.gz

# Keep last 30 days
find $BACKUP_DIR -mtime +30 -name "*.sql.gz" -delete

echo "Backup complete: guard51_$DATE.sql.gz"
```

Add to crontab: `0 2 * * * /root/scripts/backup-db.sh`

---

## 6. Redis Setup

```bash
# Redis is installed via aaPanel
# Edit config for production
nano /www/server/redis/redis.conf

# Set:
maxmemory 256mb
maxmemory-policy allkeys-lru
requirepass YOUR_REDIS_PASSWORD

systemctl restart redis
```

---

## 7. Node.js Realtime Server

### 7.1 Setup
```bash
cd /www/wwwroot/guard51/apps/realtime
npm install --production

# Create ecosystem file for PM2
cat > ecosystem.config.js << 'EOF'
module.exports = {
  apps: [{
    name: 'guard51-realtime',
    script: 'dist/server.js',
    instances: 1,
    env_production: {
      NODE_ENV: 'production',
      PORT: 3001,
      REDIS_HOST: '127.0.0.1',
      REDIS_PORT: 6379,
      JWT_SECRET: 'SAME_AS_API_JWT_SECRET',
      CORS_ORIGIN: 'https://app.guard51.com'
    }
  }]
};
EOF

# Build and start
npm run build
pm2 start ecosystem.config.js --env production
pm2 save
pm2 startup
```

### 7.2 Nginx Proxy for WebSocket
```nginx
server {
    listen 443 ssl http2;
    server_name realtime.guard51.com;

    ssl_certificate /www/server/panel/vhost/cert/realtime.guard51.com/fullchain.pem;
    ssl_certificate_key /www/server/panel/vhost/cert/realtime.guard51.com/privkey.pem;

    location / {
        proxy_pass http://127.0.0.1:3001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_read_timeout 86400;
    }
}
```

---

## 8. CI/CD Pipeline (GitHub Actions)

### 8.1 Backend CI/CD (`.github/workflows/api.yml`)
```yaml
name: Guard51 API

on:
  push:
    branches: [main]
    paths: ['apps/api/**']

jobs:
  test:
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgis/postgis:16-3.4
        env:
          POSTGRES_DB: guard51_test
          POSTGRES_USER: guard51
          POSTGRES_PASSWORD: test_password
        ports: ['5432:5432']
        options: >-
          --health-cmd pg_isready --health-interval 10s --health-timeout 5s --health-retries 5
      redis:
        image: redis:7-alpine
        ports: ['6379:6379']

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP 8.3
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: pdo_pgsql, redis, mbstring, intl, bcmath
          coverage: none

      - name: Install dependencies
        run: |
          cd apps/api
          composer install --prefer-dist --no-progress

      - name: Run tests
        env:
          DB_HOST: localhost
          DB_NAME: guard51_test
          DB_USER: guard51
          DB_PASSWORD: test_password
          JWT_SECRET: test_jwt_secret_for_ci
        run: |
          cd apps/api
          vendor/bin/phpunit --testdox

  deploy:
    needs: test
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'

    steps:
      - uses: actions/checkout@v4

      - name: Deploy to server
        uses: appleboy/ssh-action@v1
        with:
          host: ${{ secrets.SERVER_HOST }}
          username: ${{ secrets.SERVER_USER }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            cd /www/wwwroot/guard51
            git pull origin main
            cd apps/api
            composer install --no-dev --optimize-autoloader
            php vendor/bin/doctrine-migrations migrate --no-interaction
            # Clear OPcache
            php -r "opcache_reset();" 2>/dev/null || true
            echo "API deployed at $(date)"
```

### 8.2 Frontend CI/CD (`.github/workflows/web.yml`)
```yaml
name: Guard51 Web

on:
  push:
    branches: [main]
    paths: ['apps/web/**']

jobs:
  deploy:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'
          cache-dependency-path: apps/web/package-lock.json

      - name: Install and build
        run: |
          cd apps/web
          npm ci
          ng build --configuration=production

      - name: Deploy to Cloudflare Pages
        uses: cloudflare/pages-action@v1
        with:
          apiToken: ${{ secrets.CLOUDFLARE_API_TOKEN }}
          accountId: ${{ secrets.CLOUDFLARE_ACCOUNT_ID }}
          projectName: guard51-web
          directory: apps/web/dist/guard51-web
```

### 8.3 GitHub Secrets Required

| Secret | Description |
|--------|-------------|
| `SERVER_HOST` | Your server IP |
| `SERVER_USER` | SSH username (root or deploy user) |
| `SSH_PRIVATE_KEY` | SSH private key for deployment |
| `CLOUDFLARE_API_TOKEN` | Cloudflare API token (Pages edit permission) |
| `CLOUDFLARE_ACCOUNT_ID` | Cloudflare account ID |

---

## 9. SSL & Domain Configuration

### 9.1 SSL Certificates
- **api.guard51.com** → aaPanel auto-SSL (Let's Encrypt)
- **realtime.guard51.com** → aaPanel auto-SSL (Let's Encrypt)
- **app.guard51.com** → Cloudflare automatic SSL

### 9.2 Cloudflare SSL Settings
- SSL/TLS mode: **Full (Strict)**
- Always Use HTTPS: **On**
- Minimum TLS Version: **1.2**
- Automatic HTTPS Rewrites: **On**

---

## 10. Environment Variables

### Production `.env` (api.guard51.com)
```env
APP_NAME=Guard51
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.guard51.com

DB_HOST=127.0.0.1
DB_PORT=5432
DB_NAME=guard51
DB_USER=guard51
DB_PASSWORD=<STRONG_PASSWORD>

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

JWT_SECRET=<64_CHAR_RANDOM>
JWT_ACCESS_TTL=900
JWT_REFRESH_TTL=604800

PAYSTACK_SECRET_KEY=sk_live_<KEY>
PAYSTACK_PUBLIC_KEY=pk_live_<KEY>

ZEPTOMAIL_API_KEY=<KEY>
ZEPTOMAIL_FROM_EMAIL=noreply@guard51.com

TERMII_API_KEY=<KEY>
TERMII_SENDER_ID=Guard51

STORAGE_PATH=/www/wwwroot/guard51/storage
REALTIME_URL=wss://realtime.guard51.com
TOTP_ISSUER=Guard51
LOG_LEVEL=warning
```

### Angular Environment (apps/web/src/environments/environment.prod.ts)
```typescript
export const environment = {
  production: true,
  apiUrl: 'https://api.guard51.com/api/v1',
  wsUrl: 'wss://realtime.guard51.com',
  appName: 'Guard51',
};
```

---

## 11. Production Checklist

### Pre-Launch
- [ ] All migrations run successfully
- [ ] .env production values set (no defaults!)
- [ ] JWT secret is strong (64+ random chars)
- [ ] DB password is strong
- [ ] APP_DEBUG = false
- [ ] CORS origin set to app.guard51.com only
- [ ] File permissions correct (600 for .env, 755 for dirs)
- [ ] Nginx deny rules for /config, /tests, /.env
- [ ] SSL certificates valid and auto-renewing
- [ ] Redis password set
- [ ] Paystack webhook URL configured
- [ ] ZeptoMail verified sender domain
- [ ] Termii sender ID approved

### Post-Launch
- [ ] Database backup cron running (daily)
- [ ] Log rotation configured
- [ ] PM2 monitoring active
- [ ] Uptime monitoring (e.g., UptimeRobot for api.guard51.com)
- [ ] Error tracking (Sentry or similar)
- [ ] Test login flow end-to-end
- [ ] Test subscription + payment flow
- [ ] Test guard mobile app GPS tracking
- [ ] Test panic button WebSocket delivery

---

## 12. Competitor Analysis: Guard51 vs securityguard.app

### Feature Comparison

| Feature | securityguard.app | Guard51 | Advantage |
|---------|------------------|---------|-----------|
| Guard Tour (NFC/QR) | ✅ | ✅ | Tie |
| GPS Tracking + Geofence | ✅ | ✅ PostGIS | Guard51 |
| Scheduling | ✅ | ✅ Skill-based | Guard51 |
| Time Clock | ✅ | ✅ GPS + Selfie | Guard51 |
| Incident Reporting | ✅ | ✅ + Auto-escalate | Guard51 |
| Client Portal | ✅ | ✅ + Mobile App | Guard51 |
| Invoicing | ✅ | ✅ + NGN VAT | Guard51 |
| Payroll | ❌ | ✅ Nigeria PAYE | Guard51 |
| Panic Button | ✅ | ✅ + WebSocket | Guard51 |
| Dispatcher Console | ❌ | ✅ | Guard51 |
| Visitor Management | ❌ | ✅ | Guard51 |
| Parking Manager | ❌ | ✅ | Guard51 |
| Vehicle Patrol | ❌ | ✅ | Guard51 |
| Guard License Tracking | ❌ | ✅ | Guard51 |
| 2FA Authentication | ❌ | ✅ TOTP | Guard51 |
| Audit Logging | ❌ | ✅ | Guard51 |
| Multi-tenant SaaS | ❌ (single-tenant) | ✅ | Guard51 |
| White-label | ❌ | ✅ | Guard51 |
| Desktop App | ❌ | ✅ Electron | Guard51 |
| GOV Edition | ❌ | ✅ | Guard51 |
| Mobile Apps | 1 (guard) | 4 (guard/client/supervisor/dispatcher) | Guard51 |
| Chat/Messaging | ✅ | ✅ WebSocket RT | Tie |
| Analytics/KPIs | Basic | ✅ Performance Index | Guard51 |
| Nigeria Localization | ❌ | ✅ (NGN, PAYE, Pidgin) | Guard51 |

### Guard51 Competitive Advantages for Nigeria Market

1. **Nigeria-first payroll** — PAYE tax brackets, pension (8%), NHF (2.5%) built-in
2. **NGN invoicing** — Naira currency, 7.5% VAT, bank account display on invoices
3. **Multi-tenant SaaS** — security companies sign up and manage everything themselves
4. **Self-distributed APK** — guards get the app via company-branded download page (no Play Store friction)
5. **GOV edition** — state police, LSNW, NSCDC, LG security committees
6. **Pidgin English** — i18n support for Nigerian Pidgin
7. **Low-bandwidth optimized** — offline-first mobile, HTTP polling fallback for GPS
8. **Bank transfer payments** — manual payment confirmation (Paystack + bank transfer + POS)
9. **WhatsApp integration ready** — via Termii/360dialog for client notifications

### Pricing Strategy (vs $5-15/guard/month international)

| Plan | Price (NGN) | Guards | Features |
|------|-------------|--------|----------|
| Starter | ₦15,000/mo | Up to 10 | Core ops |
| Professional | ₦45,000/mo | Up to 50 | + Invoicing, Reports |
| Business | ₦120,000/mo | Up to 200 | + Analytics, Client Portal |
| Enterprise | Custom | Unlimited | All features + white-label |

---

## 13. Nigeria Market Readiness

### Regulatory Compliance
- NSCDC licensing support (guard license entity)
- Nigeria Data Protection Act (NDPA) — data stored in Nigeria/Africa
- VAT compliance (7.5% on invoices)
- Payroll compliance (PAYE, pension, NHF)

### Payment Integration
- Paystack (cards, bank transfers, USSD)
- Manual bank transfer confirmation
- POS card payment recording
- Cheque payment recording

### Connectivity Considerations
- Offline-first mobile apps (queue + sync)
- HTTP polling fallback when WebSocket unavailable
- Image compression for low-bandwidth uploads
- Progressive loading on web dashboard

### Distribution Strategy
- Self-hosted APK distribution (bypass Play Store listing)
- aaPanel-based simple hosting (familiar to Nigerian developers)
- Cloudflare edge caching for fast global access
- Lagos-region VPS for low latency
