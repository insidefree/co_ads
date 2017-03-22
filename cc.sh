#php app/console cache:clear --env=$1 --no-debug
#chmod 777 app/cache/ -R
#php app/console assetic:dump --env=$1 --no-debug

app/console cache:clear -e=$1
app/console assets:install -e=$1
app/console assetic:dump -e=$1
