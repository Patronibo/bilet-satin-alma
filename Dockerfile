FROM php:8.2-apache

# Install SQLite extensions for PDO and enable Apache rewrite
RUN apt-get update \
	&& apt-get install -y --no-install-recommends libsqlite3-dev \
	&& docker-php-ext-install pdo pdo_sqlite \
	&& a2enmod rewrite \
	&& rm -rf /var/lib/apt/lists/*

# Use provided Apache vhost
COPY apache/000-default.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

EXPOSE 80
