FROM php:8.4-fpm
COPY php.ini /usr/local/etc/php/

RUN apt-get update \
    && apt-get install -y zip

RUN pecl install xdebug-3.4.3
RUN docker-php-ext-enable xdebug

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --2.2
RUN php -r "unlink('composer-setup.php');"
RUN mv composer.phar /usr/local/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_HOME /composer
ENV PATH $PATH:/composer/vendor/bin
