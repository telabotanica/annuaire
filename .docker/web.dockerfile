FROM php:7.2-apache

COPY . /app
COPY ./.docker/vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /app
RUN apt-get update
RUN apt-get upgrade -y
RUN apt-get install wget -y
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Install composer
RUN wget https://raw.githubusercontent.com/composer/getcomposer.org/76a7060ccb93902cd7576b67264ad91c8a2700e2/web/installer -O - -q | php -- --quiet
RUN mv composer.phar /usr/local/bin/composer

RUN a2enmod rewrite
RUN composer install --no-dev --no-interaction
