

// // assets/basePath.js
// const basePath = (function () {
//     const pathName = window.location.pathname;
//     console.log("DEBUG - window.location.pathname:", pathName);

//     // Try to extract the first-level folder from the pathname
//     // Example:
//     //   /dipr/admin-login.html  →  /dipr
//     //   /admin-login.html       →  ""
//     const match = pathName.match(/^\/([^/]+)/);

//     if (match && match[1] !== "admin-login.html" && match[1] !== "index.html") {
//         return "/" + match[1];
//     }

//     // If no folder, we are at root (local dev)
//     return "";
// })();

// console.log("DEBUG - Resolved basePath:", basePath);
// window.basePath = basePath;


// assets/js/basepath.js
(function () {
    const path = window.location.pathname || "/";
    // First segment after the leading slash
    // e.g. "/dipr/admin.html" -> "dipr"
    //      "/admin-functions.html" -> "admin-functions.html"
    const firstSegment = path.split("/")[1] || "";

    // Consider it a folder ONLY if it doesn't look like a file (no dot)
    const isFolder = firstSegment && !firstSegment.includes(".");

    const basePath = isFolder ? ("/" + firstSegment) : "";

    console.log("DEBUG basepath.js → pathname:", path, "| firstSegment:", firstSegment, "| isFolder:", isFolder, "| basePath:", basePath);

    window.basePath = basePath; // "" on localhost root, "/dipr" (or similar) on staging
})();
