import md5 from 'crypto-js/md5';

createWeakHash = function (data) {
  return md5(data.toString()).toString();
};

createUniqueId = function() {
  return createWeakHash(Date.now() + Math.random());
};
