# update application cache
composer install
cp .env.development .env
php artisan key:generate --force
php artisan optimize:clear
chmod -R 777 /var/www/bootstrap
chmod -R 777 /var/www/storage

# start the application
php-fpm -D &&  nginx -g "daemon off;"
