#!/usr/bin/env bash

set -Eeuo pipefail

# ---------------------------------------------------------------------------
# APS Transport - Production Update/Deploy
#
# Run from the repository:
#
#   bash scripts/update.sh
#
# Or:
#
#   ./scripts/update.sh
# ---------------------------------------------------------------------------

APP_DIR="/opt/aps-transport"
BRANCH="main"
ENV_FILE=".env"
HEALTH_URL="http://127.0.0.1:8080/up"

cd "$APP_DIR"

echo
echo "============================================================"
echo " APS Transport Deployment"
echo "============================================================"
echo

# ---------------------------------------------------------------------------
# 1. Basic checks
# ---------------------------------------------------------------------------

echo "→ Checking deployment environment..."

if ! command -v git >/dev/null 2>&1; then
    echo "ERROR: git is not installed."
    exit 1
fi

if ! command -v docker >/dev/null 2>&1; then
    echo "ERROR: docker is not installed."
    exit 1
fi

if ! docker compose version >/dev/null 2>&1; then
    echo "ERROR: docker compose plugin is not available."
    exit 1
fi

if [ ! -f "$ENV_FILE" ]; then
    echo "ERROR: $ENV_FILE does not exist."
    exit 1
fi

# ---------------------------------------------------------------------------
# 2. Make sure the working tree is clean
# ---------------------------------------------------------------------------

echo "→ Checking Git working tree..."

if [ -n "$(git status --porcelain)" ]; then
    echo
    echo "ERROR: The Git working tree contains local changes."
    echo
    git status --short
    echo
    echo "Commit, stash, or discard these changes before deploying."
    exit 1
fi

# ---------------------------------------------------------------------------
# 3. Record current release for rollback
# ---------------------------------------------------------------------------

CURRENT_TAG="$(grep '^RELEASE_TAG=' "$ENV_FILE" | cut -d '=' -f2- || true)"

if [ -z "$CURRENT_TAG" ]; then
    CURRENT_TAG="unknown"
fi

echo "→ Current release: $CURRENT_TAG"

# ---------------------------------------------------------------------------
# 4. Create and verify a pre-deployment database backup
# ---------------------------------------------------------------------------

echo "→ Creating pre-deployment database backup..."

mkdir -p backups
BACKUP_STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
BACKUP_FILE="backups/transport-predeploy-${BACKUP_STAMP}.dump"

docker compose exec -T postgres sh -c \
    'PGPASSWORD="$POSTGRES_PASSWORD" pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" -Fc' \
    > "$BACKUP_FILE"

if [ ! -s "$BACKUP_FILE" ]; then
    echo "ERROR: Database backup is empty. Deployment stopped."
    exit 1
fi

sha256sum "$BACKUP_FILE" > "${BACKUP_FILE}.sha256"
sha256sum -c "${BACKUP_FILE}.sha256"

echo "✓ Backup created: $BACKUP_FILE"

# ---------------------------------------------------------------------------
# 5. Fetch and update repository
# ---------------------------------------------------------------------------

echo "→ Fetching latest changes from GitHub..."

git fetch origin "$BRANCH"

echo "→ Updating local $BRANCH branch..."

git checkout "$BRANCH"
git pull --ff-only origin "$BRANCH"

# ---------------------------------------------------------------------------
# 6. Determine new release tag
# ---------------------------------------------------------------------------

NEW_TAG="$(git rev-parse HEAD)"

echo
echo "→ New release:"
echo "  $NEW_TAG"
echo

if [ "$CURRENT_TAG" = "$NEW_TAG" ]; then
    echo "→ Repository is already on release $NEW_TAG."
    echo "  Deployment will continue to ensure containers and migrations are current."
fi

# ---------------------------------------------------------------------------
# 7. Update RELEASE_TAG in .env
# ---------------------------------------------------------------------------

echo "→ Updating RELEASE_TAG in $ENV_FILE..."

if grep -q '^RELEASE_TAG=' "$ENV_FILE"; then
    sed -i "s/^RELEASE_TAG=.*/RELEASE_TAG=$NEW_TAG/" "$ENV_FILE"
else
    printf '\nRELEASE_TAG=%s\n' "$NEW_TAG" >> "$ENV_FILE"
fi

echo "→ RELEASE_TAG=$NEW_TAG"

# ---------------------------------------------------------------------------
# 8. Pull release images
# ---------------------------------------------------------------------------

echo
echo "→ Pulling Docker images..."

if ! docker compose pull; then
    echo
    echo "ERROR: Failed to pull release images."
    echo "Restoring RELEASE_TAG=$CURRENT_TAG"

    if [ "$CURRENT_TAG" != "unknown" ]; then
        sed -i "s/^RELEASE_TAG=.*/RELEASE_TAG=$CURRENT_TAG/" "$ENV_FILE"
    fi

    exit 1
fi

# ---------------------------------------------------------------------------
# 9. Run additive database migrations
# ---------------------------------------------------------------------------

echo
echo "→ Running pending additive database migrations..."

docker compose run --rm app php artisan migrate --force

# ---------------------------------------------------------------------------
# 10. Run safe, idempotent production seeders
# ---------------------------------------------------------------------------

echo
echo "→ Updating production reference and permission data..."

docker compose run --rm app php artisan db:seed \
    --class='Database\Seeders\ProductionSeeder' \
    --force

# ---------------------------------------------------------------------------
# 11. Start/recreate containers
# ---------------------------------------------------------------------------

echo
echo "→ Starting application..."

docker compose --profile cloudflare up -d --remove-orphans

# ---------------------------------------------------------------------------
# 12. Restart queue workers gracefully
# ---------------------------------------------------------------------------

echo
echo "→ Restarting Laravel queue workers..."

docker compose exec -T app php artisan queue:restart || true
docker compose exec -T app php artisan permission:cache-reset

# ---------------------------------------------------------------------------
# 13. Clear/rebuild Laravel caches
# ---------------------------------------------------------------------------

echo
echo "→ Optimising Laravel..."

docker compose exec -T app php artisan optimize:clear
docker compose exec -T app php artisan optimize

# ---------------------------------------------------------------------------
# 14. Display container state
# ---------------------------------------------------------------------------

echo
echo "→ Container status:"
echo

docker compose ps

# ---------------------------------------------------------------------------
# 15. Health check
# ---------------------------------------------------------------------------

echo
echo "→ Waiting for application health check..."

MAX_ATTEMPTS=30
ATTEMPT=1

while [ "$ATTEMPT" -le "$MAX_ATTEMPTS" ]; do

    if curl -fsS "$HEALTH_URL" >/dev/null 2>&1; then
        echo
        echo "✓ Application health check passed."
        echo
        echo "============================================================"
        echo " Deployment successful"
        echo "============================================================"
        echo
        echo "Previous release: $CURRENT_TAG"
        echo "Current release:  $NEW_TAG"
        echo

        exit 0
    fi

    echo "  Health check attempt $ATTEMPT/$MAX_ATTEMPTS failed..."

    sleep 2
    ATTEMPT=$((ATTEMPT + 1))
done

# ---------------------------------------------------------------------------
# 16. Deployment failed - rollback Docker images
# ---------------------------------------------------------------------------

echo
echo "ERROR: Application failed health check."
echo

if [ "$CURRENT_TAG" = "unknown" ]; then
    echo "No previous RELEASE_TAG is available."
    echo "Automatic rollback cannot be performed."
    exit 1
fi

echo "→ Rolling back containers to:"
echo "  $CURRENT_TAG"

sed -i "s/^RELEASE_TAG=.*/RELEASE_TAG=$CURRENT_TAG/" "$ENV_FILE"

docker compose pull
docker compose --profile cloudflare up -d --remove-orphans

echo
echo "→ Previous containers restored."
echo
echo "IMPORTANT:"
echo "Database migrations and seeders have NOT been rolled back."
echo "Pre-deployment backup: $BACKUP_FILE"
echo
echo "Check logs with:"
echo
echo "  docker compose logs --tail=200"
echo

exit 1
