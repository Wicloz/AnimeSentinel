#!/bin/bash
mkdir -p ./public/media/thumbnails

composer create-project
npm install

sudo pip install cfscrape==1.7.1
sudo pip install selenium
