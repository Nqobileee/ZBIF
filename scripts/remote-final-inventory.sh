#!/bin/bash
set -e
echo "=== FINAL INVENTORY ==="
ls -ld /var/www/zbif
echo -n "files: "; find /var/www/zbif -type f | wc -l
echo -n "dirs: "; find /var/www/zbif -type d | wc -l
echo -n "php: "; find /var/www/zbif -name '*.php' | wc -l
du -sh /var/www/zbif
echo "top-level:"
ls -la /var/www/zbif
echo "nginx:"
ls -l /etc/nginx/sites-enabled/zbif
cd /var/www/zbif
sudo -u www-data php -r 'require "bootstrap.php"; $d=App\Support\Database::fetch("SELECT DATABASE() AS d"); $c=App\Support\Database::fetch("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE()"); echo "db=".$d["d"]." tables=".$c["c"].PHP_EOL;'
echo "READY"
