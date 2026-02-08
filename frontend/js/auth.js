document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("login-form");
    if (!form) return;

    form.addEventListener("submit", function (e) {
        e.preventDefault();

        const email = document.getElementById("login_email").value.trim();
        const password = document.getElementById("login_password").value.trim();

        fetch("http://localhost/web-app-project/backend/api/login.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            credentials: "include", // 🔴 BẮT BUỘC để giữ session PHP
            body: JSON.stringify({ email, password })
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                const err = document.getElementById("login-error");
                err.textContent = data.error || "Đăng nhập thất bại";
                err.classList.remove("d-none");
                return;
            }

            const role = data.user.role;

            // 🔐 PHÂN QUYỀN REDIRECT
            if (role === "admin") {
                window.location.href = "admin/admin.html";
            } 
            else if (role === "manager") {
                window.location.href = "admin/index.html";
            } 
            else {
                window.location.href = "admin/nhanvien.html";
            }
        })
        .catch(err => {
            console.error(err);
            alert("Lỗi máy chủ");
        });
    });
});
