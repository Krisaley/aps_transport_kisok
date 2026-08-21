#!/bin/sh
set -eu
mkdir -p /backups
while true; do
  stamp=$(date -u +%Y%m%dT%H%M%SZ)
  PGPASSWORD="$POSTGRES_PASSWORD" pg_dump -h postgres -U "$POSTGRES_USER" -d "$POSTGRES_DB" -Fc -f "/backups/transport-$stamp.dump"
  sha256sum "/backups/transport-$stamp.dump" > "/backups/transport-$stamp.dump.sha256"
  find /backups -type f -mtime +"${BACKUP_RETENTION_DAYS:-30}" -delete
  sleep 86400
done
