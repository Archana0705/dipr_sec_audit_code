// ======================================================
// 🔹 globalUser.js — Used to access decrypted user details globally
// ======================================================

// This assumes decryptKey() is defined globally (same as in your login page)

function getDecryptedUserData() {
    try {
        const secretKey = decryptKey();
        const encryptedData = localStorage.getItem("userAdminName");

        // If not logged in
        if (!encryptedData) {
            console.warn("No user data found in localStorage. Redirecting to login...");
            redirectToLogin();
            return null;
        }

        // Try decrypting
        const bytes = CryptoJS.AES.decrypt(encryptedData, secretKey);
        const decryptedText = bytes.toString(CryptoJS.enc.Utf8);

        if (!decryptedText) {
            console.error("Failed to decrypt user data. Redirecting to login...");
            redirectToLogin();
            return null;
        }

        const userData = JSON.parse(decryptedText);

        // Optional sanity check
        if (!userData || !userData.user_id || !userData.role) {
            console.error("Invalid user data. Redirecting to login...");
            redirectToLogin();
            return null;
        }

        return userData;
    } catch (error) {
        console.error("Error decoding user data:", error);
        redirectToLogin();
        return null;
    }
}

// 🔹 Helper function for redirect
function redirectToLogin() {
    // Adjust login page path as needed
    const loginPath = window.basePath ? `${window.basePath}/admin-login.html` : "/admin-login.html";
    window.location.href = loginPath;
}

// ======================================================
// 🔹 Example Usage (on any page)
// ======================================================
//
// const currentUser = getDecryptedUserData();
// if (currentUser) {
//   console.log("Logged in as:", currentUser.role);
//   console.log("User ID:", currentUser.user_id);
//   console.log("District:", currentUser.district);
// }
//
// You can use it like:
//   const userId = currentUser.user_id;
//   const role = currentUser.role;
//
// ======================================================
