FROM php:8.3-fpm

USER root

WORKDIR /var/www

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libzip-dev git unzip && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd zip pdo pdo_mysql

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy needed files and folder
COPY ./docker/nginx.conf /etc/nginx/nginx.conf
COPY ./docker/build.sh /build.sh
COPY . .

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN chmod +rwx /var/www

RUN ["chmod", "+x", "/build.sh"]

CMD [ "sh", "/build.sh" ]
