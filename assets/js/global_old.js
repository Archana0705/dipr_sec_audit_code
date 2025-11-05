const API_BASE_URL = "https://tngis.tnega.org/lcap_api/dipr-lcap-api/v1/";
$(document).ready(function () {
  //    $(document).on("contextmenu", function (e) {
  //     e.preventDefault();
  //      alert("Right click is disabled!");
  //      return false;
  //    });
  // $(document).on("keydown", function (e) {
  //         console.log("Key pressed:", e.key, "Code:", e.keyCode);

  //         // Ctrl+U
  //         if (e.ctrlKey && (e.key === "u" || e.key === "U" || e.keyCode === 85)) {
  //           e.preventDefault();
  //           alert("View Source is disabled!");
  //           return false;
  //         }

  //         // Ctrl+Shift+I
  //         if (e.ctrlKey && e.shiftKey && (e.key === "I" || e.key === "i" || e.keyCode === 73)) {
  //           e.preventDefault();
  //           alert("Inspect is disabled!");
  //           return false;
  //         }

  //         // F12
  //         if (e.keyCode === 123) {
  //           e.preventDefault();
  //           alert("Developer Tools disabled!");
  //           return false;
  //         }
  //       });
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
