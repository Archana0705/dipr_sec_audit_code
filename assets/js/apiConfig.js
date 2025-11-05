const environment = "prod";

const baseUrls = {
    // dev: "http://10.236.210.57/tnapex_api",
    dev: "https://tngis.tnega.org/lcap_api/dipr-lcap-api/v1",
    prod: "https://tngis.tnega.org/lcap_api/dipr-lcap-api/v1",
};

window.BASE_API_URL = baseUrls[environment];
window.BASE_UPLOAD_URL = window.BASE_API_URL.replace(/\/v1$/, '') + "/uploads/";