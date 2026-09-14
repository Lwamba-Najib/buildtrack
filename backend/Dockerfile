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

# Fix permissions
RUN chmod -R 775 storage bootstrap/cache

EXPOSE $PORT

# Safely generate .env file by quoting all values (fixes DATABASE_URL parsing), 
# then clear caches, run migrations, and start the server.
CMD sh -c "env | sed 's/=\(.*\)/=\"\1\"/' > .env && php artisan config:clear && php artisan cache:clear && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT"