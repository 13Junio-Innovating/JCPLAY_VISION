FROM php:8.2-apache

# Enable mod_rewrite for .htaccess support
RUN a2enmod rewrite

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Copy build artifacts to web root
COPY ./public /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html
