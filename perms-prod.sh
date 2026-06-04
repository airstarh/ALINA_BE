#! /bin/bash

# Set ownership and permissions from project root
# cd ~/_A001/rep/ALINA_BE

# MYSQL (999)
sudo chown -R 999:999 ./database/mysql

# PHP (www-data 33)
sudo chown -R 33:33 ./server/var/log/php
sudo chown -R 33:33 ./server/var/www
sudo chown -R 33:33 ./server/srv/alina
sudo chown -R 33:33 ./server/srv/alina_consumers
chmod 755 ./server/var/log/php
find ./server/var/www -type d -exec chmod 755 {} \;
find ./server/var/www -type f -exec chmod 644 {} \;
find ./server/srv/alina -type d -exec chmod 755 {} \;
find ./server/srv/alina -type f -exec chmod 644 {} \;
find ./server/srv/alina_consumers -type d -exec chmod 755 {} \;
find ./server/srv/alina_consumers -type f -exec chmod 644 {} \;

# NGINX (nginx 101)
sudo chown -R 101:101 ./server/var/log/nginx   # Nginx user
sudo chown 101:101 ./server/srv/sewa/cert003/cert/*.pem
chmod 644 ./server/srv/sewa/cert003/cert/*.pem
chmod 755 ./server/srv/sewa/cert003/cert
chmod 755 ./server/var/log/nginx


# CONFIGS PROTECTED SECURITY
chmod 644 ./server/etc/nginx/*.conf
chmod 644 ./server/etc/nginx/conf.d/*.conf
chmod 644 ./server/usr/local/etc/php/php.ini