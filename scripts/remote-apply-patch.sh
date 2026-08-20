#!/bin/bash
set -euo pipefail
cd /var/www/zbif
tar -xzf /tmp/zbif-patch.tgz -C /var/www/zbif
# Production URL for IP access until DNS/SSL ready
sed -i 's|^APP_URL=.*|APP_URL=http://31.97.199.82|' /var/www/zbif/.env
chown -R www-data:www-data /var/www/zbif/config /var/www/zbif/cron /var/www/zbif/lang /var/www/zbif/bootstrap.php /var/www/zbif/app/Support/SecurityHeaders.php /var/www/zbif/.env
find /var/www/zbif/config /var/www/zbif/cron /var/www/zbif/lang -type f -exec chmod 644 {} \;
chmod 640 /var/www/zbif/.env
echo "=== verify missing files now present ==="
for f in config/app.php cron/worker.php lang/en.php bootstrap.php app/Support/SecurityHeaders.php; do
  test -f "/var/www/zbif/$f" && echo "OK $f" || echo "FAIL $f"
done
echo "=== php count ==="
find /var/www/zbif -name '*.php' -type f | wc -l
echo "=== cookie secure check (should NOT say Secure when HTTP) ==="
curl -sS -D - -o /dev/null -H "Host: zbif.bulconsultancy.com" http://127.0.0.1/ | grep -i set-cookie
echo "=== homepage title ==="
curl -sS -H "Host: zbif.bulconsultancy.com" http://127.0.0.1/ | grep -o '<title>[^<]*</title>'
echo "=== login page ==="
curl -sS -o /dev/null -w "login HTTP %{http_code}\n" -H "Host: zbif.bulconsultancy.com" http://127.0.0.1/login.php
echo "=== admin login ==="
curl -sS -o /dev/null -w "admin login HTTP %{http_code}\n" -H "Host: zbif.bulconsultancy.com" http://127.0.0.1/admin/login.php
echo "=== register ==="
curl -sS -o /dev/null -w "register HTTP %{http_code}\n" -H "Host: zbif.bulconsultancy.com" http://127.0.0.1/register
echo "PATCH APPLIED"
