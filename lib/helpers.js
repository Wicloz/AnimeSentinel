RegExp.escape = function(string) {
  return string.replace(/[-\/\\^$*+?.()|[\]{}!=]/g, '\\$&')
};
