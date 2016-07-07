#!/bin/bash
php artisan down
git pull -r
nodejs ./node_modules/gulp/bin/gulp.js --production
php artisan db:seed --class=StreamersSeeder
php artisan up
