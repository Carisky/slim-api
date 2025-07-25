FROM php:8.2-cli

# Установим необходимые расширения
RUN docker-php-ext-install pdo pdo_mysql

# Установим Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
