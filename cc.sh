sudo php app/console cache:clear --env=prod --no-debug
sudo php chmod 777 app/cache/ -R
sudo php app/console assetic:dump --env=prod --no-debug
