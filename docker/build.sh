#!/bin/bash

set -e

echo "==> Starting WCAG Accessibility API deployment..."

# Install PHP dependencies
echo "==> Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Setup environment
echo "==> Setting up environment..."
if [ ! -f .env ]; then
    cp .env.development .env
fi

# Generate application key if not set
if [ -z "$APP_KEY" ]; then
    echo "==> Generating application key..."
    php artisan key:generate --force
fi

# Optimize the application for production
echo "==> Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set proper permissions
echo "==> Setting permissions..."
chmod -R 775 /var/www/storage
chmod -R 775 /var/www/bootstrap/cache
chown -R www-data:www-data /var/www/storage
chown -R www-data:www-data /var/www/bootstrap/cache

echo "==> Starting PHP-FPM and Nginx..."
php-fpm -D
nginx -g "daemon off;"
