$(document).ready(function () {
    // //debugger
    $('#navbar-container').load("globalNavbar.html", function () {
        setTimeout(() => {
            const secretKey = "55464654654646654";
const encryptedData = localStorage.getItem("role");
let role = null;

if (encryptedData) {
    try {
        const decryptedData = JSON.parse(
            CryptoJS.AES.decrypt(encryptedData, secretKey).toString(CryptoJS.enc.Utf8)
        );
        console.log(decryptedData);
          
        role = decryptedData || null; // assuming loginResult has user_id
    } catch (err) {
        console.error("Error decrypting user data:", err);
    }
}
            const userAdminName = localStorage.getItem("userAdminName");

            if (userAdminName) {
                $('.admin-name-change').text(role);
                $('.js-tabLike .admin-menu-show').css('display', 'block');
            }

            const interval = setInterval(() => {
                if ($('#t_MenuNav_7').length) {
                    $('#t_MenuNav_7').css('display', 'flex');
                }
                if ($('#t_MenuNav_8').length) {
                    $('#t_MenuNav_8').css('display', 'flex');
                }

                if ($('#t_MenuNav_7').length && $('#t_MenuNav_8').length) {
                    clearInterval(interval);
                }
            }, 100);

            $(document).on("click", '.a-MenuBar-label[href="#"]', function (e) {
                e.preventDefault();

                const $parentLi = $(this).closest("li");
                const $submenu = $parentLi.find("ul").first();

                // Toggle visibility
                if ($submenu.is(":visible")) {
                    $submenu.slideUp();
                } else {
                    $submenu.slideDown();
                }
            });

            const appendSignout = `
        <div class="t-NavigationBar-menu showlink">
          <ul>
            <li><a href="${window.basePath}/index.html" class="signOutBtn">Sign Out</a></li>
          </ul>
        </div>
      `;

            $('.t-NavigationBar-item.has-username.shownav').append(appendSignout);

            $(".signOutBtn").on("click", function (event) {
    event.preventDefault();

    const secretKey = "55464654654646654";
    const encryptedData = localStorage.getItem("userAdminName");
    let userId = null;

    if (encryptedData) {
        try {
            const decryptedData = JSON.parse(
                CryptoJS.AES.decrypt(encryptedData, secretKey).toString(CryptoJS.enc.Utf8)
            );
            userId = decryptedData?.user_id || null; // assuming loginResult has user_id
        } catch (err) {
            console.error("Error decrypting user data:", err);
        }
    }
 

    // Prepare encrypted logout payload
    const payload = {
        action: "function_call",
        function_name: "user_logout",
        params: { user_id: userId }
    };

    
    $.ajax({
        url: `${BASE_API_URL}/commonfunction`,
        type: "POST",
        headers: {
            "X-App-Key": "dipr",
            "X-App-Name": "dipr"
        },
        data: { 
            data: encryptData(payload) // <-- important!
        },
        dataType: "json",
        success: function (response) {debugger
            if (response.success) {
                localStorage.removeItem("role");
                localStorage.removeItem("userAdminName");
                showSuccessToast(response.message || "Logged out successfully!");
                window.location.href = "index.html";
            } else {
                showErrorToast(response.message || "Logout failed");
            }
        },
        error: function (xhr, status, error) {
            console.error("Logout request failed:", error);
            showErrorToast("Logout request failed. Please try again.");
        }
    });
  });
        }, 100);
    });
});
