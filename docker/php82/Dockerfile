FROM php:8.2-fpm

ARG INSTALL_XDEBUG=false

RUN ln -sf /bin/bash /bin/sh

##################################################
# COMPOSER
COPY transfer/composer.phar /usr/local/bin/composer
RUN chmod +x /usr/local/bin/composer

##################################################
# ESSENTIAL TOOLS + BUILD DEPS
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        zlib1g-dev \
        libzip-dev \
        unzip \
        autoconf \
        build-essential \
        libmagickwand-dev \
        imagemagick \
        libpango1.0-dev \
        libglib2.0-dev \
        pkg-config \
        libssl-dev \
        libcurl4-openssl-dev \
        git \
        && rm -rf /var/lib/apt/lists/*

##################################################
# PHP EXTENSIONS
RUN docker-php-ext-install zip pdo pdo_mysql mysqli

##################################################
# IMAGE MAGICK - INSTALL FROM SOURCE
RUN git clone https://github.com/Imagick/imagick /tmp/imagick && \
    cd /tmp/imagick && \
    phpize && \
    ./configure && \
    make && \
    make install && \
    docker-php-ext-enable imagick && \
    rm -rf /tmp/imagick

##################################################
# XDEBUG - FIXED
RUN if [ "$INSTALL_XDEBUG" = "true" ]; then \
      pecl channel-update pecl.php.net && \
      pecl install xdebug-3.3.2 && \
      docker-php-ext-enable xdebug; \
    fi

COPY transfer/xdebug.ini /tmp/xdebug.ini

RUN if [ "$INSTALL_XDEBUG" = "true" ]; then \
      mkdir -p /usr/local/etc/php/conf.d && \
      cp /tmp/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini; \
    fi

RUN usermod -u 33 www-data && \
    groupmod -g 33 www-data

COPY transfer/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh && \
    chown 33:33 /usr/local/bin/entrypoint.sh

USER www-data

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
