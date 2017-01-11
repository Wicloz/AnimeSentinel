#!/bin/bash
php artisan migrate --seed --force
nodejs ./node_modules/gulp/bin/gulp.js --production
