import {CloudKicker} from "cloudkicker/lib/index";
import request from 'request';

const cloudkicker = new CloudKicker();

downloadWithCallback = function(url, callback, tries=1) {
  // TODO: Fix database stuff so the client can download too
  // if (Meteor.isServer || Session.get('AddOnInstalled')) {

  if (Meteor.isServer) {
    url = encodeURI(url).replace(/%25/g, '%');

    if (Meteor.isDevelopment) {
      console.log('Downloading: url \'' + url + '\', try \'' + tries + '\'');
    }

    if (Meteor.isClient && url.startsWith('http://')) {
      // TODO: Fix http downloads on the client
      callback(false);
      return;
    }

    cloudkicker.get(url).then(({options, response}) => {
      if (Meteor.isDevelopment) {
        console.log('Downloaded: url \'' + response.request.href + '\', status \'' + response.statusCode + ' ' + response.statusMessage + '\'');
      }
      callback(response.body.toString());
    },

    (err) => {
      maybeNextDownload(url, callback, tries, err);
    }).

    catch((err) => {
      if (err === 'Download Failed!') {
        maybeNextDownload(url, callback, tries, err);
      } else {
        console.error(err);
      }
    });
  }
};

function maybeNextDownload(url, callback, tries, err) {
  if (tries >= 3) {
    console.error('Failed downloading ' + url + ' after ' + tries + ' tries.');
    console.error(err);
    callback(false);
  } else {
    downloadWithCallback(url, callback, tries + 1);
  }
}

downloadToStream = function(url, callback, tries=1) {
  url = encodeURI(url).replace(/%25/g, '%');

  let options = {
    encoding: null,
    jar: cloudkicker.cookieJar,
    headers: {
      'User-Agent': cloudkicker.options.userAgent
    },
    url: url,
  };

  request.get(options).

  on('response', Meteor.bindEnvironment((response) => {
    callback(response, response.headers['content-type'].split('; ')[0]);
  })).

  on('error', Meteor.bindEnvironment((err) => {
    if (tries >= 3) {
      console.error('Failed downloading ' + url + ' after ' + tries + ' tries.');
      console.error(err);
      callback(false, false);
    } else {
      downloadToStream(url, callback, tries + 1);
    }
  }));
};
