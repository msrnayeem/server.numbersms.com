FROM php:8.2-apache

# Install necessary PHP extensions
RUN docker-php-ext-install pdo_mysql

# Enable Apache mod_rewrite for Laravel
RUN a2enmod rewrite

# Set the working directory
WORKDIR /var/www/html

# Copy the project files
COPY . /var/www/html

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Install Supervisor to manage multiple processes
RUN apt-get update && apt-get install -y supervisor

# Copy Supervisor configuration
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Start Supervisor (which runs both Apache and schedule:work)
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]


