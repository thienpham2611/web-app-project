document.addEventListener("DOMContentLoaded", function () {
    const loginForm = document.getElementById("login-form");

    // ==========================================
    // XỬ LÝ ĐĂNG NHẬP KHÁCH HÀNG
    // ==========================================
    if (loginForm) {
        loginForm.addEventListener("submit", function (e) {
            e.preventDefault();

            const email    = document.getElementById("login_username").value.trim();
            const password = document.getElementById("login_password").value.trim();

            fetch("../backend/api/login_customer.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                credentials: "include",
                body: JSON.stringify({ email, password })
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert(data.error || "Đăng nhập thất bại");
                    return;
                }
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
        setTimeout(function() {
            const loginNavBtn = document.querySelector('[data-target="#login-modal"]');
            if (loginNavBtn) {
                loginNavBtn.click();
            } else if (window.jQuery) {
                $('#login-modal').modal('show');
            }
            window.history.replaceState(null, null, window.location.pathname);
        }, 500);
    }
});

// ==========================================
// XỬ LÝ ĐĂNG KÝ KHÁCH HÀNG
// [FIX] Gộp thành 1 listener duy nhất (trước có 2 → submit bị gọi 2 lần)
// ==========================================
const registerForm = document.getElementById("register-form");
if (registerForm) {
    registerForm.addEventListener("submit", function (e) {
        e.preventDefault();

        const name     = document.getElementById("register_username").value.trim();
        const email    = document.getElementById("register_email").value.trim();
        const phone    = document.getElementById("register_phone") ? document.getElementById("register_phone").value.trim() : '';
        const password = document.getElementById("register_password").value.trim();
        const errorBox = document.getElementById("register-error");

        if (errorBox) errorBox.classList.add("d-none");

        fetch("../backend/api/register_customer.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            credentials: "include",
            body: JSON.stringify({ name, email, phone, password })
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                if (errorBox) {
                    errorBox.textContent = data.error || "Đăng ký thất bại do lỗi không xác định!";
                    errorBox.classList.remove("d-none");
                } else {
                    alert("Lỗi: " + (data.error || "Đăng ký thất bại"));
                }
            } else {
                alert("Đăng ký thành công! Bạn có thể đăng nhập ngay bây giờ.");
                registerForm.reset();
                const lf = document.getElementById("login-form");
                if (lf) { registerForm.style.display = "none"; lf.style.display = "block"; }
            }
        })
        .catch(err => {
            console.error("Lỗi Fetch:", err);
            if (errorBox) {
                errorBox.textContent = "Không thể kết nối đến máy chủ. Vui lòng kiểm tra lại!";
                errorBox.classList.remove("d-none");
            }
        });
    });
}

// Hiệu ứng chuyển đổi giữa 2 form
const btnGoRegister = document.getElementById("login_register_btn");
const btnGoLogin    = document.getElementById("register_login_btn");

if (btnGoRegister) {
    btnGoRegister.addEventListener("click", function() {
        document.getElementById("login-form").style.display    = "none";
        document.getElementById("register-form").style.display = "block";
    });
}
if (btnGoLogin) {
    btnGoLogin.addEventListener("click", function() {
        document.getElementById("register-form").style.display = "none";
        document.getElementById("login-form").style.display    = "block";
    });
}

// ==========================================
// CẬP NHẬT HỒ SƠ KHÁCH HÀNG (chạy ở khachhang.php)
// ==========================================
const updateProfileForm = document.getElementById('form-update-profile');
if (updateProfileForm) {
    updateProfileForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const data = {
            name:    document.getElementById('prof_name').value.trim(),
            phone:   document.getElementById('prof_phone').value.trim(),
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
            if (data.success) { alert(data.message); location.reload(); }
            else { alert(data.error || "Có lỗi xảy ra"); }
        })
        .catch(err => { console.error(err); alert('Lỗi máy chủ!'); });
    });
}

// ==========================================
// HÀM BẬT MODAL YÊU CẦU SỬA CHỮA (TỪ KHACHHANG.PHP)
// ==========================================
function openRepairModal(deviceId, deviceName, isExpired) {
    $('#modal_device_id').val(deviceId);
    $('#modal_device_name').val(deviceName);
    $('#modal_description').val('');

    if (isExpired) {
        $('#repair_expired_warning').show();
    } else {
        $('#repair_expired_warning').hide();
    }

    $('#createRepairModal').modal('show');
}

// Gửi yêu cầu sửa chữa
$(document).ready(function() {
    $('#btnGuiYeuCau').on('click', function() {
        var device_id   = $('#modal_device_id').val();
        var description = $('#modal_description').val().trim();

        if (!description) { alert("Vui lòng nhập mô tả lỗi!"); return; }

        var formData = new FormData();
        formData.append('device_id',   device_id);
        formData.append('description', description);

        $.ajax({
            url: '../backend/api/create_repair_ticket.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            xhrFields: { withCredentials: true },
            success: function(response) {
                try {
                    var res = (typeof response === 'string') ? JSON.parse(response) : response;
                    if (res.success) {
                        alert(res.message + "\n\nMã phiếu: #TICK-" + res.ticket_id);
                        $('#createRepairModal').modal('hide');
                        location.reload();
                    } else { alert("Lỗi: " + (res.error || "Không xác định")); }
                } catch(e) { alert("Lỗi xử lý dữ liệu từ máy chủ."); }
            },
            error: function() { alert("Không thể kết nối đến máy chủ."); }
        });
    });
});

// ==========================================
// HÀM BẬT MODAL GIA HẠN BẢO HÀNH (TỪ KHACHHANG.PHP)
// ==========================================
function openWarrantyModal(deviceId, deviceName, isExpired) {
    // Nếu chưa hết hạn (isExpired = false) => Chặn
    if (!isExpired) {
        alert('Thiết bị đang trong thời gian bảo hành, không thể thực hiện hành động này!');
        return false;
    }
    document.getElementById('warranty_device_id').value = deviceId;
    document.getElementById('warranty_device_name').value = deviceName;
    document.getElementById('warranty_note').value = '';
    $('#warrantyRequestModal').modal('show');
}

// ==========================================
// ĐĂNG XUẤT KHÁCH HÀNG
// ==========================================
function logoutCustomer() {
    fetch('../backend/api/logout_customer.php', {
        method: 'GET',
        credentials: 'include'
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) { window.location.href = 'index.php'; }
        else { alert('Lỗi đăng xuất: ' + (result.message || 'Không xác định')); }
    })
    .catch(() => { window.location.href = 'index.php'; });
}

function loadCustomerNotifications() {
    fetch('../backend/api/notifications_customer.php', { method: 'GET', credentials: 'include' })
    .then(r => r.json())
    .then(result => {
        var list = document.getElementById('notif-list');
        var badge = document.getElementById('notif-badge');
        if (!list || !badge) return;
        if (!result.success || result.data.length === 0) {
            list.innerHTML = '<div class="text-center text-muted py-3 small">Không có thông báo</div>';
            badge.style.display = 'none';
            return;
        }

        // Lấy timestamp lần cuối mở chuông
        var lastSeen = parseInt(localStorage.getItem('customer_notif_seen') || '0');
        var unreadCount = result.data.filter(n => {
            if (!n.time) return false;
            return new Date(n.time).getTime() > lastSeen;
        }).length;

        if (unreadCount > 0) {
            badge.textContent = unreadCount > 9 ? '9+' : unreadCount;
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }

        list.innerHTML = result.data.map(n => {
            var isUnread = n.time && new Date(n.time).getTime() > lastSeen;
            var bg = isUnread ? 'background:#fff8e1;' : '';
            let extraHtml = '';
            if (n.type === 'repair_log' && n.progress !== undefined) {
                const barColor = n.progress >= 90 ? '#28a745' : n.progress < 30 ? '#dc3545' : '#17a2b8';
                extraHtml = `<div style="margin-top:4px;">
                    <div style="background:#e9ecef;border-radius:4px;height:6px;overflow:hidden;">
                        <div style="width:${n.progress}%;background:${barColor};height:100%;"></div>
                    </div>
                    <small style="color:#666;">${n.progress}%</small>
                </div>`;
            }
            return `<a class="dropdown-item py-2 border-bottom" href="${n.link}" style="white-space:normal;font-size:13px;${bg}">${n.message}${extraHtml}</a>`;
        }).join('');
    });
}
document.addEventListener('DOMContentLoaded', function() {
    loadCustomerNotifications();
    var bell = document.getElementById('notification-bell');
    if (bell) {
        bell.addEventListener('show.bs.dropdown', function() {
            localStorage.setItem('customer_notif_seen', Date.now().toString());
            loadCustomerNotifications();
        });
    }
});