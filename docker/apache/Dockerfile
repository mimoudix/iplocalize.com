FROM php:8.3-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip vim \
    && docker-php-ext-install pdo_mysql zip

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Enable Apache modules (rewrite)
RUN a2enmod rewrite
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
RUN a2ensite 000-default.conf

WORKDIR /var/www/html

# Copy the project files
COPY . .

# --- DYNAMIC ARGUMENT LOGIC ---
# This is set to 'prod' by your GitHub Workflow
ARG APP_ENV=dev
ENV APP_ENV=${APP_ENV}

# If APP_ENV is prod, we skip dev dependencies (like maker-bundle)
RUN if [ "$APP_ENV" = "prod" ]; then \
        composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev; \
    else \
        composer install --no-interaction --prefer-dist; \
    fi

# Build frontend assets
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && apt-get install -y nodejs
RUN npm install -g yarn
RUN yarn install
RUN if [ "$APP_ENV" = "prod" ]; then yarn encore production; else yarn encore dev; fi

# 1. Ensure the directory structure exists
RUN mkdir -p var/cache var/log

# 2. Warm up cache FIRST (while still root)
RUN php bin/console cache:clear --env=${APP_ENV}
RUN php bin/console cache:warmup --env=${APP_ENV}

# 3. PERFORM THE GLOBAL HANDOVER LAST
# This ensures even the files created by the cache:warmup 
# command are now owned by Apache.
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 775 /var/www/html/var /var/www/html/vendor

EXPOSE 80 443
