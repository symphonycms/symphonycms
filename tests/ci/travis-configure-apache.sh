#!/bin/bash

# from https://docs.travis-ci.com/user/languages/php/#Apache-%2B-PHP

# enable php-fpm.conf
sudo cp ~/.phpenv/versions/$(phpenv version-name)/etc/php-fpm.conf.default ~/.phpenv/versions/$(phpenv version-name)/etc/php-fpm.conf
# fix missing ServerName
echo "ServerName localhost" | sudo tee --append /etc/apache2/apache2.conf > /dev/null
# the next lines are for PHP 7 only, so we need to check if the source file exists
WWWCONF=~/.phpenv/versions/$(phpenv version-name)/etc/php-fpm.d/www.conf.default;
if [ -e "$WWWCONF" ]; then
    sudo cp $WWWCONF ~/.phpenv/versions/$(phpenv version-name)/etc/php-fpm.d/www.conf
else
    echo "No need to copy $WWWCONF"
fi;
# enale apache module
sudo a2enmod rewrite actions fastcgi alias
# enable fix_pathinfo
echo "cgi.fix_pathinfo = 1" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
# fix user/group config
sudo sed -i -e "s,www-data,travis,g" /etc/apache2/envvars
sudo chown -R travis:travis /var/lib/apache2/fastcgi
# start php-fpm
~/.phpenv/versions/$(phpenv version-name)/sbin/php-fpm
# configure apache virtual hosts
sudo cp -f tests/ci/travis-vhost.conf /etc/apache2/sites-available/000-default.conf
sudo sed -e "s?%TRAVIS_BUILD_DIR%?$(pwd)?g" --in-place /etc/apache2/sites-available/000-default.conf
# restart the service
sudo service apache2 restart
