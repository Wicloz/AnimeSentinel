window.addEventListener('message', (event) => {
  if (event.data && event.data.direction === 'from-page-script' && event.data.message === 'ready') {
    window.postMessage({
      direction: 'from-content-script',
      message: 'ready'
    }, '*');
  }
});
