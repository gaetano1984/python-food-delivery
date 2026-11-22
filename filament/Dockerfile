FROM php:8.4-cli

# Estensioni richieste da Laravel 13 / Filament 5 (intl e zip sono obbligatorie per Filament)
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql intl zip gd bcmath exif \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# I sorgenti arrivano dal bind-mount; il comando di avvio è nel docker-compose.yaml
EXPOSE 8001
