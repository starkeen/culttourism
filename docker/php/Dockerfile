FROM php:8.1.26-apache
RUN apt-get update && apt-get install -y \
                          curl \
                          wget \
                          git \
                          libfreetype6-dev \
                          libjpeg62-turbo-dev \
                          libonig-dev \
                          libmcrypt-dev \
    && apk cache clean
RUN docker-php-ext-configure gd --with-jpeg
RUN docker-php-ext-install -j$(nproc) mbstring pdo pdo_mysql iconv mysqli gd fileinfo
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug
RUN a2enmod rewrite
