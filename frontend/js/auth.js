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
    if (isExpired) {
        alert('Thiết bị đã hết thời gian bảo hành. Chức năng này không thực hiện được!');
        return false;
    }
    // Nếu chưa hết hạn thì mở Modal bình thường
    $('#modal_device_id').val(deviceId);
    $('#modal_device_name').val(deviceName);
    $('#modal_description').val('');
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
            list.innerHTML = '<div class="text-center text-muted py-4"><i class="fa fa-bell-slash fa-2x mb-2 d-block"></i><small>Không có thông báo nào</small></div>';
            badge.style.display = 'none';
            return;
        }

        badge.textContent = result.count > 9 ? '9+' : result.count;
        badge.style.display = 'inline-block';

        var typeConfig = {
            'repair':  { bg: '#e8f5e9', border: '#4caf50', icon: 'fa-wrench',       color: '#2e7d32' },
            'warranty':{ bg: '#fff8e1', border: '#ff9800', icon: 'fa-shield',        color: '#e65100' },
            'expired': { bg: '#ffebee', border: '#f44336', icon: 'fa-exclamation-circle', color: '#c62828' }
        };

        list.innerHTML = result.data.map(n => {
            var cfg = typeConfig[n.type] || { bg: '#f5f5f5', border: '#9e9e9e', icon: 'fa-bell', color: '#555' };
            var timeHtml = n.time ? '<div style="font-size:11px;color:#999;margin-top:3px;"><i class="fa fa-clock-o"></i> ' + formatNotifTime(n.time) + '</div>' : '';
            return '<a class="dropdown-item" href="' + n.link + '" style="white-space:normal;padding:10px 14px;border-left:3px solid ' + cfg.border + ';background:' + cfg.bg + ';margin-bottom:2px;display:block;">'
                + '<div style="display:flex;align-items:flex-start;gap:8px;">'
                +   '<i class="fa ' + cfg.icon + ' mt-1" style="color:' + cfg.color + ';min-width:16px;font-size:14px;"></i>'
                +   '<div style="flex:1;font-size:12.5px;line-height:1.4;color:#333;">' + n.message + timeHtml + '</div>'
                + '</div>'
                + '</a>';
        }).join('');
    })
    .catch(() => {
        var list = document.getElementById('notif-list');
        if (list) list.innerHTML = '<div class="text-center text-muted py-3 small"><i class="fa fa-wifi"></i> Không thể tải thông báo</div>';
    });
}
document.addEventListener('DOMContentLoaded', function() {
    loadCustomerNotifications();
    var bell = document.getElementById('notification-bell');
    if (bell) { bell.addEventListener('show.bs.dropdown', loadCustomerNotifications); }
});