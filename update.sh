#!/bin/bash
php artisan down
git pull -r
node ./node_modules/gulp/bin/gulp.js --production
php artisan up
