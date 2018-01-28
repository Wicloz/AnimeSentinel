function modifyHeaders(response) {
  response.responseHeaders = response.responseHeaders.filter((header) => {
    return header.name.toLowerCase() !== "x-frame-options";
  });

  // console.log(response.responseHeaders.map((header) => {
  //   return header.name.toLowerCase();
  // }));
  return {responseHeaders: response.responseHeaders};
}

browser.webRequest.onHeadersReceived.addListener(
  modifyHeaders,
  {urls: ["<all_urls>"]},
  ["blocking", "responseHeaders"]
);
