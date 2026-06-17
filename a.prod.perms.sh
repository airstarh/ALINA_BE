#!/bin/bash
set -euo pipefail

# Проверка прав
if [[ $EUID -ne 0 ]]; then
    echo "Ошибка: запустите скрипт через sudo" >&2
    exit 1
fi

# Базовая директория
BASE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

ALINA_USER_PHP=33
ALINA_GROUP_PHP=33
ALINA_USER_NGINX=101
ALINA_GROUP_NGINX=101
ALINA_USER_MYSQL=999
ALINA_GROUP_MYSQL=999

ALINA_DIR=755
ALINA_FILE=644

ALINA_PERMISSIONS() {
    local U=$1
    local G=$2
    local P="$BASE_DIR/$3"

    if [[ ! -d "$P" ]]; then
        echo "Предупреждение: путь '$P' не найден, пропускаем." >&2
        return 0
    fi

    chown -R "$U:$G" "$P"
    find "$P" -type d -exec chmod "$ALINA_DIR" {} \;
    find "$P" -type f -exec chmod "$ALINA_FILE" {} \;
}

# === MySQL ===
chown -R "$ALINA_USER_MYSQL:$ALINA_GROUP_MYSQL" "$BASE_DIR/database/mysql"
chown -R "$ALINA_USER_MYSQL:$ALINA_GROUP_MYSQL" "$BASE_DIR/server/var/log/mysql"

# === PHP ===
ALINA_PERMISSIONS "$ALINA_USER_PHP" "$ALINA_GROUP_PHP" "server/var/log/php"
ALINA_PERMISSIONS "$ALINA_USER_PHP" "$ALINA_GROUP_PHP" "server/var/www"
ALINA_PERMISSIONS "$ALINA_USER_PHP" "$ALINA_GROUP_PHP" "server/srv/alina"
ALINA_PERMISSIONS "$ALINA_USER_PHP" "$ALINA_GROUP_PHP" "server/srv/alina_consumers"

# === NGINX ===
ALINA_PERMISSIONS "$ALINA_USER_NGINX" "$ALINA_GROUP_NGINX" "server/var/log/nginx"
ALINA_PERMISSIONS "$ALINA_USER_NGINX" "$ALINA_GROUP_NGINX" "server/var/log/letsencrypt"
ALINA_PERMISSIONS "$ALINA_USER_NGINX" "$ALINA_GROUP_NGINX" "server/etc/nginx"
ALINA_PERMISSIONS "$ALINA_USER_NGINX" "$ALINA_GROUP_NGINX" "server/etc/letsencrypt"
ALINA_PERMISSIONS "$ALINA_USER_NGINX" "$ALINA_GROUP_NGINX" "server/srv/sewa/cert003"

# === Защита конфигов ===
chmod "$ALINA_FILE" "$BASE_DIR/server/usr/local/etc/php/php.ini"
