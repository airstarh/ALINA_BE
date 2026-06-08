#! /bin/bash

# Set ownership and permissions from project root
# cd ~/_A001/rep/ALINA_BE

ALINA_USER_PHP=33
ALINA_GROUP_PHP=33
ALINA_USER_NGINX=101
ALINA_GROUP_NGINX=101
ALINA_USER_MYSQL=999
ALINA_GROUP_MYSQL=999
ALINA_DIR=777
ALINA_FILE=777

# MYSQL (999)
sudo chown -R "${ALINA_USER_MYSQL}":"${ALINA_GROUP_MYSQL}" ./database/mysql

# PHP (www-data 33)
sudo chown -R "${ALINA_USER_PHP}":"${ALINA_GROUP_PHP}" ./server/var/log/php
sudo chown -R "${ALINA_USER_PHP}":"${ALINA_GROUP_PHP}" ./server/var/www
sudo chown -R "${ALINA_USER_PHP}":"${ALINA_GROUP_PHP}" ./server/srv/alina
sudo chown -R "${ALINA_USER_PHP}":"${ALINA_GROUP_PHP}" ./server/srv/alina_consumers
#
sudo chmod -R "${ALINA_DIR}" ./server/var/log/php
sudo find ./server/var/www -type d -exec chmod "${ALINA_DIR}" {} \;
sudo find ./server/var/www -type f -exec chmod "${ALINA_FILE}" {} \;
sudo find ./server/srv/alina -type d -exec chmod "${ALINA_DIR}" {} \;
sudo find ./server/srv/alina -type f -exec chmod "${ALINA_FILE}" {} \;
sudo find ./server/srv/alina_consumers -type d -exec chmod "${ALINA_DIR}" {} \;
sudo find ./server/srv/alina_consumers -type f -exec chmod "${ALINA_FILE}" {} \;

# NGINX (nginx 101)
sudo chown -R "${ALINA_USER_NGINX}":"${ALINA_GROUP_NGINX}" ./server/var/log/nginx
sudo chown -R "${ALINA_USER_NGINX}":"${ALINA_GROUP_NGINX}" ./server/etc/nginx
sudo chown -R "${ALINA_USER_NGINX}":"${ALINA_GROUP_NGINX}" ./server/etc/letsencrypt
sudo chown "${ALINA_USER_NGINX}":"${ALINA_GROUP_NGINX}" ./server/srv/sewa/cert003/cert/*.pem
##
sudo chmod "${ALINA_FILE}" ./server/srv/sewa/cert003/cert/*.pem
sudo chmod -R "${ALINA_DIR}" ./server/srv/sewa/cert003/cert
sudo chmod -R "${ALINA_DIR}" ./server/var/log/nginx
sudo chmod -R "${ALINA_DIR}" ./server/etc/nginx
sudo chmod -R "${ALINA_DIR}" ./server/etc/letsencrypt

# CONFIGS PROTECTED SECURITY
sudo chmod "${ALINA_FILE}" ./server/etc/nginx/*.conf
sudo chmod "${ALINA_FILE}" ./server/etc/nginx/conf.d/*.conf
sudo chmod "${ALINA_FILE}" ./server/usr/local/etc/php/php.ini