FROM php:8.2-fpm

ARG INSTALL_XDEBUG=false

# Bash как дефолтная оболочка (опционально)
RUN ln -sf /bin/bash /bin/sh

##################################################
# COMPOSER
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

##################################################
# ESSENTIAL TOOLS & BUILD DEPS
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        zlib1g-dev \
        libzip-dev \
        unzip \
        libssl-dev \
        libcurl4-openssl-dev \
        pkg-config \
        autoconf \
        build-essential \
        libmagickwand-dev \
        imagemagick && \
    rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install zip

##################################################
# PDO & MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

##################################################
# MongoDB: НЕ через pecl, а через Composer (стабильно для PHP 8.2)
# PECL-версия часто ломается при сборке образа; драйвер mongodb ставится как PHP-пакет.
# В composer.json добавь: "mongodb/mongodb": "^1.20"
# Это даёт и нативный драйвер, и ODM-обёртку.

##################################################
# ImageMagick
RUN printf "\n" | pecl install imagick && \
    docker-php-ext-enable imagick

##################################################
# XDEBUG (условно)
RUN if [ "$INSTALL_XDEBUG" = "true" ]; then \
      pecl install xdebug; \
    fi

COPY xdebug82.ini /tmp/xdebug82.ini

RUN if [ "$INSTALL_XDEBUG" = "true" ]; then \
      mkdir -p /usr/local/etc/php/conf.d && \
      cp /tmp/xdebug82.ini /usr/local/etc/php/conf.d/xdebug.ini; \
    fi

# Права пользователя www-data (uid/gid 33)
RUN usermod -u 33 www-data && \
    groupmod -g 33 www-data

# Entrypoint
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh && \
    chown root:root /usr/local/bin/entrypoint.sh

USER www-data

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
