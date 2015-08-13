#! /bin/bash

compass compile src/Wix/GoogleAdsenseBundle/Resources/public

php app/console cache:clear -e=prod
php app/console fos:js-routing:dump -e=prod
php app/console assetic:dump -e=prod
php app/console assets:install -e=prod

