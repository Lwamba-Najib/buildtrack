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

# CRITICAL FIX: Forcefully delete any cached config files generated during composer install
# This ensures Laravel is forced to read your fresh config/database.php file
RUN rm -rf bootstrap/cache/*.php || true

# Fix permissions
RUN chmod -R 775 storage bootstrap/cache

EXPOSE $PORT

# Start the server directly without running config:clear to avoid boot-time database checks
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT