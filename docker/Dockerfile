FROM php:8.2-fpm

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
        libicu-dev \
    && docker-php-ext-install \
        mysqli \
        pdo_mysql \
        intl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# PHP config
RUN echo "upload_max_filesize = 10M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 10M" >> /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html
