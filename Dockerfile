FROM php:8.3-apache

WORKDIR /var/www/html

# Install dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zlib1g-dev \
    libicu-dev \
    supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql bcmath gd zip intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy custom Apache config
COPY docker/apache/laravel.conf /etc/apache2/sites-available/000-default.conf

# Enable mod_rewrite
RUN a2enmod rewrite

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy application source code
COPY . .

# Install project dependencies
RUN composer install --optimize-autoloader --no-dev

# Clear cache
RUN php artisan route:cache
RUN php artisan config:cache
RUN php artisan view:cache

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache

# Copy supervisor config file
COPY docker/supervisor/laravel-worker.conf /etc/supervisor/conf.d/laravel-worker.conf

# Expose port 80
EXPOSE 80

# Start supervisor and apache in foreground
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
