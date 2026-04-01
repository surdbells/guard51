#!/bin/bash
# Guard51 PostgreSQL Daily Backup Script
# Usage: ./backup.sh
# Recommended crontab: 0 3 * * * /www/wwwroot/guard51/apps/api/bin/backup.sh

BACKUP_DIR="/var/backups/guard51"
DB_NAME="guard51"
DB_USER="guard51"
DB_PORT="5433"
RETENTION_DAYS=30
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/guard51_${TIMESTAMP}.sql.gz"

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Dump and compress
echo "[$(date)] Starting backup..."
pg_dump -U "$DB_USER" -p "$DB_PORT" "$DB_NAME" | gzip > "$BACKUP_FILE"

if [ $? -eq 0 ]; then
  SIZE=$(ls -lh "$BACKUP_FILE" | awk '{print $5}')
  echo "[$(date)] Backup completed: $BACKUP_FILE ($SIZE)"
else
  echo "[$(date)] ERROR: Backup failed!"
  exit 1
fi

# Remove old backups
echo "[$(date)] Cleaning backups older than ${RETENTION_DAYS} days..."
find "$BACKUP_DIR" -name "guard51_*.sql.gz" -mtime +$RETENTION_DAYS -delete

# List remaining backups
echo "[$(date)] Current backups:"
ls -lh "$BACKUP_DIR"/guard51_*.sql.gz 2>/dev/null | tail -5
echo "[$(date)] Done."
