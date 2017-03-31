#!/bin/bash
mkdir -p ./public/media/thumbnails

composer create-project
npm install

sudo pip install cfscrape
sudo pip install selenium

sudo npm install -g phantomjs-prebuilt
