# Guard51 — Deployment Runbook

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Local Development (Docker Desktop)](#local-development-docker-desktop)
3. [Production Deployment (Linux + aaPanel)](#production-deployment-linux--aapanel)
4. [Post-Deployment Checklist](#post-deployment-checklist)
5. [Maintenance & Monitoring](#maintenance--monitoring)

---

## Prerequisites

### Required Software
- PHP 8.3+ with extensions: pdo_pgsql, redis, mbstring, openssl, gd, intl
- PostgreSQL 16+
- Redis 7+
- Node.js 20+ (for Angular build + WebSocket server)
- Composer 2.x
- npm 10+
- Git

### Required Accounts & Keys
- GitHub access to `github.com/surdbells/guard51.git`
- ENCRYPTION_KEY (32 bytes, base64-encoded): `openssl rand -base64 32`
- JWT_SECRET: `openssl rand -hex 32`
- ZeptoMail API key (email)
- Termii API key (SMS)
- Paystack secret key (payments)
- FCM credentials (push notifications)

---

## Local Development (Docker Desktop)

### Step 1: Clone & Setup

```bash
git clone https://github.com/surdbells/guard51.git
cd guard51
```

### Step 2: Docker Compose

Create `docker-compose.yml` in the project root:

```yaml
version: '3.8'

services:
  postgres:
    image: postgres:16-alpine
    environment:
      POSTGRES_DB: guard51
      POSTGRES_USER: guard51
      POSTGRES_PASSWORD: guard51_dev_password
    ports:
      - "5432:5432"
    volumes:
      - pg_data:/var/lib/postgresql/data

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

  api:
    build:
      context: ./apps/api
      dockerfile: Dockerfile
    ports:
      - "8080:80"
    environment:
      APP_ENV: development
      APP_DEBUG: "true"
      DB_HOST: postgres
      DB_PORT: "5432"
      DB_NAME: guard51
      DB_USER: guard51
      DB_PASSWORD: guard51_dev_password
      REDIS_HOST: redis
      REDIS_PORT: "6379"
      JWT_SECRET: dev-jwt-secret-change-in-production
      ENCRYPTION_KEY: ""
      CORS_ORIGIN: "http://localhost:4200"
    depends_on:
      - postgres
      - redis
    volumes:
      - ./apps/api:/var/www/html

  web:
    build:
      context: ./apps/web
      dockerfile: Dockerfile
    ports:
      - "4200:80"
    depends_on:
      - api

  websocket:
    build:
      context: ./apps/ws
      dockerfile: Dockerfile
    ports:
      - "3001:3001"
    environment:
      JWT_SECRET: dev-jwt-secret-change-in-production
      REDIS_HOST: redis
    depends_on:
      - redis

volumes:
  pg_data:
```

### Step 3: API Dockerfile

Create `apps/api/Dockerfile`:

```dockerfile
FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    libpq-dev libzip-dev libicu-dev libgd-dev unzip git \
    && docker-php-ext-install pdo pdo_pgsql intl zip gd opcache \
    && pecl install redis && docker-php-ext-enable redis

RUN a2enmod rewrite headers
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --optimize-autoloader

EXPOSE 80
```

Create `apps/api/docker/apache.conf`:

```apache
<VirtualHost *:80>
    DocumentRoot /var/www/html/public
    <Directory /var/www/html/public>
        AllowOverride All
        Require all granted
        FallbackResource /index.php
    </Directory>
</VirtualHost>
```

### Step 4: Web Dockerfile

Create `apps/web/Dockerfile`:

```dockerfile
FROM node:20-alpine AS build
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npx ng build --configuration=production

FROM nginx:alpine
COPY --from=build /app/dist/guard51-web/browser /usr/share/nginx/html
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
```

Create `apps/web/docker/nginx.conf`:

```nginx
server {
    listen 80;
    root /usr/share/nginx/html;
    index index.html;
    location / {
        try_files $uri $uri/ /index.html;
    }
}
```

### Step 5: Start

```bash
docker compose up -d

# Run migrations
docker compose exec api php vendor/bin/doctrine-migrations migrate --no-interaction

# Seed demo data
docker compose exec api php bin/seed.php

# Access:
# Web:    http://localhost:4200
# API:    http://localhost:8080/api/health
# Login:  admin@shieldforce.demo / ShieldForce@2026
```

---

## Production Deployment (Linux + aaPanel)

### Step 1: Server Setup (aaPanel)

```bash
# Install aaPanel (Ubuntu 22.04+)
wget -O install.sh http://www.aapanel.com/script/install-ubuntu_6.0_en.sh && bash install.sh

# In aaPanel web UI, install:
# - Nginx 1.24+
# - PHP 8.3 (with extensions: pgsql, pdo_pgsql, redis, mbstring, openssl, gd, intl, zip)
# - PostgreSQL 16
# - Redis 7
# - Node.js 20 (via App Store)
# - Supervisor (via App Store)
```

### Step 2: Create Database

```bash
# In aaPanel → Database → PostgreSQL → Add Database
# Database: guard51wpetd7900
# User: guard51wpetd7900
# Password: <generate secure password>
```

### Step 3: Create Websites

In aaPanel → Website → Add Site:

1. **API**: `api.guard51.com` → PHP 8.3 → set document root to `apps/api/public`
2. **Web**: `app.guard51.com` → Static site → set root to `apps/web/dist/guard51-web/browser`

### Step 4: Clone & Install

```bash
cd /www/wwwroot
git clone https://github.com/surdbells/guard51.git guard51
cd guard51

# API dependencies
cd apps/api
composer install --no-dev --optimize-autoloader

# Create .env
cp .env.example .env
nano .env  # Fill in all values
```

### Step 5: Environment Configuration

Create `apps/api/.env`:

```env
APP_ENV=production
APP_DEBUG=false

# Database
DB_DRIVER=pdo_pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_NAME=guard51wpetd7900
DB_USER=guard51wpetd7900
DB_PASSWORD=<your-db-password>

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Security
JWT_SECRET=<openssl rand -hex 32>
ENCRYPTION_KEY=<openssl rand -base64 32>

# CORS
CORS_ORIGIN=https://app.guard51.com

# Email (ZeptoMail)
ZEPTOMAIL_TOKEN=<your-zeptomail-token>
ZEPTOMAIL_FROM=noreply@guard51.com

# SMS (Termii)
TERMII_API_KEY=<your-key>
TERMII_SENDER_ID=Guard51

# Payments (Paystack)
PAYSTACK_SECRET_KEY=<your-key>

# FCM (Push Notifications)
FCM_CREDENTIALS_PATH=/www/wwwroot/guard51/apps/api/config/fcm-credentials.json
```

### Step 6: Build Frontend

```bash
cd /www/wwwroot/guard51/apps/web
npm ci
npx ng build --configuration=production
```

### Step 7: Run Migrations

```bash
cd /www/wwwroot/guard51/apps/api
php vendor/bin/doctrine-migrations migrate --no-interaction
php bin/seed.php  # Only on first deploy
```

### Step 8: Nginx Configuration

**API** (`api.guard51.com`):

```nginx
server {
    listen 80;
    server_name api.guard51.com;
    root /www/wwwroot/guard51/apps/api/public;
    index index.php;

    client_max_body_size 50M;

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/tmp/php-cgi-83.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # SSL managed by aaPanel/Let's Encrypt
}
```

**Web** (`app.guard51.com`):

```nginx
server {
    listen 80;
    server_name app.guard51.com;
    root /www/wwwroot/guard51/apps/web/dist/guard51-web/browser;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff2?)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # SSL managed by aaPanel/Let's Encrypt
}
```

### Step 9: SSL Certificates

In aaPanel → Website → each site → SSL → Let's Encrypt → Issue certificate

### Step 10: Supervisor (Background Workers)

Create `/etc/supervisor/conf.d/guard51-worker.conf`:

```ini
[program:guard51-worker]
command=php /www/wwwroot/guard51/apps/api/bin/worker.php
directory=/www/wwwroot/guard51/apps/api
autostart=true
autorestart=true
stderr_logfile=/var/log/guard51-worker.err.log
stdout_logfile=/var/log/guard51-worker.out.log
user=www
numprocs=2
process_name=%(program_name)s_%(process_num)02d
```

Create `/etc/supervisor/conf.d/guard51-websocket.conf`:

```ini
[program:guard51-ws]
command=node /www/wwwroot/guard51/apps/ws/server.js
directory=/www/wwwroot/guard51/apps/ws
autostart=true
autorestart=true
stderr_logfile=/var/log/guard51-ws.err.log
stdout_logfile=/var/log/guard51-ws.out.log
user=www
```

```bash
supervisorctl reread
supervisorctl update
supervisorctl start all
```

### Step 11: Cron Jobs

```bash
# Backup (2 AM daily)
0 2 * * * /www/wwwroot/guard51/apps/api/bin/backup.sh >> /var/log/guard51-backup.log 2>&1
```

### Step 12: Deploy Script

Use for subsequent deployments:

```bash
#!/bin/bash
set -e
cd /www/wwwroot/guard51

echo "Pulling latest code..."
git pull origin main

echo "Installing API dependencies..."
cd apps/api && composer install --no-dev --optimize-autoloader

echo "Running migrations..."
php vendor/bin/doctrine-migrations migrate --no-interaction

echo "Building frontend..."
cd ../web && npm ci && npx ng build --configuration=production

echo "Restarting services..."
kill -USR2 $(pgrep -o php-fpm) 2>/dev/null || true
supervisorctl restart guard51-worker:*
supervisorctl restart guard51-ws

echo "Deploy complete!"
```

---

## Post-Deployment Checklist

- [ ] `https://api.guard51.com/api/health` returns `{"status":"ok"}`
- [ ] `https://app.guard51.com` loads login page
- [ ] Login with test credentials works
- [ ] Dashboard loads with data
- [ ] SSL certificates are valid (green padlock)
- [ ] CORS allows requests from app.guard51.com
- [ ] Encryption key is set (phone numbers not showing `enc:...`)
- [ ] Email sending works (test forgot password)
- [ ] WebSocket connects (check browser console)
- [ ] Supervisor processes running: `supervisorctl status`
- [ ] Backup script runs: `bash apps/api/bin/backup.sh`

---

## Maintenance & Monitoring

### Logs

```bash
# API errors
tail -f /www/wwwroot/guard51/apps/api/var/logs/app.log

# Worker logs
tail -f /var/log/guard51-worker.out.log

# Nginx access
tail -f /www/wwwlogs/api.guard51.com.log
```

### Health Checks

```bash
# API
curl https://api.guard51.com/api/health

# Database
psql -h 127.0.0.1 -U guard51wpetd7900 -d guard51wpetd7900 -c "SELECT count(*) FROM tenants;"

# Redis
redis-cli ping

# Supervisor
supervisorctl status
```

### Backup Restore

```bash
pg_restore -h 127.0.0.1 -U guard51wpetd7900 -d guard51wpetd7900 --clean /var/backups/guard51/guard51_YYYYMMDD_HHMMSS.dump
```
