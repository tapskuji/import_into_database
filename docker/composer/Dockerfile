# This Dockerfile will install
# phpoffice/phpspreadsheet dependences,
FROM composer:2.0.2

#RUN apk update
#
####
### PHP Extensisons
####
#

RUN apk add --no-cache freetype libpng libjpeg-turbo freetype-dev libpng-dev libjpeg-turbo-dev
RUN docker-php-ext-configure gd
RUN docker-php-ext-install gd

WORKDIR /apps