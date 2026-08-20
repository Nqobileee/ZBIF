#!/bin/bash
set -euo pipefail
echo "=== nginx zbif site ==="
ls -la /etc/nginx/sites-enabled/zbif /etc/nginx/sites-available/zbif
echo "=== curl by Host header ==="
curl -sS -D - -o /tmp/zbif1.html -H "Host: zbif.bulconsultancy.com" http://127.0.0.1/ | head -20
echo "--- body head ---"
head -c 300 /tmp/zbif1.html; echo
echo "=== curl Host 31.97.199.82 ==="
curl -sS -D - -o /tmp/zbif2.html -H "Host: 31.97.199.82" http://127.0.0.1/ | head -20
echo "=== php-fpm direct via SCRIPT ==="
SCRIPT_FILENAME=/var/www/zbif/public/index.php REQUEST_METHOD=GET REQUEST_URI=/ SCRIPT_NAME=/index.php \
  cgi-fcgi -bind -connect /run/php/php8.3-fpm.sock 2>/dev/null | head -5 || true
echo "=== tree critical ==="
find /var/www/zbif -maxdepth 2 -type d | sort
echo "=== counts ==="
echo -n "php files: "; find /var/www/zbif -name '*.php' | wc -l
echo -n "css files: "; find /var/www/zbif -name '*.css' | wc -l
echo -n "js files: "; find /var/www/zbif -name '*.js' | wc -l
echo -n "png files: "; find /var/www/zbif -name '*.png' | wc -l
echo -n "views: "; find /var/www/zbif/views -type f | wc -l
echo -n "controllers: "; find /var/www/zbif/app/Http/Controllers -type f | wc -l
echo "=== missing partner logos? ==="
ls /var/www/zbif/public/assets/img/ | head -30
ls /var/www/zbif/public/assets/img/partners 2>/dev/null | head -20 || echo "no partners dir"
echo "=== .env (redacted) ==="
grep -E '^(APP_|DB_|AUTH_VERIFICATION)' /var/www/zbif/.env | sed 's/PASSWORD=.*/PASSWORD=***/'
echo "=== ownership sample ==="
ls -la /var/www/zbif /var/www/zbif/public /var/www/zbif/.env /var/www/zbif/storage
echo "=== nginx which server for zbif host ==="
nginx -T 2>/dev/null | awk '/server_name.*zbif/,/^}/' | head -40
