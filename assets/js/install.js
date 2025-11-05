if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/service-worker.js')
    .then(() => console.log('Service Worker registered'));
}

let deferredPrompt;

window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  deferredPrompt = e;
  $('.a-pwaInstall').show(); // show the button only when eligible
});

$('.a-pwaInstall a').on('click', async function() {
  if (!deferredPrompt) return;
  deferredPrompt.prompt();
  const { outcome } = await deferredPrompt.userChoice;
  if (outcome === 'accepted') {
    console.log('App installed');
    $('.a-pwaInstall').hide();
  }
  deferredPrompt = null;
});

window.addEventListener('appinstalled', () => {
  console.log('App successfully installed');
  $('.a-pwaInstall').hide();
});
