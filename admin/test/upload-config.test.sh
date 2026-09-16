#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
FPM_CONF="$ROOT_DIR/server_specific/php82/usr/local/etc/php-fpm.d/www.conf"
PHP_INI="$ROOT_DIR/server_specific/php82/usr/local/etc/php/conf.d/99.php.ini"
NGINX_CONF="$ROOT_DIR/server/etc/nginx/nginx.conf"
NGINX_LOCATION="$ROOT_DIR/server/etc/nginx/conf.d/location.alina.php82"
COMPOSE_CONF="$ROOT_DIR/dc.all.php82.yml"

assert_line() {
    local file="$1"
    local expected="$2"
    if ! grep -Fqx "$expected" "$file"; then
        printf 'FAIL: %s must contain: %s\n' "$file" "$expected" >&2
        exit 1
    fi
}

assert_line "$PHP_INI" "upload_max_filesize = 5000M"
assert_line "$PHP_INI" "post_max_size = 5120M"
assert_line "$PHP_INI" "max_file_uploads = 100"
assert_line "$PHP_INI" "upload_tmp_dir = /var/tmp/php-upload"
assert_line "$PHP_INI" "memory_limit = 512M"
assert_line "$PHP_INI" "max_execution_time = 14400"
assert_line "$PHP_INI" "max_input_time = 14400"

assert_line "$FPM_CONF" "php_admin_value[upload_max_filesize] = 5000M"
assert_line "$FPM_CONF" "php_admin_value[post_max_size] = 5120M"
assert_line "$FPM_CONF" "php_admin_value[max_file_uploads] = 100"
assert_line "$FPM_CONF" "php_admin_value[upload_tmp_dir] = /var/tmp/php-upload"
assert_line "$FPM_CONF" "php_admin_value[memory_limit] = 512M"
assert_line "$FPM_CONF" "php_admin_value[max_execution_time] = 14400"
assert_line "$FPM_CONF" "php_admin_value[max_input_time] = 14400"

assert_line "$FPM_CONF" "request_terminate_timeout = 14400s"
assert_line "$NGINX_CONF" "    client_max_body_size 5120M;"
assert_line "$NGINX_CONF" "    client_body_timeout 14400s;"
assert_line "$NGINX_LOCATION" "    fastcgi_request_buffering off;"
assert_line "$NGINX_LOCATION" "    fastcgi_send_timeout 14400s;"
assert_line "$NGINX_LOCATION" "    fastcgi_read_timeout 14400s;"
assert_line "$COMPOSE_CONF" "        mem_limit: 768M"
assert_line "$COMPOSE_CONF" "            - ./server/var/tmp/php-upload/:/var/tmp/php-upload/:rw"

printf 'PASS: large-upload configuration contract\n'
