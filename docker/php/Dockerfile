FROM php:7.4-fpm

ARG INSTALL_XDEBUG=false

RUN ln -sf /bin/bash /bin/sh

##################################################
# COMPOSER
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

##################################################
# ESSENTIAL TOOLS
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
# XDEBUG (условно)
COPY xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
RUN if [ "$INSTALL_XDEBUG" = "true" ]; then \
      pecl install xdebug-2.9.8; \
    fi

##################################################
# CREATE PHP USER/GROUP
RUN usermod -u 33 www-data && \
    groupmod -g 33 www-data && \
    mkdir -p /var/www && \
    chown -R www-data:www-data /var/www && \
    chmod -R 755 /var/www

##################################################
# COPY ENTRYPOINT SCRIPT
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh && \
    chown www-data:www-data /usr/local/bin/entrypoint.sh

USER www-data

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
