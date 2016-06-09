#!/bin/sh

echo "deploy start"
pwd
git fetch origin
php bin/composer.phar install
echo "deploy finish"
