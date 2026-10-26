FROM php:8.4-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libonig-dev libsqlite3-dev libzip-dev libicu-dev \
    && docker-php-ext-install -j"$(nproc)" mbstring pdo_sqlite zip intl bcmath pcntl \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install -j"$(nproc)" gd \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/restaurant-entrypoint
RUN sed -i 's/\r$//' /usr/local/bin/restaurant-entrypoint \
    && chmod +x /usr/local/bin/restaurant-entrypoint

WORKDIR /var/www/html
ENTRYPOINT ["restaurant-entrypoint"]
CMD ["apache2-foreground"]
