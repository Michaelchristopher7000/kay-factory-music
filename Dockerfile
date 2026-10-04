FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    nodejs \
    npm \
    && docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    intl \
    zip \
    && a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy Laravel project
COPY . .

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Install JavaScript dependencies and build React/Vite
RUN npm ci && npm run build

# Configure Apache for Laravel
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Laravel permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

CMD ["apache2-foreground"]