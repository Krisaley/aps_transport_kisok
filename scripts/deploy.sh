#!/bin/sh
set -eu
cd "${DEPLOY_PATH:-/opt/aps-transport}"
test -f .env
test -n "${RELEASE_TAG:-}"
mkdir -p .deploy backups
previous=""
test -f .deploy/current-release && previous=$(cat .deploy/current-release)
printf '%s' "$RELEASE_TAG" > .deploy/pending-release
export RELEASE_TAG
docker compose pull app web queue scheduler
docker compose run --rm app php artisan migrate --force
docker compose up -d --remove-orphans
if ! timeout 120 sh -c 'until curl -fsS http://127.0.0.1:${HTTP_PORT:-8080}/up >/dev/null; do sleep 3; done'; then
  echo "Health check failed; rolling back" >&2
  if [ -n "$previous" ]; then export RELEASE_TAG="$previous"; docker compose up -d --remove-orphans; fi
  exit 1
fi
printf '%s' "$RELEASE_TAG" > .deploy/current-release
rm -f .deploy/pending-release
docker image prune -f --filter until=168h
