FROM php:8.3-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libzip-dev libpq-dev zip unzip

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy backend files
COPY . /var/www/html

# Install dependencies and setup
RUN composer install --no-dev --optimize-autoloader
RUN if [ ! -f .env ]; then cp .env.example .env && php artisan key:generate; fi
RUN chmod -R 755 storage bootstrap/cache

# Start command using Render's PORT variable
CMD sh -c "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT"