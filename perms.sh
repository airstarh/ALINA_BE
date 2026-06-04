#! /bin/bash

# Set ownership and permissions from project root
# cd ~/_A001/rep/ALINA_BE

# 1. Directories that need write access (user uploads, logs, cache)
sudo chown -R 101:101 ./server/var/log/nginx   # Nginx user
sudo chown -R 999:999 ./database/mysql         # MySQL user
sudo chown -R 33:33 ./server/var/www           # PHP user (www-data)
sudo chown -R 33:33 ./server/var/log/php       # PHP user
sudo chown -R 33:33 ./server/srv               # PHP user Framework (PHP needs write for cache?)

# 2. Set permissions
# Directories: 755 (rwxr-xr-x)
# Files: 644 (rw-r--r--)
find ./server/var/www -type d -exec chmod 755 {} \;
find ./server/var/www -type f -exec chmod 644 {} \;
find ./server/srv/alina -type d -exec chmod 755 {} \;
find ./server/srv/alina -type f -exec chmod 644 {} \;
find ./server/srv/alina_consumers -type d -exec chmod 755 {} \;
find ./server/srv/alina_consumers -type f -exec chmod 644 {} \;

# 3. For SSL certificates (nginx needs to read)
sudo chown 101:101 ./server/srv/sewa/cert003/cert/*.pem
chmod 644 ./server/srv/sewa/cert003/cert/*.pem
chmod 755 ./server/srv/sewa/cert003/cert

# 4. For log directories
chmod 755 ./server/var/log/nginx
chmod 755 ./server/var/log/php

# 5. For config files (read-only for everyone)
chmod 644 ./server/etc/nginx/*.conf
chmod 644 ./server/etc/nginx/conf.d/*.conf
chmod 644 ./server/usr/local/etc/php/php.ini