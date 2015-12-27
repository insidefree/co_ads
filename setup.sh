#! /bin/bash

composer install

compass compile src/Wix/GoogleAdsenseBundle/Resources/public

php app/console cache:clear -e=$l
php app/console fos:js-routing:dump -e=$l
php app/console assetic:dump -e=$l
php app/console assets:install -e=$l

