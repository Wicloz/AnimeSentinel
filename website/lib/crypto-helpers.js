import md5 from 'crypto-js/md5';

createWeakHash = function (data) {
  return md5(data.toString()).toString();
};

createUniqueId = function() {
  return createWeakHash(Date.now() + Math.random());
};

encodeBase64 = function(str) {
  return btoa(encodeURIComponent(str).replace(/%([0-9A-F]{2})/g, (match, p1) => {
    return String.fromCharCode('0x' + p1);
  }));
};

decodeBase64 = function(str) {
  return decodeURIComponent(atob(str).split('').map((c) => {
    return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
  }).join(''));
};
