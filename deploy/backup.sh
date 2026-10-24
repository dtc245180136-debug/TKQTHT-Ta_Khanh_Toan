#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
umask 077
mkdir -p backups
target="backups/portfolio-$(date +%Y%m%d-%H%M%S).sql"
docker compose exec -T mysql sh -c 'MYSQL_PWD="$(cat /run/secrets/db_password)" mysqldump -u portfolio --single-transaction --no-tablespaces portfolio' > "$target"
test -s "$target"
printf 'Backup: %s\n' "$target"
