# Use an official PHP-Apache image
FROM php:8.2-apache

# Install system dependencies for GD and other extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/*

# Configure and install the GD extension
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd

# Install and enable the mysqli extension
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Tăng giới hạn tải file (max_file_uploads mặc định chỉ là 20 - quá ít cho Admin)
RUN { \
    echo 'max_file_uploads = 100'; \
    echo 'post_max_size = 512M'; \
    echo 'upload_max_filesize = 50M'; \
    echo 'memory_limit = 256M'; \
} > /usr/local/etc/php/conf.d/admin-upload.ini

# Copy the application code to the container
WORKDIR /var/www/html

# Adjust permissions for Apache to access files
RUN chown -R www-data:www-data /var/www/html
