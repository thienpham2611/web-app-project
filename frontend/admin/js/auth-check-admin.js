document.addEventListener("DOMContentLoaded", function () {

    fetch("http://localhost/web-app-project/backend/api/check_auth.php", {
        method: "GET",
        credentials: "include" // BẮT BUỘC để gửi session cookie
    })
    .then(res => {
        if (res.status === 401 || res.status === 403) {
            // Chưa đăng nhập hoặc không có quyền
            window.location.href = "../index.html";
            return null;
        }
        return res.json();
    })
    .then(data => {
        if (!data) return;

        // Nếu backend có trả role
        if (data.role !== "admin") {
            // Không phải admin → đá ra
            window.location.href = "../index.html";
        }

        // (Optional) Có thể hiển thị info admin
        console.log("Admin authenticated:", data);
    })
    .catch(err => {
        console.error("Auth check failed", err);
        window.location.href = "../index.html";
    });

});

