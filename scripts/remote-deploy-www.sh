#!/bin/bash
# Comprehensive ZBIF deploy onto /var/www/zbif
set -euo pipefail

APP_ROOT=/var/www/zbif
ARCHIVE=/tmp/zbif-full-deploy.tgz
ENV_FILE=/tmp/zbif-production.env
NGINX_SITE=/etc/nginx/sites-available/zbif
NGINX_LINK=/etc/nginx/sites-enabled/zbif

echo "=== [1/8] Preconditions ==="
test -f "$ARCHIVE" || { echo "Missing $ARCHIVE"; exit 1; }
test -f "$ENV_FILE" || { echo "Missing $ENV_FILE"; exit 1; }
php -v | head -1
nginx -v 2>&1 | head -1
test -S /var/run/php/php8.3-fpm.sock || test -S /run/php/php8.3-fpm.sock || { echo "php8.3-fpm sock missing"; exit 1; }

echo "=== [2/8] Backup existing /var/www/zbif if present ==="
if [ -d "$APP_ROOT" ] && [ "$(ls -A "$APP_ROOT" 2>/dev/null | wc -l)" -gt 0 ]; then
  TS=$(date +%Y%m%d_%H%M%S)
  mv "$APP_ROOT" "/var/www/zbif.bak_${TS}"
  echo "Backed up to /var/www/zbif.bak_${TS}"
fi

echo "=== [3/8] Create /var/www/zbif and extract code ==="
mkdir -p "$APP_ROOT"
tar -xzf "$ARCHIVE" -C "$APP_ROOT"
# If archive contained a top-level zbif/ folder, flatten it
if [ -d "$APP_ROOT/zbif" ] && [ ! -f "$APP_ROOT/public/index.php" ]; then
  shopt -s dotglob
  mv "$APP_ROOT/zbif"/* "$APP_ROOT"/
  rmdir "$APP_ROOT/zbif"
  shopt -u dotglob
fi

echo "=== [4/8] Verify critical paths ==="
for p in \
  public/index.php \
  public/router.php \
  bootstrap.php \
  routes/web.php \
  app/Support/Autoloader.php \
  views/layouts/public.php \
  database/schema.sql
 do
  test -f "$APP_ROOT/$p" || { echo "MISSING: $p"; exit 1; }
  echo "OK $p"
done

echo "=== [5/8] Storage dirs + production .env ==="
mkdir -p "$APP_ROOT/storage/logs" "$APP_ROOT/storage/cache" "$APP_ROOT/storage/uploads" \
         "$APP_ROOT/storage/uploads/avatars" "$APP_ROOT/storage/uploads/logos" \
         "$APP_ROOT/storage/uploads/decks" "$APP_ROOT/storage/uploads/brochures"
touch "$APP_ROOT/storage/logs/.gitkeep" "$APP_ROOT/storage/cache/.gitkeep" "$APP_ROOT/storage/uploads/.gitkeep"
cp -f "$ENV_FILE" "$APP_ROOT/.env"
chmod 640 "$APP_ROOT/.env"

echo "=== [6/8] Ownership and permissions ==="
chown -R www-data:www-data "$APP_ROOT"
find "$APP_ROOT" -type d -exec chmod 755 {} \;
find "$APP_ROOT" -type f -exec chmod 644 {} \;
chmod -R ug+rwX "$APP_ROOT/storage"
# Keep .env readable by php-fpm only
chmod 640 "$APP_ROOT/.env"
chown www-data:www-data "$APP_ROOT/.env"

echo "=== [7/8] Nginx site (document root = public/) ==="
# Do not clobber an existing Certbot-managed SSL vhost.
if [ -f "$NGINX_SITE" ] && grep -q 'ssl_certificate' "$NGINX_SITE" 2>/dev/null; then
  echo "Keeping existing SSL-enabled nginx site: $NGINX_SITE"
  ln -sfn "$NGINX_SITE" "$NGINX_LINK"
  nginx -t
  systemctl reload nginx
else
  mkdir -p /var/lib/letsencrypt/webroot
  cat > "$NGINX_SITE" <<'NGINX'
# ZBIF InnovaMatch — PHP app (document root: /var/www/zbif/public)
# HTTP first; run: certbot --nginx -d zbif.bulconsultancy.com --redirect
server {
    listen 80;
    listen [::]:80;
    server_name zbif.bulconsultancy.com;

    root /var/www/zbif/public;
    index index.php;

    access_log /var/log/nginx/zbif_access.log;
    error_log  /var/log/nginx/zbif_error.log;
    client_max_body_size 32M;

    location ^~ /.well-known/acme-challenge/ {
        root /var/lib/letsencrypt/webroot;
        default_type "text/plain";
        allow all;
    }

    location ~* \.(?:css|js|jpg|jpeg|png|gif|ico|svg|webp|woff2?|ttf|eot|map)$ {
        expires 7d;
        add_header Cache-Control "public";
        try_files $uri =404;
        access_log off;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 120s;
    }

    location ~ /\.(?!well-known).* {
        deny all;
        access_log off;
        log_not_found off;
    }

    # Block direct access to app sources outside public
    # Protect source folders if accidentally exposed. Do NOT include "app"
    # or dashboard URLs like /app/solutions will 404.
    location ~* ^/(bootstrap|database|routes|views|storage|scripts|tests|docs)(/|$) {
        deny all;
        return 404;
    }
}
NGINX

  ln -sfn "$NGINX_SITE" "$NGINX_LINK"
  nginx -t
  systemctl reload nginx
  if command -v certbot >/dev/null 2>&1; then
    echo "Issuing/installing TLS for zbif.bulconsultancy.com (idempotent)..."
    certbot --nginx -d zbif.bulconsultancy.com --redirect -n --agree-tos --register-unsafely-without-email || true
  fi
fi

echo "=== [8/8] Smoke tests ==="
cd "$APP_ROOT"
sudo -u www-data php -r '
require "bootstrap.php";
use App\Support\Database;
use App\Support\Env;
echo "APP_ENV=" . Env::get("APP_ENV") . PHP_EOL;
echo "DB=" . Env::get("DB_DATABASE") . "@" . Env::get("DB_HOST") . ":" . Env::get("DB_PORT") . PHP_EOL;
$n = Database::fetch("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE()");
echo "tables=" . ($n["c"] ?? "?") . PHP_EOL;
$u = Database::fetch("SELECT email, status FROM users WHERE email = ?", ["super@zbif.test"]);
echo "super=" . ($u["email"] ?? "MISSING") . " status=" . ($u["status"] ?? "?") . PHP_EOL;
'
FILE_COUNT=$(find "$APP_ROOT" -type f | wc -l)
DIR_COUNT=$(find "$APP_ROOT" -type d | wc -l)
echo "files=$FILE_COUNT dirs=$DIR_COUNT"
echo "curl local:"
curl -sS -o /tmp/zbif-home.html -w "HTTP %{http_code} size=%{size_download}\n" -H "Host: zbif.bulconsultancy.com" http://127.0.0.1/ || true
head -c 200 /tmp/zbif-home.html; echo
echo "DEPLOY COMPLETE: $APP_ROOT"
