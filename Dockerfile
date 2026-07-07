FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql \
    && a2enmod rewrite

COPY healthcare_dbms_project/ /var/www/html/

# Remove old database file to ensure fresh initialization
RUN rm -f /var/www/html/clinic.db

# Set proper permissions
RUN chmod 755 /var/www/html

EXPOSE 80
