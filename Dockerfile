FROM php:8.2-apache

# Enable cURL extension for Supabase API requests
RUN apt-get update && apt-get install -y libcurl4-openssl-dev pkg-config \
    && docker-php-ext-install curl

# Copy project files to Apache web directory
COPY . /var/www/html/

EXPOSE 80
