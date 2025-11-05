$(document).ready(function () {
  const secretKey = "55464654654646654";
const encryptedData = localStorage.getItem("role");
let role = null;

if (encryptedData) {
    try {
        const decryptedData = JSON.parse(
            CryptoJS.AES.decrypt(encryptedData, secretKey).toString(CryptoJS.enc.Utf8)
        );
        console.log(decryptedData);
          
        role = decryptedData || null;
    } catch (err) {
        console.error("Error decrypting user data:", err);
    }
}
})