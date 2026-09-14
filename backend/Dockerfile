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

# 🔍 DEBUG STEP: Print the exact database.php file that Docker copied into the container
RUN echo "========== START OF config/database.php ==========" && cat config/database.php && echo "========== END OF config/database.php =========="

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Delete any cached config files that might override database.php
RUN rm -rf bootstrap/cache/*.php || true

# Fix permissions
RUN chmod -R 775 storage bootstrap/cache

EXPOSE $PORT

# Start the server
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT