import {CloudKicker} from "cloudkicker/lib/index";

const cloudkicker = new CloudKicker();

downloadWithCallback = function(url, callback, tries=1) {
  url = encodeURI(url).replace(/%25/g, '%');

  if (Meteor.isDevelopment) {
    console.log('Downloading: url \'' + url + '\', try \'' + tries + '\'');
  }

  cloudkicker.get(url).then(({options, response}) => {
    callback(response.body.toString());
  }, (err) => {
    if (tries >= 3) {
      console.error('Failed downloading ' + url + ' after ' + tries + ' tries.');
      console.error(err);
      if (Meteor.isServer) {
        callback(false);
      }
    } else {
      downloadWithCallback(url, callback, tries + 1);
    }
  }).catch((err) => {
    console.error(err);
  });
};
