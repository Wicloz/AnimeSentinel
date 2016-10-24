#!/bin/bash
curl -o- https://raw.githubusercontent.com/creationix/nvm/v0.32.1/install.sh | bash
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"
nvm install node

composer create-project
npm install

sudo pip install cfscrape
sudo pip install selenium

npm install phantomjs-prebuilt
