FROM php:8.0-apache

# Copy application files to the web server's document root
COPY ./src /var/www/html/

# Expose port 80
EXPOSE 80 