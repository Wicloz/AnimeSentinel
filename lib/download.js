import {CloudKicker} from "cloudkicker/lib/index";

const cloudkicker = new CloudKicker();

downloadWithCallback = function(url, callback, tries=1) {
  cloudkicker.get(url).then(({options, response}) => {
    callback(response.body.toString());
  }).catch((error) => {
    if (tries >= 3) {
      console.error(error); // Failed downloading page
      callback(false);
    } else {
      downloadWithCallback(url, callback, tries+1);
    }
  });
};
