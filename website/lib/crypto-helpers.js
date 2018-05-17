import md5 from 'crypto-js/md5';
import Base64 from 'crypto-js/enc-base64';
import Utf8 from 'crypto-js/enc-utf8';

createWeakHash = function (data) {
  return md5(data.toString()).toString();
};

createUniqueId = function() {
  return createWeakHash(Date.now() + Math.random());
};

encodeBase64 = function(str) {
  return Base64.stringify(Utf8.parse(str));
};

decodeBase64 = function(str) {
  return Utf8.stringify(Base64.parse(str));
};
