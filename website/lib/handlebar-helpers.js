if (typeof UI !== 'undefined') {
  UI.registerHelper('$in', function (a, ...list) {
    list.pop();
    if (list.length === 1 && Array.isArray(list[0])) {
      list = list[0];
    }
    return list.includes(a);
  });

  UI.registerHelper('$nin', function (a, ...list) {
    list.pop();
    if (list.length === 1 && Array.isArray(list[0])) {
      list = list[0];
    }
    return !list.includes(a);
  });
}
