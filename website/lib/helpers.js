RegExp.escape = function(string) {
  return string.replace(/[-\/\\^$*+?.()|\[\]{}!=:,]/g, '\\$&');
};

RegExp.makeMatchWS = function(string) {
  let wsRegex = '[\\s\\f\\n\\r\\t\u1680\u180e\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u2028\u2029\u202f\u205f\u3000\ufeff\x09\x0a\x0b\x0c\x0d\x20\xa0]';
  return string.replace(new RegExp(wsRegex, 'g'), wsRegex);
};

String.prototype.cleanWhitespace = function(leaveEnd=false) {
  let trimmed = leaveEnd ? this.trimStart() : this.trim();
  return trimmed.replace(/\s+/g, ' ');
};

String.prototype.cleanQuery = function() {
  return this.cleanWhitespace().toLowerCase();
};

encodeURIComponentReplaceSpaces = function(string, spacer=undefined) {
  string = encodeURIComponent(string);
  if (typeof spacer === 'undefined') {
    return string;
  } else {
    return string.replace(/%20/g, spacer);
  }
};

String.prototype.count = function(string) {
  return (this.match(new RegExp(RegExp.escape(string), 'g')) || []).length;
};

Array.prototype.empty = function() {
  return this.length === 0;
};

Array.prototype.hasPartialObjects = function(object) {
  return this.some((value) => {
    return Object.keys(object).every((key) => {
      return value[key] === object[key];
    });
  });
};

Array.prototype.getPartialObjects = function(object) {
  return this.filter((value) => {
    return Object.keys(object).every((key) => {
      return value[key] === object[key];
    });
  });
};

Array.prototype.replacePartialObjects = function(oldObject, newObject) {
  return this.map((value) => {
    if (Object.keys(oldObject).every((key) => {
        return value[key] === oldObject[key];
      })) {
      return newObject;
    }
    return value;
  });
};

isNumeric = function(num) {
  return !isNaN(parseFloat(num)) && isFinite(num);
};

String.prototype.capitalize = function() {
  return this.charAt(0).toUpperCase() + this.substr(1);
};

String.prototype.replaceEnd = function(from, to, caseInsensitive=false) {
  if (from.source) {
    from = from.source;
    caseInsensitive = caseInsensitive || from.ignoreCase;
  } else {
    from = RegExp.escape(from);
  }

  let flags = caseInsensitive ? 'i' : '';
  let regex = new RegExp(from + '$', flags);

  return this.replace(regex, to);
};

String.prototype.replaceStart = function(from, to, caseInsensitive=false) {
  if (from.source) {
    from = from.source;
    caseInsensitive = caseInsensitive || from.ignoreCase;
  } else {
    from = RegExp.escape(from);
  }

  let flags = caseInsensitive ? 'i' : '';
  let regex = new RegExp('^' + from, flags);

  return this.replace(regex, to);
};

String.prototype.ensureStart = function(start) {
  if (this.startsWith(start)) {
    return this;
  } else {
    return start + this;
  }
};

String.prototype.replaceFull = function(from, to, caseInsensitive=false) {
  if (from.source) {
    from = from.source;
    caseInsensitive = caseInsensitive || from.ignoreCase;
  } else {
    from = RegExp.escape(from);
  }

  let flags = caseInsensitive ? 'i' : '';
  let regex = new RegExp('^' + from + '$', flags);

  return this.replace(regex, to);
};

Object.countNonEmptyValues = function(object) {
  return Object.keys(object).filter((key) => {
    return (object[key] || object[key] === false || object[key] === 0) && (typeof object[key].length === 'undefined' || object[key].length !== 0);
  }).length;
};

Array.prototype.pluck = function(key) {
  return this.map((value) => {
    return value[key];
  });
};

Array.prototype.peek = function() {
  return this[this.length - 1];
};

Number.prototype.mod = function(n) {
  return ((this % n) + n) % n;
};

tryGetProperty = function(object, property) {
  if (_.isObject(object) && object.hasOwnProperty(property)) {
    return object[property];
  } else {
    return undefined;
  }
};
