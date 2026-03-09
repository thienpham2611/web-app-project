document.addEventListener("DOMContentLoaded", function () {
    const loginForm = document.getElementById("login-form");
    const registerForm = document.getElementById("register-form");
    const btnGoToRegister = document.getElementById("login_register_btn");
    const btnGoToLogin = document.getElementById("register_login_btn");

    if (btnGoToRegister && btnGoToLogin) {
        btnGoToRegister.addEventListener("click", function () {
            loginForm.style.display = "none";
            registerForm.style.display = "block";
        });

        btnGoToLogin.addEventListener("click", function () {
            registerForm.style.display = "none";
            loginForm.style.display = "block";
        });
    }

    // ==========================================
    // 1. XỬ LÝ ĐĂNG KÝ KHÁCH HÀNG
    // ==========================================
    if (registerForm) {
        registerForm.addEventListener("submit", function (e) {
            e.preventDefault();

            const name = document.getElementById("register_username").value.trim();
            const email = document.getElementById("register_email").value.trim();
            const password = document.getElementById("register_password").value.trim();

            // Nhớ điều chỉnh lại đường dẫn API cho đúng với thư mục máy của bạn
            fetch("http://localhost/web-app-project/backend/api/register_customer.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ name, email, password })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert("Đăng ký thành công! Vui lòng đăng nhập.");
                    registerForm.reset(); 
                    btnGoToLogin.click(); // Tự động quay lại form đăng nhập
                } else {
                    alert("Lỗi: " + data.error);
                }
            })
            .catch(err => {
                console.error(err);
                alert("Lỗi kết nối đến máy chủ khi đăng ký.");
            });
        });
    }

    // ==========================================
    // 2. XỬ LÝ ĐĂNG NHẬP KHÁCH HÀNG
    // ==========================================
    if (loginForm) {
        loginForm.addEventListener("submit", function (e) {
            e.preventDefault();

            // Lưu ý: ID input HTML của bạn đang là login_username, nhưng ta lấy giá trị email
            const email = document.getElementById("login_username").value.trim(); 
            const password = document.getElementById("login_password").value.trim();

            fetch("http://localhost/web-app-project/backend/api/login_customer.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                credentials: "include", // Giữ session PHP
                body: JSON.stringify({ email, password })
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert(data.error || "Đăng nhập thất bại");
                    return;
                }

                // Nếu đăng nhập thành công, chuyển hướng về trang khách hàng
                window.location.href = data.redirect_url || "khachhang.php";
            })
            .catch(err => {
                console.error(err);
                alert("Lỗi kết nối đến máy chủ khi đăng nhập.");
            });
        });
    }
});