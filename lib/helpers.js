RegExp.escape = function(string) {
  return string.replace(/[-\/\\^$*+?.()|[\]{}!=]/g, '\\$&')
};

String.prototype.cleanWhitespace = function() {
  return this.replace(/\n/g, ' ').replace(/\s+/g, ' ').trim();
};
