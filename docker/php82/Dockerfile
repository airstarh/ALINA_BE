FROM php:8.2-fpm

ARG INSTALL_XDEBUG=false

RUN ln -sf /bin/bash /bin/sh

##################################################
# COMPOSER
COPY soft/composer.phar /usr/local/bin/composer
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
# XDEBUG
RUN if [ "$INSTALL_XDEBUG" = "true" ]; then \
      pecl install xdebug; \
    fi

COPY xdebug82.ini /tmp/xdebug82.ini

RUN if [ "$INSTALL_XDEBUG" = "true" ]; then \
      mkdir -p /usr/local/etc/php/conf.d && \
      cp /tmp/xdebug82.ini /usr/local/etc/php/conf.d/xdebug.ini; \
    fi

RUN usermod -u 33 www-data && \
    groupmod -g 33 www-data

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh && \
    chown root:root /usr/local/bin/entrypoint.sh

USER www-data

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
