php app/console cache:clear --env=prod --no-debug
chmod 777 app/cache/ -R
php app/console assetic:dump --env=prod --no-debug
