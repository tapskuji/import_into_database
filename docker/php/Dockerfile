# This Dockerfile will install
# mysql, xdebug, vim, git, composer (dependences),
FROM php:7.4-cli

RUN apt-get update

###
## PHP Extensisons
###

## Install vim and git and ps command
RUN apt-get install --yes vim git procps

## Install zip libraries and extension
RUN apt-get install --yes zlib1g-dev libzip-dev \
    && docker-php-ext-install zip

# internationalization mechanisms (https://pub.dev/packages/intl)
## Install intl library and extension
RUN apt-get install --yes libicu-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl

###
## Optional PHP extensions
###

## mysql and mariadb
RUN apt-get install -y mariadb-client
RUN docker-php-ext-install mysqli pdo_mysql

#COPY . /apps
WORKDIR /apps

#CMD ["php", "/path/to/script/script.php"]