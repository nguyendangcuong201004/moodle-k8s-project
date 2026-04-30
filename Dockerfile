FROM php:8.2-fpm-bullseye

RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev \
    libicu-dev libxml2-dev libonig-dev libxslt1-dev \
    libpq-dev libsodium-dev unzip git locales \
    && rm -rf /var/lib/apt/lists/*

RUN echo "en_US.UTF-8 UTF-8" > /etc/locale.gen && \
    echo "vi_VN.UTF-8 UTF-8" >> /etc/locale.gen && \
    locale-gen

ENV LANG=vi_VN.UTF-8
ENV LANGUAGE=vi_VN:en
ENV LC_ALL=vi_VN.UTF-8

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd opcache intl zip soap exif pgsql pdo_pgsql sodium \
    && pecl install redis \
    && docker-php-ext-enable redis

RUN echo "max_input_vars = 5000" >> /usr/local/etc/php/conf.d/moodle-vars.ini
RUN echo "upload_max_filesize = 100M" >> /usr/local/etc/php/conf.d/uploads.ini
RUN echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/uploads.ini
RUN echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/memory.ini

RUN { \
      echo "[www]"; \
      echo "pm.status_path = /status"; \
      echo "ping.path = /ping"; \
      echo "ping.response = pong"; \
    } > /usr/local/etc/php-fpm.d/zz-status.conf

COPY docker/moodle-fpm-entrypoint.sh /usr/local/bin/moodle-fpm-entrypoint.sh
RUN chmod +x /usr/local/bin/moodle-fpm-entrypoint.sh

COPY ./src /var/www/html

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

WORKDIR /var/www/html

ENV OPCACHE_MEMORY=256 \
    FPM_PM_CONTROL=dynamic \
    FPM_MAX_CHILDREN=40 \
    FPM_START_SERVERS=5 \
    FPM_MIN_SPARE=5 \
    FPM_MAX_SPARE=10

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/moodle-fpm-entrypoint.sh"]
