FROM php:7.4-fpm
RUN ln -sf /bin/bash /bin/sh
RUN apt-get update

##################################################
# COMPOSER
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
##################################################
RUN apt-get install -y rsync
##################################################
# ZIP/UNZIP for composer
# Install unzip utility and libs needed by zip PHP extension
RUN apt-get install -y \
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
#RUN apt-get update && apt-get install -y libssl-dev libcurl4-openssl-dev
# Install specific mongodb version compatible with PHP 7.4
# Last version supporting PHP 7.4
RUN pecl install mongodb-1.14.2
RUN docker-php-ext-enable mongodb
##################################################

##################################################
# IMAGE MAGIK
RUN apt-get update && apt-get install -y libmagickwand-dev --no-install-recommends && rm -rf /var/lib/apt/lists/*
RUN printf "\n" | pecl install imagick
RUN docker-php-ext-enable imagick
##################################################
RUN rm -rf /var/lib/apt/lists/*
##################################################
#CHMOD CHOWN
RUN chmod -R 777 /var/www
##################################################