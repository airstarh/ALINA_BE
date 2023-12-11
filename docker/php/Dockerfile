FROM php:7.4-fpm
RUN ln -sf /bin/bash /bin/sh
RUN apt-get update

##################################################
# COMPOSER
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
##################################################

##################################################
# ZIP/UNZIP for composer
# Install unzip utility and libs needed by zip PHP extension
RUN apt-get update && apt-get install -y \
    zlib1g-dev \
    libzip-dev \
    unzip
RUN docker-php-ext-install zip
##################################################

##################################################
# PDO
RUN docker-php-ext-install pdo pdo_mysql mysqli
##################################################

##################################################
# Mongo DB
RUN apt-get install -y autoconf pkg-config libssl-dev
RUN pecl install mongodb
RUN echo "extension=mongodb.so" >> /usr/local/etc/php/conf.d/mongodb.ini
##################################################

##################################################
# IMAGE MAGIK
RUN apt-get update && apt-get install -y libmagickwand-dev --no-install-recommends && rm -rf /var/lib/apt/lists/*
RUN printf "\n" | pecl install imagick
RUN docker-php-ext-enable imagick
##################################################

##################################################
#CHMOD CHOWN
RUN chmod -R 777 /var/www
##################################################