#! /bin/bash

# Set ownership and permissions from project root
# cd ~/_A001/rep/ALINA_BE

ALINA_USER_PHP=33
ALINA_GROUP_PHP=33
ALINA_USER_NGINX=101
ALINA_GROUP_NGINX=101
ALINA_USER_MYSQL=999
ALINA_GROUP_MYSQL=999

# MYSQL (999)
sudo chown -R "${ALINA_USER_MYSQL}":"${ALINA_GROUP_MYSQL}" ./database/mysql

# PHP (www-data 33)
sudo chown -R "${ALINA_USER_PHP}":"${ALINA_GROUP_PHP}" ./server/var/log/php
sudo chown -R "${ALINA_USER_PHP}":"${ALINA_GROUP_PHP}" ./server/var/www
sudo chown -R "${ALINA_USER_PHP}":"${ALINA_GROUP_PHP}" ./server/srv/alina
sudo chown -R "${ALINA_USER_PHP}":"${ALINA_GROUP_PHP}" ./server/srv/alina_consumers
sudo chmod 755 ./server/var/log/php
sudo find ./server/var/www -type d -exec chmod 755 {} \;
sudo find ./server/var/www -type f -exec chmod 644 {} \;
sudo find ./server/srv/alina -type d -exec chmod 755 {} \;
sudo find ./server/srv/alina -type f -exec chmod 644 {} \;
sudo find ./server/srv/alina_consumers -type d -exec chmod 755 {} \;
sudo find ./server/srv/alina_consumers -type f -exec chmod 644 {} \;

# NGINX (nginx 101)
sudo chown -R "${ALINA_USER_NGINX}":"${ALINA_GROUP_NGINX}" ./server/var/log/nginx
sudo chown "${ALINA_USER_NGINX}":"${ALINA_GROUP_NGINX}" ./server/srv/sewa/cert003/cert/*.pem
sudo chmod 644 ./server/srv/sewa/cert003/cert/*.pem
sudo chmod 755 ./server/srv/sewa/cert003/cert
sudo chmod 755 ./server/var/log/nginx

# CONFIGS PROTECTED SECURITY
sudo chmod 644 ./server/etc/nginx/*.conf
sudo chmod 644 ./server/etc/nginx/conf.d/*.conf
sudo chmod 644 ./server/usr/local/etc/php/php.ini