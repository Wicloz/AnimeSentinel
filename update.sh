#!/bin/bash
composer install
npm install

pip install cfscrape --upgrade
pip install selenium --upgrade

npm update phantomjs
