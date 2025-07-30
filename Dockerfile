FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git unzip zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . /var/www/html

RUN composer install --no-interaction --prefer-dist --no-scripts

RUN chown -R www-data:www-data var \
    && chmod -R 775 var

RUN mkdir -p var/cache/prod var/cache/dev \
    && chown -R www-data:www-data var \
    && chmod -R 775 var

EXPOSE 9000

CMD ["php-fpm"]