#!/bin/bash
# Guard51 Database Backup Script
# Run via cron: 0 2 * * * /www/wwwroot/guard51/apps/api/bin/backup.sh
set -e

BACKUP_DIR="/var/backups/guard51"
RETENTION_DAYS=30
DB_NAME="${DB_NAME:-guard51wpetd7900}"
DB_USER="${DB_USER:-guard51wpetd7900}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-5432}"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

mkdir -p "$BACKUP_DIR"

echo "[Backup] Starting at $(date)"

# Full database dump
PGPASSWORD="$DB_PASSWORD" pg_dump -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" \
  --format=custom --compress=9 \
  -f "$BACKUP_DIR/guard51_${TIMESTAMP}.dump"

echo "[Backup] Created: guard51_${TIMESTAMP}.dump ($(du -h "$BACKUP_DIR/guard51_${TIMESTAMP}.dump" | cut -f1))"

# Clean old backups
find "$BACKUP_DIR" -name "guard51_*.dump" -mtime +$RETENTION_DAYS -delete
echo "[Backup] Cleaned backups older than $RETENTION_DAYS days"

# Count remaining
REMAINING=$(ls "$BACKUP_DIR"/guard51_*.dump 2>/dev/null | wc -l)
echo "[Backup] Done. $REMAINING backup(s) retained."
