FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    zip \
    unzip \
    && docker-php-ext-install \
        pdo_pgsql \
        pgsql \
        zip \
        intl \
        mbstring \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# PHP configuration
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
COPY php.ini-override /usr/local/etc/php/conf.d/99-guard51.ini

# Set working directory
WORKDIR /var/www/api

# Create non-root user
RUN groupadd -g 1000 guard51 && \
    useradd -u 1000 -ms /bin/bash -g guard51 guard51

# Set permissions
RUN chown -R guard51:guard51 /var/www

USER guard51

EXPOSE 9000
CMD ["php-fpm"]
