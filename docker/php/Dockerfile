FROM php:7.4-fpm
RUN ln -sf /bin/bash /bin/sh

##################################################
# COMPOSER
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

##################################################
# ESSENTIAL TOOLS (including rsync)
RUN apt-get update && \
    apt-get install -y \
        rsync \
        zlib1g-dev \
        libzip-dev \
        unzip && \
    rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install zip

##################################################
# PDO
RUN docker-php-ext-install pdo pdo_mysql mysqli

##################################################
# Mongo DB
RUN apt-get update && \
    apt-get install -y libssl-dev libcurl4-openssl-dev && \
    pecl install mongodb-1.14.2 && \
    docker-php-ext-enable mongodb && \
    rm -rf /var/lib/apt/lists/*

##################################################
# IMAGE MAGIK
RUN apt-get update && \
    apt-get install -y libmagickwand-dev --no-install-recommends && \
    printf "\n" | pecl install imagick && \
    docker-php-ext-enable imagick && \
    rm -rf /var/lib/apt/lists/*

##################################################
# COPY ENTRYPOINT SCRIPT
COPY entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

##################################################
# CHMOD CHOWN
RUN chmod -R 777 /var/www

##################################################
# SET ENTRYPOINT
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]