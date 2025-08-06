FROM php:7.2-fpm
COPY php.ini /usr/local/etc/php/

RUN sed -i s/deb.debian.org\\/debian\ stretch-updates/archive.debian.org\\/debian\ stretch/g /etc/apt/sources.list
RUN sed -i s/deb.debian.org\\/debian\ stretch/archive.debian.org\\/debian\ stretch/g /etc/apt/sources.list
RUN sed -i s/security.debian.org\\/debian-security\ stretch/archive.debian.org\\/debian-security\ stretch/g /etc/apt/sources.list

RUN apt-get update \
    && apt-get install -y zip

RUN pecl install xdebug-3.1.6
RUN docker-php-ext-enable xdebug

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --2.2
RUN php -r "unlink('composer-setup.php');"
RUN mv composer.phar /usr/local/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_HOME /composer
ENV PATH $PATH:/composer/vendor/bin
