# Wix Adsense

TODO

### requirements

### Composer

You need Composer installed globally:

```
$ curl -sS https://getcomposer.org/installer | php
```

```
$ sudo mv composer.phar /usr/local/bin/composer
```
### MongoDB

install mongoDB on your computer :

http://docs.mongodb.org/manual/administration/

### PHP

install php on your computer :

```
apt-get install php5-fpm
```

### Installation Wix-Adsense

Navigate to your folder :

```
$ cd <your project folder>
```

Clone the project:

```
$ git clone git@git.codeoasis.com:wix/wix-adsense.git
```

CD into your new project:

```
$ cd wix-adsense
```

Checkout to desired branch

```
$ git checkout <branch>
```

Install this file to complete:

```
./setup.sh
```

Copy parameters file to your project:

```
$ cp app/config/parameters.yml.dist app/config/parameters.yml
```

Change the mongodb_name .

Login to dev.wix.com/my-apps with user : codeoasisapps@gmail.com

and update in your project the right wix_app_id and wix_app_secret.

### Nginx

copy nginx file  to /etc/nginx/sites-enabled/ directory on your computer.

make sure the root attribute point to where your project lives;

make sure your server runs on port 9000;

Restart your nginx :

```bash
sudo service nginx restart
```

copy this line to your /etc/hosts file :

127.0.0.1       adsense.apps.wix.dev

open the project within your browser at adsense.apps.wix.dev

### Project hierarchy
    - wix-adsense
        - src
            - Wix
                - GoogleAdsenseBundle
                    - Resources
                        - public







