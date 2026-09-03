#!/usr/bin/env bash
#
# SEWA HOSPITALITY — release artifact builder + deploy helper
#
# Make executable once:   chmod +x deploy.sh
# Build an artifact:      ./deploy.sh
# Build + upload:         TARGET_HOST=user@host TARGET_PATH=/home/uXXXX ./deploy.sh
#
# LOCKED DECISION (sewdocs/03-technical-specs/01-stack-and-dependencies.md §2.7
# and 06-hosting-deployment.md §1): the release artifact is built LOCALLY or in
# CI — the server NEVER runs Node. public/build/* (hashed assets + Vite
# manifest) and the --no-dev vendor/ tree are baked into the artifact on this
# machine; the host only extracts, migrates, optimizes and serves.
#
set -euo pipefail

# Always operate from the project root (the directory containing this script).
cd "$(dirname "$0")"

# Release id: short git SHA when in a repo, timestamped tag otherwise.
SHA="$(git rev-parse --short HEAD 2>/dev/null || echo "manual-$(date +%Y%m%d%H%M%S)")"
mkdir -p artifacts

echo "== SEWA HOSPITALITY — building release artifact ${SHA} =="

# 1) Production dependency tree. vendor/ ships INSIDE the artifact (the host
#    may have no SSH composer — 06-hosting-deployment.md §3).
composer install --no-dev --optimize-autoloader --no-interaction

# 2) Frontend build — LOCAL/CI ONLY, never on the server (locked decision).
npm ci
npm run build

# 3) Package the release: everything the host needs, nothing it must not get
#    (no dev tooling, no git history, no logs/sessions/cache, no env files —
#    13-testing-qa.md §2 gate 7: no .env in the artifact).
tar -czf "artifacts/release-${SHA}.tar.gz" \
  --exclude='./artifacts' \
  --exclude='./node_modules' \
  --exclude='./.git' \
  --exclude='./tests' \
  --exclude='./.github' \
  --exclude='./storage/logs/*' \
  --exclude='./storage/framework/cache/*' \
  --exclude='./storage/framework/sessions/*' \
  --exclude='./storage/framework/views/*' \
  --exclude='./storage/framework/testing/*' \
  --exclude='./storage/app/private/*' \
  --exclude='./storage/app/public/*' \
  --exclude='./storage/app/backups/*' \
  --exclude='./.pest-run.txt' \
  --exclude='./.env' \
  --exclude='./.env.*' \
  .

ARTIFACT="artifacts/release-${SHA}.tar.gz"
SIZE="$(du -h "${ARTIFACT}" | cut -f1)"
echo ""
echo "Artifact ready: ${ARTIFACT} (${SIZE})"

# 4) OPTIONAL upload — runs only when BOTH TARGET_HOST and TARGET_PATH are set.
if [ -n "${TARGET_HOST:-}" ] && [ -n "${TARGET_PATH:-}" ]; then
  echo "Uploading to ${TARGET_HOST}:${TARGET_PATH}/releases/ ..."
  rsync -az --delete "artifacts/release-${SHA}.tar.gz" "${TARGET_HOST}:${TARGET_PATH}/releases/"
  echo "Upload complete."
else
  echo "TARGET_HOST / TARGET_PATH not set — skipping upload (artifact stays in ./artifacts)."
fi

# 5) ALWAYS print the runbook (06-hosting-deployment.md §7). A release never
#    leaves this machine without its checklist.
cat <<RUNBOOK

==============================================================================
DEPLOY RUNBOOK — release ${SHA}
Source of truth: sewdocs/03-technical-specs/06-hosting-deployment.md §7
==============================================================================
PRE-DEPLOY
  [ ] CI green on this exact SHA (tests, dependency audit, route health,
      Vite build — 13-testing-qa.md §2 gates 1–4). Red = no deploy.
  [ ] Backup the DB: php artisan db:backup → dated dump in the backups dir
      (SEWA_BACKUPS_PATH, outside the app dir on the host).

DEPLOY
  [ ] Upload artifact → extract into releases/${SHA}/ on the host
  [ ] Update the current symlink → releases/${SHA}  (docroot serves current/public)
  [ ] php artisan migrate --force
  [ ] php artisan optimize
  [ ] php artisan storage:link
  [ ] php artisan queue:restart

POST-DEPLOY — 5-MINUTE GATE
  [ ] GET https://sewahospitality.com/               → 200 in < 800 ms
  [ ] GET https://api.sewahospitality.com/v1/health  → {"status":"ok"}
  [ ] GET https://sewahospitality.com/status         → scheduler + queue green
  [ ] Submit a test lead → arrives in admin + ack email (money path — live from M3)
  [ ] Sentry: mark release ${SHA}; Pulse shows the deploy marker
  [ ] Cloudflare: purge HTML only (hashed build/media assets are never purged)

ROLLBACK (target < 5 minutes)
  [ ] Re-point the current symlink to the previous release
  [ ] php artisan optimize && php artisan queue:restart
  [ ] If this release migrated: prefer roll-forward; migrate:rollback ONLY with
      reviewed, paired down() migrations — never blind; on data damage restore
      the latest verified backup (decision tree: 06-hosting-deployment.md §7
      and 12-monitoring.md §8).
==============================================================================
Full runbook: DEPLOY.md at the project root.
RUNBOOK
