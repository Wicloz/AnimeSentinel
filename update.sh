#!/bin/bash
composer install
npm update

sudo pip install cfscrape --upgrade
sudo pip install selenium --upgrade

sudo npm update -g phantomjs-prebuilt
