import {CloudKicker} from "cloudkicker/lib/index";
import request from 'request';

const cloudkicker = new CloudKicker();

function isStatusCodeSuccess(statusCode) {
  statusCode = statusCode.toString();
  return statusCode.length === 3 && (statusCode.startsWith('1') || statusCode.startsWith('2') || statusCode.startsWith('3'));
}

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
      if (isStatusCodeSuccess(response.statusCode)) {
        callback(response.body.toString());
      }

      else {
        tryNextDownloadWithCallback(url, callback, tries, response.statusCode + ' ' + response.statusMessage);
      }
    },

    (err) => {
      tryNextDownloadWithCallback(url, callback, tries, err);
    }).

    catch((err) => {
      console.error(err);
    });
  }
};

function tryNextDownloadWithCallback(url, callback, tries, err) {
  if (tries >= 4) {
    console.error('Failed downloading ' + url + ' after ' + tries + ' tries.');
    console.error(err);
    callback(false);
  } else {
    _.delay(Meteor.bindEnvironment(downloadWithCallback), 200, url, callback, tries + 1);
  }
}

downloadToStream = function(url, callback, tries=1) {
  url = encodeURI(url).replace(/%25/g, '%');

  if (Meteor.isDevelopment) {
    console.log('Downloading: url \'' + url + '\', try \'' + tries + '\'');
  }

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
    if (isStatusCodeSuccess(response.statusCode)) {
      callback(response, response.headers['content-type'].split('; ')[0], Number(response.headers['content-length']));
    }

    else {
      tryNextDownloadToStream(url, callback, tries, response.statusCode + ' ' + response.statusMessage);
    }
  })).

  on('error', Meteor.bindEnvironment((err) => {
    tryNextDownloadToStream(url, callback, tries, err);
  }));
};

function tryNextDownloadToStream(url, callback, tries, err) {
  if (tries >= 4) {
    console.error('Failed downloading ' + url + ' after ' + tries + ' tries.');
    console.error(err);
    callback(false, false, false);
  } else {
    _.delay(Meteor.bindEnvironment(downloadToStream), 200, url, callback, tries + 1);
  }
}
