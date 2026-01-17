FROM php:8.3-apache

# Install system dependencies for Symfony (e.g. zip, unzip, git, libzip)
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip vim \
    && docker-php-ext-install pdo_mysql zip

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Enable Apache modules (rewrite)
RUN a2enmod rewrite

COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Enable the sites
RUN a2ensite 000-default.conf

# Set working directory
WORKDIR /var/www/html

# 👇 Copy entire Symfony project
COPY . .

# ✅ Now everything (bin/, src/, vendor/) is there
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# uncomment this if you dont want to push /public/build and build it manually on prod server
# Install Node.js + yarn + build frontend assets
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && apt-get install -y nodejs
RUN npm install -g yarn
RUN yarn install
RUN yarn encore production

EXPOSE 80 443

RUN chown -R www-data:www-data var vendor
RUN chmod -R 775 var

# Clear and warm up cache
RUN php bin/console cache:clear
RUN php bin/console cache:warmup
