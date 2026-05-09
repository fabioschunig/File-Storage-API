FROM php:8.0-apache

RUN apt-get update && apt-get install -y \
    libzip-dev \
    && docker-php-ext-install \
    mysqli \
    pdo_mysql \
    zip

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar DocumentRoot para /public
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite
