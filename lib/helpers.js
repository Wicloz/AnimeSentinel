RegExp.escape = function(string) {
  return string.replace(/[-\/\\^$*+?.()|[\]{}!=]/g, '\\$&')
};

String.prototype.cleanEntersAndSpaces = function() {
  return this.replace(/\n/g, ' ').replace(/ +/g, ' ').trim();
};
