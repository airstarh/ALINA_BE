# --- Stage 1: Build dependencies and compile extensions ---
FROM php:8.2-fpm AS builder

ARG INSTALL_XDEBUG=false

RUN ln -sf /bin/bash /bin/sh

# Базовые зависимости для сборки расширений
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        autoconf \
        build-essential \
        libmagickwand-dev \
        imagemagick \
        libpango1.0-dev \
        libglib2.0-dev \
        pkg-config \
        libzip-dev \
        zlib1g-dev \
        libssl-dev \
        libcurl4-openssl-dev && \
    rm -rf /var/lib/apt/lists/*

# ВАЖНО: Вместо скачивания установщика мы просто копируем готовый composer.phar
# Файл должен лежать в той же папке, где запускается docker build
COPY soft/composer.phar /usr/local/bin/composer
RUN chmod +x /usr/local/bin/composer

# PHP расширения
RUN docker-php-ext-install zip pdo pdo_mysql mysqli

# PECL расширения (ImageMagick)
RUN pecl channel-update pecl.php.net && \
    pecl install imagick && \
    docker-php-ext-enable imagick

# Xdebug (опционально)
RUN if [ "\$INSTALL_XDEBUG" = "true" ]; then \
      pecl install xdebug; \
    fi

COPY xdebug82.ini /tmp/xdebug82.ini

RUN if [ "\$INSTALL_XDEBUG" = "true" ]; then \
      mkdir -p /usr/local/etc/php/conf.d && \
      cp /tmp/xdebug82.ini /usr/local/etc/php/conf.d/xdebug.ini; \
    fi


# --- Stage 2: Minimal runtime image ---
FROM php:8.2-fpm

ARG INSTALL_XDEBUG=false

RUN ln -sf /bin/bash /bin/sh

# Копируем только готовые расширения и конфиги (без тяжёлых build-инструментов)
COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/
COPY --from=builder /usr/local/bin/composer /usr/local/bin/composer
RUN chmod +x /usr/local/bin/composer

# Подстройка UID/GID для www-data (33)
RUN usermod -u 33 www-data && \
    groupmod -g 33 www-data

# Entrypoint
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh && \
    chown root:root /usr/local/bin/entrypoint.sh

USER www-data

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
