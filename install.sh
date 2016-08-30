#!/bin/bash
composer install
npm install
nodejs ./node_modules/gulp/bin/gulp.js --production
php artisan migrate --seed --force
