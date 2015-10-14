#! /bin/bash

echo "setting write permissions"

setfacl -R -m u:hagit:rwx -m u:www-data:rwx ./app/logs
setfacl -dR -m u:hagit:rwx -m u:www-data:rwx ./app/logs
setfacl -R -m u:hagit:rwx -m u:www-data:rwx ./app/cache
setfacl -dR -m u:hagit:rwx -m u:www-data:rwx ./app/cache


composer install

compass compile src/Wix/GoogleAdsenseBundle/Resources/public

php app/console cache:clear -e=prod
php app/console fos:js-routing:dump -e=prod
php app/console assetic:dump -e=prod
php app/console assets:install -e=prod

