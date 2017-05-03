#!/bin/bash
php artisan down
git pull -r
composer install
npm install
sudo pip install --upgrade selenium
php artisan db:seed --force
php artisan route:cache
php artisan queue:restart
nodejs ./node_modules/gulp/bin/gulp.js --production
php artisan up
