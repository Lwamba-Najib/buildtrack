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

# NUCLEAR FIX: Forcefully overwrite database.php with the exact correct pgsql config
RUN echo "<?php return ['default' => env('DB_CONNECTION', 'pgsql'), 'connections' => ['pgsql' => ['driver' => 'pgsql', 'url' => env('DATABASE_URL'), 'host' => env('DB_HOST', '127.0.0.1'), 'port' => env('DB_PORT', '5432'), 'database' => env('DB_DATABASE', 'forge'), 'username' => env('DB_USERNAME', 'forge'), 'password' => env('DB_PASSWORD', ''), 'charset' => 'utf8', 'prefix' => '', 'prefix_indexes' => true, 'search_path' => 'public', 'sslmode' => 'prefer']], 'migrations' => 'migrations'];" > config/database.php

# Delete any cached config files and the local .env file
RUN rm -f .env bootstrap/cache/config.php bootstrap/cache/packages.php bootstrap/cache/services.php

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Fix permissions
RUN chmod -R 775 storage bootstrap/cache

EXPOSE $PORT

# Start the server (NO config:clear to avoid boot-time errors)
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT