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
    // XỬ LÝ ĐĂNG KÝ KHÁCH HÀNG
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
    // XỬ LÝ ĐĂNG NHẬP KHÁCH HÀNG
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

    // ==========================================
    // TỰ ĐỘNG MỞ MODAL ĐĂNG NHẬP TỪ TRANG KHÁC TỚI
    // ==========================================
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('show_login') === 'true') {
        // Thêm khoảng trễ 500ms để đợi trình duyệt load xong giao diện HTML/CSS
        setTimeout(function() {
            // Tìm nút Đăng nhập trên thanh Navbar và giả lập cú click chuột
            const loginNavBtn = document.querySelector('[data-target="#login-modal"]');
            if (loginNavBtn) {
                loginNavBtn.click();
            } else if (window.jQuery) {
                // Chữa cháy bằng jQuery nếu không tìm thấy nút
                $('#login-modal').modal('show');
            }
            
            // Xóa chữ "?show_login=true" trên thanh địa chỉ web cho gọn gàng
            window.history.replaceState(null, null, window.location.pathname);
            
        }, 500); 
    }
});

    // CẬP NHẬT HỒ SƠ KHÁCH HÀNG (chỉ chạy ở khachhang.php)

    const updateProfileForm = document.getElementById('form-update-profile');
    if (updateProfileForm) {
        updateProfileForm.addEventListener('submit', function(e) {
            e.preventDefault(); 

            const data = {
                name: document.getElementById('prof_name').value.trim(),
                phone: document.getElementById('prof_phone').value.trim(),
                address: document.getElementById('prof_address').value.trim()
            };

            fetch('../backend/api/update_profile_customer.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload(); 
                } else {
                    alert(data.error || "Có lỗi xảy ra");
                }
            })
            .catch(err => {
                console.error(err);
                alert('Lỗi máy chủ!');
            });
        });
    }


// Form đăng kí cho khách hàng

const registerForm = document.getElementById("register-form");
if (registerForm) {
    registerForm.addEventListener("submit", function (e) {
        e.preventDefault(); // Ngăn chặn load lại trang

        const name = document.getElementById("register_username").value.trim();
        const email = document.getElementById("register_email").value.trim();
        const phone = document.getElementById("register_phone").value.trim();
        const password = document.getElementById("register_password").value.trim();
        const errorBox = document.getElementById("register-error");

        // Ẩn lỗi cũ đi trước khi gửi request mới
        errorBox.classList.add("d-none");

        fetch("../backend/api/register_customer.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ name, email, phone, password })
        })
        .then(res => res.json()) // Ép hàm fetch đọc file JSON dù API có trả về lỗi 400
        .then(data => {
            if (!data.success) {
                // HIỂN THỊ CHÍNH XÁC CÂU BÁO LỖI TỪ PHP LÊN UI
                errorBox.textContent = data.error || "Đăng ký thất bại do lỗi không xác định!";
                errorBox.classList.remove("d-none");
            } else {
                // Thành công
                alert("Đăng ký thành công! Bạn có thể đăng nhập ngay bây giờ.");
                registerForm.reset();
                
                // Tự động chuyển qua form đăng nhập
                document.getElementById("register-form").style.display = "none";
                document.getElementById("login-form").style.display = "block";
            }
        })
        .catch(err => {
            console.error("Lỗi Fetch:", err);
            errorBox.textContent = "Không thể kết nối đến máy chủ. Vui lòng kiểm tra lại đường dẫn API!";
            errorBox.classList.remove("d-none");
        });
    });
}

// Hiệu ứng chuyển đổi giữa 2 form (cho đẹp)
const btnGoRegister = document.getElementById("login_register_btn");
const btnGoLogin = document.getElementById("register_login_btn");

if(btnGoRegister) {
    btnGoRegister.addEventListener("click", function() {
        document.getElementById("login-form").style.display = "none";
        document.getElementById("register-form").style.display = "block";
    });
}
if(btnGoLogin) {
    btnGoLogin.addEventListener("click", function() {
        document.getElementById("register-form").style.display = "none";
        document.getElementById("login-form").style.display = "block";
    });
}

// ==========================================
// HÀM BẬT MODAL YÊU CẦU SỬA CHỮA (TỪ KHACHHANG.PHP)
// ==========================================
function openRepairModal(deviceId, deviceName) {
    $('#modal_device_id').val(deviceId);
    $('#modal_device_name').val(deviceName);
    $('#modal_description').val(''); // Dọn sạch khung nhập lỗi cũ
    
    // Bật Modal lên
    $('#createRepairModal').modal('show');
}

// Modal của nút gửi yêu cầu sửa chữa
$(document).ready(function() {
    $('#btnGuiYeuCau').on('click', function() {
        var device_id = $('#modal_device_id').val();
        var description = $('#modal_description').val().trim();

        if (!description) {
            alert("Vui lòng nhập mô tả lỗi!");
            return;
        }

        var formData = new FormData();
        formData.append('device_id', device_id);
        formData.append('description', description);

        $.ajax({
            url: '../backend/api/create_repair_ticket.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                try {
                    var res = (typeof response === 'string') ? JSON.parse(response) : response;
                    if (res.success) {
                        alert(res.message + "\n\nMã phiếu: #TICK-" + res.ticket_id);
                        $('#createRepairModal').modal('hide');
                        location.reload(); // Tự động load lại trang
                    } else {
                        alert("Lỗi: " + (res.error || "Không xác định"));
                    }
                } catch(e) {
                    alert("Lỗi xử lý dữ liệu từ máy chủ.");
                }
            },
            error: function() {
                alert("Không thể kết nối đến máy chủ.");
            }
        });
    });
});