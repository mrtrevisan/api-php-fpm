# dependencies are installed in the composer image, which has the tools to download packages
FROM composer:2 AS vendor

WORKDIR /app

# pdo_mysql only exists in the final image
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-autoloader --ignore-platform-req=ext-pdo_mysql

COPY src ./src
RUN composer dump-autoload --no-dev

FROM php:8.5-fpm-alpine

RUN docker-php-ext-install pdo_mysql

# production defaults: errors go to the log, never to the response
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY etc/php-fpm/zz-app.conf /usr/local/etc/php-fpm.d/zz-app.conf

WORKDIR /var/www

COPY --from=vendor /app/vendor ./vendor
COPY src ./src
# migrations and seeds (phinx), run inside this container
COPY phinx.php ./
COPY database ./database

CMD ["php-fpm"]
