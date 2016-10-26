#!/bin/bash
php artisan migrate --seed
nodejs ./node_modules/gulp/bin/gulp.js --production
