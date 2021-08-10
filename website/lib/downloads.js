import request from 'request';

function isStatusCodeSuccess(statusCode) {
  statusCode = statusCode.toString();
  return statusCode.length === 3 && (statusCode.startsWith('1') || statusCode.startsWith('2') || statusCode.startsWith('3'));
}

function failMessage(method, url, status) {
  if (Meteor.isDevelopment) {
    console.error('Failed \'' + method + '\' request to \'' + url + '\'' + ' (' + status + ')');
  }
}

rp = function (method, url, chisel, config = {}, bodyFailures = []) {
  return new Promise((resolve, reject) => {
    url = encodeURI(url).replace(/%25/g, '%');
    config.url = (chisel ? 'https://chisel.wilcodeboer.me/api/' : '') + url;
    config.method = method;
    config.followAllRedirects = true;

    // TODO: Fix database stuff so the client can download too
    // if (config.url && (Meteor.isServer || Session.get('AddOnInstalled'))) {
    if (config.url && Meteor.isServer) {
      if (Meteor.isDevelopment) {
        console.log('Sending \'' + config.method + '\' request to \'' + url + '\'');
      }

      request(config, (error, res, body) => {
        let status = res ? res.statusCode : 'interrupted'

        if (error) {
          failMessage(config.method, url, status);
          reject(error);
        } else if (!isStatusCodeSuccess(status)) {
          failMessage(config.method, url, status);
          reject(status);
        } else {
          let bodyFailed = false;
          bodyFailures.forEach((bodyFailure) => {
            if (body.includes(bodyFailure)) {
              failMessage(config.method, url, status);
              reject(bodyFailure);
              bodyFailed = true;
            }
          });
          if (!bodyFailed) {
            resolve(body);
          }
        }
      });
    }

    else {
      failMessage(config.method, url, 404);
      reject(false);
    }
  });
};
