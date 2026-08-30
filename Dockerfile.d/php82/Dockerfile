FROM php:8.2-fpm

ARG INSTALL_XDEBUG=false

RUN ln -sf /bin/bash /bin/sh

##################################################
# COMPOSER
COPY transfer/composer.phar /usr/local/bin/composer
RUN chmod +x /usr/local/bin/composer

##################################################
# ESSENTIAL TOOLS + BUILD DEPS (всё, что нужно для сборки)
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        zlib1g-dev \
        libzip-dev \
        unzip \
        autoconf \
        automake \
        build-essential \
        libmagickwand-dev \
        imagemagick \
        libpango1.0-dev \
        libglib2.0-dev \
        pkg-config \
        libssl-dev \
        libcurl4-openssl-dev \
        git \
        curl \
        libtool \
        bison \
        flex \
        re2c \
        && rm -rf /var/lib/apt/lists/*

##################################################
# PHP EXTENSIONS (базовые)
RUN docker-php-ext-install zip pdo pdo_mysql mysqli

##################################################
# INSTALL PIE FROM GITHUB
RUN curl -fL --output /tmp/pie.phar https://github.com/php/pie/releases/latest/download/pie.phar && \
    mv /tmp/pie.phar /usr/local/bin/pie && \
    chmod +x /usr/local/bin/pie

##################################################
# IMAGE MAGICK PHP EXTENSION VIA PIE (правильное имя: imagick/imagick)
RUN pie install --auto-install-build-tools imagick/imagick && \
    docker-php-ext-enable imagick

##################################################
# XDEBUG VIA PIE (правильное имя: xdebug/xdebug)
RUN if [ "$INSTALL_XDEBUG" = "true" ]; then \
      pie install --auto-install-build-tools xdebug/xdebug && \
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
