function modifyHeaders(response) {
  // console.log(response);

  if (response.originUrl.match(/^.*:\/\/development.wilcodeboer.me/) || response.originUrl.match(/^.*:\/\/anime.wilcodeboer.me/)) {
    response.responseHeaders = response.responseHeaders.filter((header) => {
      return !['x-frame-options', 'access-control-allow-origin', 'access-control-allow-credentials', 'access-control-allow-methods', 'access-control-allow-headers'].includes(header.name.toLowerCase());
    });

    response.responseHeaders.push({
      name: 'Access-Control-Allow-Origin',
      value: response.originUrl.replace(/^(.*:\/\/[^\/]+).*$/, '$1')
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

  // console.log(response.responseHeaders);
  return {responseHeaders: response.responseHeaders};
}

browser.webRequest.onHeadersReceived.addListener(
  modifyHeaders,
  {urls: ['<all_urls>']},
  ['blocking', 'responseHeaders']
);
