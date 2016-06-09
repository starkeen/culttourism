#!/bin/sh

echo "deploy start"

git fetch origin
php /home/s/starkeen/culttourism.ru/public_html/bin/composer.phar install

echo "deploy finish"
