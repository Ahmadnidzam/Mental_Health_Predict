# Mental Health Risk Prediction - Dockerfile untuk Coolify / Docker deployment
# Container ini berisi PHP 8.2 (Apache) + Python 3.11 + sklearn dalam 1 image
# supaya Laravel bisa langsung memanggil Python predict.py via Symfony Process.

FROM php:8.2-apache

# ==============================================================================
# 1. Install system dependencies + Python 3
# ==============================================================================
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        zip \
        curl \
        libpng-dev libjpeg-dev libfreetype6-dev \
        libxml2-dev libonig-dev libzip-dev \
        default-mysql-client \
        python3 python3-pip python3-venv \
    && rm -rf /var/lib/apt/lists/*

# ==============================================================================
# 2. PHP extensions yang dibutuhkan Laravel
# ==============================================================================
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        xml \
        zip \
        gd \
        bcmath \
        opcache

# Composer (copy binary dari official image)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ==============================================================================
# 3. Apache config: document root ke /public, enable mod_rewrite
# ==============================================================================
RUN a2enmod rewrite headers
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# ==============================================================================
# 4. Install Python dependencies
# ==============================================================================
COPY storage/models/requirements.txt /tmp/requirements.txt
RUN pip3 install --no-cache-dir --break-system-packages -r /tmp/requirements.txt

WORKDIR /var/www/html

# ==============================================================================
# 5. Install PHP dependencies (composer)
# ==============================================================================
# Copy composer files dulu agar layer cache valid kalau hanya source code berubah
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# ==============================================================================
# 6. Copy seluruh source code
# ==============================================================================
COPY . .
RUN composer dump-autoload --optimize --no-dev

# ==============================================================================
# 7. Permissions untuk folder yang ditulis runtime
# ==============================================================================
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# ==============================================================================
# 8. Entrypoint: jalankan migrate + seed otomatis lalu start Apache
# ==============================================================================
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
