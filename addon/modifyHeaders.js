function modifyHeaders(response) {
  let origin = undefined;
  if (response.initiator) {
    origin = response.initiator;
  }
  else if (response.originUrl) {
    origin = response.originUrl.replace(/^(.*:\/\/[^\/]+).*$/, '$1');
  }

  if (origin && (origin.match(/^.*:\/\/development.wilcodeboer.me\/?$/) || origin.match(/^.*:\/\/anime.wilcodeboer.me\/?$/))) {
    response.responseHeaders = response.responseHeaders.filter((header) => {
      return !['x-frame-options', 'access-control-allow-origin', 'access-control-allow-credentials', 'access-control-allow-methods', 'access-control-allow-headers'].includes(header.name.toLowerCase());
    });

    response.responseHeaders.push({
      name: 'Access-Control-Allow-Origin',
      value: origin
    });
    response.responseHeaders.push({
      name: 'Access-Control-Allow-Credentials',
      value: 'true'
    });
    response.responseHeaders.push({
      name: 'Access-Control-Allow-Methods',
      value: response.method
    });

    response.responseHeaders.push({
      name: 'Access-Control-Allow-Headers',
      value: response.responseHeaders.map((header) => {
        return header.name;
      }).join(', ')
    });
  }

  return {responseHeaders: response.responseHeaders};
}

if (typeof browser !== 'undefined') {
  browser.webRequest.onHeadersReceived.addListener(
    modifyHeaders,
    {urls: ['<all_urls>']},
    ['blocking', 'responseHeaders']
  );
}

else if (typeof chrome !== 'undefined') {
  chrome.webRequest.onHeadersReceived.addListener(
    modifyHeaders,
    {urls: ['<all_urls>']},
    ['blocking', 'responseHeaders']
  );
}
