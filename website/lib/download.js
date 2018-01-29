import {CloudKicker} from "cloudkicker/lib/index";

const cloudkicker = new CloudKicker();

downloadWithCallback = function(url, callback, tries=1) {
  if (Meteor.isServer || Session.get('AddOnInstalled')) {
    url = encodeURI(url).replace(/%25/g, '%');

    if (Meteor.isDevelopment) {
      console.log('Downloading: url \'' + url + '\', try \'' + tries + '\'');
    }

    cloudkicker.get(url).then(({options, response}) => {
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
