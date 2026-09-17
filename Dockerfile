# ==============================================================================
# MindCare Counselling Service - Production Dockerfile
# Optimized for Apache, PHP 8.2, PDO MySQL, and Azure Deployments
# ==============================================================================

FROM php:8.2-apache

# Metadata
LABEL maintainer="MindCare Team"
LABEL description="MindCare Mental Health Counselling Platform"

# Configure Apache environment variables
ENV APACHE_DOCUMENT_ROOT=/var/www/html
ENV PORT=80

# Install required system packages, build tools, and PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    zip \
    unzip \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mysqli \
        gd \
        zip \
        mbstring \
    && a2enmod rewrite headers \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configure Apache VirtualHost with AllowOverride All for clean routing & .htaccess
RUN echo '<VirtualHost *:80>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html\n\
    <Directory /var/www/html>\n\
        Options -Indexes +FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Configure production PHP settings
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 10M/g' "$PHP_INI_DIR/php.ini" \
    && sed -i 's/post_max_size = 8M/post_max_size = 12M/g' "$PHP_INI_DIR/php.ini" \
    && sed -i 's/memory_limit = 128M/memory_limit = 256M/g' "$PHP_INI_DIR/php.ini"

# Set working directory
WORKDIR /var/www/html

# Copy application source code into the container
COPY . /var/www/html/

# Set appropriate ownership and permissions
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && if [ -d "/var/www/html/frontend/assets/images/profiles" ]; then \
           chmod -R 775 /var/www/html/frontend/assets/images/profiles; \
       fi

# Expose HTTP port
EXPOSE 80

# Run Apache in foreground
CMD ["apache2-foreground"]
