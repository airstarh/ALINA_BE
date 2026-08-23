#!/bin/sh

if [ "$ALINA_MODE" != "DEV" ]; then
    return 0
fi

cd /srv/alina || { echo "ERROR: /srv/alina does not exist"; exit 0; }

mkdir -p vendor
chown -R www-data:www-data /srv/alina/vendor || true

# Пробуем composer install только при наличии интернета.
# Проверяем доступность любого публичного DNS (можно заменить на другой хост).
if ping -c 1 8.8.8.8 >/dev/null 2>&1; then
    composer install --no-interaction --optimize-autoloader >> /var/log/php/entrypoint.php82.log 2>&1
    if [ $? -eq 0 ]; then
        echo "composer install was on php 8.2" >> /var/log/php/entrypoint.php82.log
    else
        echo "composer install failed" >> /var/log/php/entrypoint.php82.log
        exit 1
    fi
else
    echo "No internet detected, skipping composer install" >> /var/log/php/entrypoint.php82.log
fi

date +"%Y-%m-%d %H:%M:%S" >> /var/log/php/entrypoint.php82.log
echo "" >> /var/log/php/entrypoint.php82.log
echo "" >> /var/log/php/entrypoint.php82.log
echo "" >> /var/log/php/entrypoint.php82.log
