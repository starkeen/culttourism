#!bin/sh


  echo "deploy start"

  cd /home/s/starkeen/culttourism.ru/public_html
  /usr/local/bin/php bin/composer.phar install

  echo "deploy finish"
