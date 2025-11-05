self.addEventListener('install', event => {
  console.log('Service worker installed');
});

self.addEventListener('fetch', event => {
  // You can add caching logic here later if needed
});
