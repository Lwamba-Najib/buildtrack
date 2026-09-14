FROM php:8.3-cli

# Install system dependencies, PHP extensions, and Node.js
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libzip-dev libpq-dev zip unzip \
    nodejs npm \
    && docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip

WORKDIR /var/www/html

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy ALL files from root (both frontend and backend)
COPY . .

# Install backend dependencies
RUN cd backend && composer install --no-dev --optimize-autoloader

# Install and build frontend
RUN cd frontend && npm install && npm run build

# Copy built frontend assets to Laravel's public directory
RUN cp -r frontend/dist/* backend/public/

# Delete cached config files
RUN rm -f backend/bootstrap/cache/config.php backend/bootstrap/cache/packages.php backend/bootstrap/cache/services.php

# Fix permissions
RUN chmod -R 775 backend/storage backend/bootstrap/cache

EXPOSE $PORT

# Start Laravel server from backend directory
CMD cd backend && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT