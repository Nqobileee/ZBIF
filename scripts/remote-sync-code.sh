#!/bin/bash
# Sync app code into /var/www/zbif without wiping storage/.env or nginx SSL.
set -euo pipefail

APP_ROOT=/var/www/zbif
ARCHIVE=/tmp/zbif-sync.tgz
ENV_FILE=/tmp/zbif-production.env

test -f "$ARCHIVE" || { echo "Missing $ARCHIVE"; exit 1; }
test -d "$APP_ROOT" || { echo "Missing $APP_ROOT — run full deploy first"; exit 1; }

echo "=== Extract code over existing install ==="
tar -xzf "$ARCHIVE" -C "$APP_ROOT"

echo "=== Storage dirs ==="
mkdir -p "$APP_ROOT/storage/logs" "$APP_ROOT/storage/cache" "$APP_ROOT/storage/uploads" \
         "$APP_ROOT/storage/uploads/avatars" "$APP_ROOT/storage/uploads/logos" \
         "$APP_ROOT/storage/uploads/decks" "$APP_ROOT/storage/uploads/brochures"

if [ -f "$ENV_FILE" ]; then
  echo "=== Refresh .env from production template ==="
  cp -f "$ENV_FILE" "$APP_ROOT/.env"
  chmod 640 "$APP_ROOT/.env"
  chown www-data:www-data "$APP_ROOT/.env"
fi

# Ensure HTTPS APP_URL even if template lagged
sed -i 's|^APP_URL=.*|APP_URL=https://zbif.bulconsultancy.com|' "$APP_ROOT/.env"

chown -R www-data:www-data "$APP_ROOT"
find "$APP_ROOT" -type d -exec chmod 755 {} \;
find "$APP_ROOT" -type f -exec chmod 644 {} \;
chmod -R ug+rwX "$APP_ROOT/storage"
chmod 640 "$APP_ROOT/.env"
chown www-data:www-data "$APP_ROOT/.env"

echo "=== Seed schedule if script present ==="
if [ -f "$APP_ROOT/scripts/seed-schedule-2026.php" ]; then
  cd "$APP_ROOT"
  sudo -u www-data php scripts/seed-schedule-2026.php || true
fi

echo "=== Smoke ==="
grep '^APP_URL=' "$APP_ROOT/.env"
curl -skS -o /dev/null -w "https home: %{http_code}\n" --resolve zbif.bulconsultancy.com:443:127.0.0.1 https://zbif.bulconsultancy.com/
curl -skS -o /dev/null -w "https schedule: %{http_code}\n" --resolve zbif.bulconsultancy.com:443:127.0.0.1 "https://zbif.bulconsultancy.com/schedule?tab=deal-rooms"
echo "SYNC COMPLETE"
