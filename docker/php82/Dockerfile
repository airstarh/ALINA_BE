FROM php:8.2-fpm

ARG INSTALL_XDEBUG=false

# Bash as default shell (optional)
RUN ln -sf /bin/bash /bin/sh

##################################################
# COMPOSER
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

##################################################
# ESSENTIAL TOOLS & BUILD DEPS (all in one update to avoid stale lists)
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
# MongoDB (use latest stable via pecl without version pin)
RUN pecl channel-update pecl.php.net && \
    pecl install mongodb && \
    docker-php-ext-enable mongodb

##################################################
# ImageMagick
RUN printf "\n" | pecl install imagick && \
    docker-php-ext-enable imagick

##################################################
# XDEBUG (conditional)
RUN if [ "$INSTALL_XDEBUG" = "true" ]; then \
      pecl install xdebug; \
    fi

COPY xdebug.ini /tmp/xdebug82.ini

RUN if [ "$INSTALL_XDEBUG" = "true" ]; then \
      mkdir -p /usr/local/etc/php/conf.d && \
      cp /tmp/xdebug82.ini /usr/local/etc/php/conf.d/xdebug.ini; \
    fi

# Adjust UID/GID for www-data (33)
RUN usermod -u 33 www-data && \
    groupmod -g 33 www-data

# Entrypoint
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh && \
    chown root:root /usr/local/bin/entrypoint.sh

USER www-data

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
