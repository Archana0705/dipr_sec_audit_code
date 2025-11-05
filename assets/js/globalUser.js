function getDecryptedUserData(options = {}) {
    const { redirectOnFail = true } = options; // 👈 add this toggle

    try {
        const secretKey = decryptKey();
        const encryptedData = localStorage.getItem("userAdminName");

        if (!encryptedData) {
            console.warn("No user data found in localStorage.");
            if (redirectOnFail) redirectToLogin();
            return null;
        }

        const bytes = CryptoJS.AES.decrypt(encryptedData, secretKey);
        const decryptedText = bytes.toString(CryptoJS.enc.Utf8);

        if (!decryptedText) {
            console.error("Failed to decrypt user data.");
            if (redirectOnFail) redirectToLogin();
            return null;
        }

        const userData = JSON.parse(decryptedText);
        if (!userData || !userData.user_id || !userData.role) {
            console.error("Invalid user data structure.");
            if (redirectOnFail) redirectToLogin();
            return null;
        }

        return userData;
    } catch (error) {
        console.error("Error decoding user data:", error);
        if (redirectOnFail) redirectToLogin();
        return null;
    }
}

function redirectToLogin() {
    const loginPath = window.basePath
        ? `${window.basePath}/admin-login.html`
        : "/admin-login.html";
    window.location.href = loginPath;
}
