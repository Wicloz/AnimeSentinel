#!/bin/bash
php artisan migrate --seed --force
php artisan route:cache
nodejs ./node_modules/gulp/bin/gulp.js --production
