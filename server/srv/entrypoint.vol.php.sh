#!/bin/sh

# Если не DEV — просто выходим, обёртка сама запустит "$@"
if [ "$ALINA_MODE" != "DEV" ]; then
  exit 0
fi

cd /srv/alina || { echo "ERROR: /srv/alina does not exist"; exit 1; }

mkdir -p /srv/alina/vendor
mkdir -p storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chown -R www-data:www-data /srv/alina/vendor || true

composer install --no-interaction --optimize-autoloader

