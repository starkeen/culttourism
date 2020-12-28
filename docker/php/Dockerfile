FROM php:7.4-apache
RUN apt-get update && apt-get install -y \
                          curl \
                          wget \
                          git \
                          libfreetype6-dev \
                          libjpeg62-turbo-dev \
                          libonig-dev \
                          libmcrypt-dev
RUN docker-php-ext-install -j$(nproc) mbstring pdo pdo_mysql tokenizer iconv mysqli gd fileinfo
RUN pecl install xdebug-3.0.1 && docker-php-ext-enable xdebug
RUN a2enmod rewrite
