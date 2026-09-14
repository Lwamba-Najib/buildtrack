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

# Create a minimal .env file for production
RUN echo "APP_NAME=BuildTrack" > .env && \
    echo "APP_ENV=production" >> .env && \
    echo "APP_DEBUG=false" >> .env && \
    echo "DB_CONNECTION=pgsql" >> .env && \
    echo "DB_HOST=\${DB_HOST:-127.0.0.1}" >> .env && \
    echo "DB_PORT=\${DB_PORT:-5432}" >> .env && \
    echo "DB_DATABASE=\${DB_DATABASE:-forge}" >> .env && \
    echo "DB_USERNAME=\${DB_USERNAME:-forge}" >> .env && \
    echo "DB_PASSWORD=\${DB_PASSWORD:-''}" >> .env

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Fix permissions
RUN chmod -R 775 storage bootstrap/cache

# Expose port and start
EXPOSE $PORT
CMD php artisan config:clear && php artisan cache:clear && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT