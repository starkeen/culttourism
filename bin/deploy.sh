#!/bin/sh

echo "deploy start"
echo 'pwd'
git fetch origin
php bin/composer.phar install
echo "deploy finish"
