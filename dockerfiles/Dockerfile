# Dockerfile

# First Stage: Build
FROM debian:12 AS builder

# Set environment variables for non-interactive installs
ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC

# Install system dependencies
RUN apt-get update && apt-get install -y \
    curl \
    git \
    unzip \
    zip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    php8.2 \
    php8.2-cli \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-curl \
    php8.2-zip \
    php8.2-gd \
    php8.2-mysql \
    php8.2-intl \
    php8.2-bcmath \
    php8.2-soap \
    && apt-get clean

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

COPY backend/composer.json backend/composer.lock ./
ARG BUILD_ENV
# Install PHP dependencies
RUN if [ "$BUILD_ENV" = "production" ]; then composer install --no-dev --no-scripts; fi
RUN if [ "$BUILD_ENV" = "testing" ]; then composer install --no-scripts; fi
# Copy application code
COPY backend/app app
COPY backend/tests tests
COPY backend/resources resources
COPY backend/config config
COPY backend/routes routes
COPY backend/bootstrap bootstrap
COPY backend/database database
COPY backend/public public
COPY backend/storage storage
COPY backend/artisan backend/postcss.config.js backend/composer.lock backend/phpunit.xml backend/vite.config.js backend/composer.json backend/package.json backend/README.md backend/tailwind.config.js .

RUN mkdir -p /var/www/html/bootstrap/cache

# Install PHP dependencies without dev dependencies
RUN if [ "$BUILD_ENV" = "production" ]; then composer install --optimize-autoloader --no-dev; fi
RUN if [ "$BUILD_ENV" = "testing" ]; then composer install --optimize-autoloader; fi

# Second Stage: Runtime
FROM debian:12 AS runtime

# Install necessary PHP packages only
RUN apt-get update && apt-get install -y \
    curl \
    php8.2-fpm \
    php8.2-cli \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-curl \
    php8.2-zip \
    php8.2-gd \
    php8.2-mysql \
    && apt-get clean

# Copy PHP-FPM config for production (optional: if you need custom configs)
# COPY --from=builder /etc/php/8.2/fpm/php-fpm.conf /etc/php/8.2/fpm/php-fpm.conf

# Set working directory
WORKDIR /var/www/html

# Copy files from the build stage to the runtime stage
COPY --from=builder /var/www/html /var/www/html

# Set permissions for Laravel storage and cache directories
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN echo > .env

# Expose the port for PHP-FPM
EXPOSE 9000

EXPOSE 8000

# Start PHP-FPM
CMD ["php-fpm8.2", "-F"]
