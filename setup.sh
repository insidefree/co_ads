#! /bin/bash

composer install

compass compile src/Wix/GoogleAdsenseBundle/Resources/public

php app/console cache:clear -e=$1
php app/console fos:js-routing:dump -e=$1
php app/console assetic:dump -e=$1
php app/console assets:install -e=$1

