#!/bin/sh
set -eu

port="${PORT:-80}"
if [ -z "$(find /var/www/html/images -mindepth 1 -maxdepth 1 -print -quit)" ]; then
    cp -a /opt/seed-images/. /var/www/html/images/
    chown -R www-data:www-data /var/www/html/images
fi
sed -ri "s/^Listen 80$/Listen ${port}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \\*:80>/<VirtualHost *:${port}>/" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
