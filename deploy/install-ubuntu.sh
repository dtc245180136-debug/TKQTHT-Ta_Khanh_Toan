#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
command -v openssl >/dev/null
docker info >/dev/null
docker compose version
umask 077
mkdir -p secrets backups
for name in db_password root_password admin_password grafana_password exporter_password; do
  if [ ! -s "secrets/$name" ]; then openssl rand -hex 24 > "secrets/$name"; fi
done
# Non-root services must read bind-mounted Compose secrets. Parent remains owner-only.
chmod 700 secrets
chmod 644 secrets/*_password
if [ ! -s secrets/exporter.cnf ]; then
  printf '[client]\nuser=exporter\npassword=%s\nhost=mysql\n' "$(cat secrets/exporter_password)" > secrets/exporter.cnf
fi
chmod 644 secrets/exporter.cnf
if [ ! -s secrets/init-exporter.sql ]; then
  printf "CREATE USER IF NOT EXISTS 'exporter'@'%%' IDENTIFIED BY '%s' WITH MAX_USER_CONNECTIONS 3;\nGRANT PROCESS, REPLICATION CLIENT, SELECT ON *.* TO 'exporter'@'%%';\n" "$(cat secrets/exporter_password)" > secrets/init-exporter.sql
fi
docker compose config --quiet
docker compose --profile linux-monitoring up -d --build
printf '\nWebsite: http://localhost:8088\nAdmin: /login (username: admin)\nPassword: secrets/admin_password\nGrafana: http://localhost:3008 (admin; secrets/grafana_password)\n'
printf 'Run: docker compose ps ; then follow docs/NGHIEM-THU.md\n'
