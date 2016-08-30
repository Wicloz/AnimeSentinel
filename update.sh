#!/bin/bash
php artisan down
git pull -r
php artisan db:seed --force
php artisan queue:restart
nodejs ./node_modules/gulp/bin/gulp.js --production
php artisan up
