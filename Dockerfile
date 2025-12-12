FROM php:8.3-fpm

USER root

WORKDIR /var/www

# Install system dependencies including nginx
RUN apt-get update && apt-get install -y \
    nginx \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    git \
    curl \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip pdo pdo_mysql mbstring bcmath opcache

# Install PHP opcache for production performance
RUN docker-php-ext-enable opcache

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy nginx configuration
COPY ./docker/nginx.conf /etc/nginx/nginx.conf

# Copy PHP-FPM configuration
COPY ./docker/www.conf /usr/local/etc/php-fpm.d/www.conf

# Copy application source code
COPY . .

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy and prepare build script
COPY ./docker/build.sh /build.sh
RUN chmod +x /build.sh

# Set proper permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

# Expose port 80
EXPOSE 80

CMD ["/build.sh"]
