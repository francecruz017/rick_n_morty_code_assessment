FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git unzip zip libpq-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_pgsql

WORKDIR /var/www/html

COPY . /var/www/html
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN composer install --no-interaction --prefer-dist --no-scripts

EXPOSE 9000
CMD ["php-fpm"]