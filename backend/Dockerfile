FROM php:8.3-cli

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libzip-dev libpq-dev zip unzip \
    && docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip

WORKDIR /var/www/html

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# CRITICAL FIX: Delete cached config and local .env so they don't override Render's variables
RUN rm -f .env bootstrap/cache/config.php bootstrap/cache/packages.php bootstrap/cache/services.php

# Fix permissions
RUN chmod -R 775 storage bootstrap/cache

EXPOSE $PORT

# Start the server
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT