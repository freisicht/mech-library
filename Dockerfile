FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libfreetype6-dev libjpeg62-turbo-dev libpng-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf 'memory_limit = 256M\n' > "$PHP_INI_DIR/conf.d/mechlib.ini"

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && printf '<Directory ${APACHE_DOCUMENT_ROOT}>\n    AllowOverride All\n    Require all granted\n</Directory>\n' \
        > /etc/apache2/conf-available/mechlib.conf \
    && a2enconf mechlib \
    && a2enmod rewrite

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Dependencies first, so code changes do not invalidate this layer.
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/root/.cache/composer \
    composer install --no-dev --no-scripts --no-autoloader --no-interaction --no-progress --prefer-dist

COPY . .
RUN composer dump-autoload --no-dev --optimize --no-interaction

COPY docker/entrypoint.sh /usr/local/bin/mechlib-entrypoint
RUN chmod +x /usr/local/bin/mechlib-entrypoint

HEALTHCHECK --interval=10s --timeout=5s --start-period=30s --retries=5 \
    CMD php -r 'exit(@file_get_contents("http://localhost/auth/login") === false ? 1 : 0);'

ENTRYPOINT ["mechlib-entrypoint"]
CMD ["apache2-foreground"]
