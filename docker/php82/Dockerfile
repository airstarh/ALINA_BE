FROM php:8.2-fpm

ARG INSTALL_XDEBUG=false

RUN ln -sf /bin/bash /bin/sh

##################################################
# COMPOSER
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

##################################################
# ESSENTIAL TOOLS
RUN apt-get update && \
    apt-get install -y \
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
    pecl install mongodb-2.4.0 && \
    docker-php-ext-enable mongodb && \
    rm -rf /var/lib/apt/lists/*

##################################################
# IMAGE MAGICK
RUN apt-get update && \
    apt-get install -y libmagickwand-dev --no-install-recommends && \
    printf "\n" | pecl install imagick && \
    docker-php-ext-enable imagick && \
    rm -rf /var/lib/apt/lists/*

##################################################
# XDEBUG (условно)
RUN if [ "$INSTALL_XDEBUG" = "true" ]; then \
      pecl install xdebug-3.5.0; \
    fi

COPY xdebug.ini /tmp/xdebug.ini

RUN if [ "$INSTALL_XDEBUG" = "true" ]; then \
      mkdir -p /usr/local/etc/php/conf.d && \
      cp /tmp/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini; \
    fi

# Права пользователя www-data (uid/gid 33)
RUN usermod -u 33 www-data && \
    groupmod -g 33 www-data

# Копируем entrypoint и делаем исполняемым
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh && \
    chown root:root /usr/local/bin/entrypoint.sh

USER www-data

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
