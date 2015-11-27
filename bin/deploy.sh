#!/bin/sh

echo "deploy start"
git fetch origin
bin/composer install
echo "deploy finish"
