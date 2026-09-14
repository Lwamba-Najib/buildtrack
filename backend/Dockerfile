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

# Create a startup script that generates .env from environment variables
RUN echo '#!/bin/bash\n\
php artisan config:clear\n\
php artisan cache:clear\n\
php artisan migrate --force\n\
php artisan serve --host=0.0.0.0 --port=$PORT' > /start.sh && chmod +x /start.sh

EXPOSE $PORT

CMD ["/start.sh"]