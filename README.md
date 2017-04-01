# AnimeSentinel ![Travis CI Status](https://travis-ci.org/Wicloz/AnimeSentinel.svg?branch=master)
This project will (eventually) aim to create THE streaming site, by streaming videos available on a lot of other streaming sites.
It will also focus on delivering notifications when new episodes come out so you'll never miss them again.

Features will include:
- Integrate with MyAnimeList so it knows what you are watching.
- Send you a mail or update to an RSS feed when a new episode for an anime you're watching becomes available.
- Index which anime are streamed on which locations. Since this is A LOT of data, it must be done dynamically. As in, only start searching for relevant data once a user requests it, then store it forever.
- <strike>Provide links to locations where a certain anime is streamed. Possibly</strike> stream those video's directly from this site.

UPDATE: The site should be live here, but with questionable stablity: https://anime.wilcodeboer.me/

Currently I'm not hosting on any potato, but a potato with the weight, size, loudness and temperature of a jet engine. So I really need an actual server:
https://sharex.wilcodeboer.me/?id=nFxQ1CXvBayp61S

Guide for development servers (WIP):
- Required software:
  - ffmpeg
  - pip
    - cfscrape
    - selenium
  - firefox
  - xvfb
- Included in Homestead:
  - composer
  - python >= 2.5
  - a recent version of nodejs and npm
  - a webserver
  - a database
  - openssl
  - php7.0, php7.0-curl, other php extensions
  - php database extension
