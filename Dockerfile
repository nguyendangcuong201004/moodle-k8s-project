FROM php:8.2-apache


RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev \
    libicu-dev libxml2-dev libonig-dev libxslt1-dev \
    libpq-dev libsodium-dev unzip git locales \
    && rm -rf /var/lib/apt/lists/*


RUN echo "en_US.UTF-8 UTF-8" > /etc/locale.gen && locale-gen


RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd opcache intl zip soap exif pgsql pdo_pgsql sodium


RUN echo "max_input_vars = 5000" >> /usr/local/etc/php/conf.d/moodle-vars.ini
RUN echo "upload_max_filesize = 100M" >> /usr/local/etc/php/conf.d/uploads.ini
RUN echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/uploads.ini
RUN echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/memory.ini

RUN echo "en_US.UTF-8 UTF-8" > /etc/locale.gen && \
    echo "vi_VN.UTF-8 UTF-8" >> /etc/locale.gen && \
    locale-gen

ENV LANG=vi_VN.UTF-8
ENV LANGUAGE=vi_VN:en
ENV LC_ALL=vi_VN.UTF-8


ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN a2enmod rewrite


COPY ./src /var/www/html


RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html