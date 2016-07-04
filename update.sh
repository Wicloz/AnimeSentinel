#!/bin/bash
php artisan down
git pull -r
php artisan up
