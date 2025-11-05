const API_BASE_URL = "https://tngis.tnega.org/lcap_api/dipr-lcap-api/v1/";
$(document).ready(function () {

  $.ajaxSetup({
    headers: {
      "X-App-Key": "dipr",
      "X-App-Name": "dipr"
    }
  });

  function getBasePath() {
    const path = window.location.pathname;
    const baseMatch = path.match(/^(\/dipr)?/);
    return baseMatch ? baseMatch[0] : "";
  }

  function navigateTo(page) {
    const basePath = getBasePath();
    window.location.href = `${basePath}/${page}`;
  }

  function signOut() {
    localStorage.removeItem("role");
    localStorage.removeItem("userAdminName");
    navigateTo("index.html");
  }

});
function decryptKey() {
  let encoded = "QVtRUVV2BAYFQVpTU1d2BAY=";
  let key = "tnega@123";
  let text = atob(encoded); // decode from base64
  let result = '';
  for (let i = 0; i < text.length; i++) {
    // XOR back with the key to get original
    result += String.fromCharCode(text.charCodeAt(i) ^ key.charCodeAt(i % key.length));
  }
  return result;
}