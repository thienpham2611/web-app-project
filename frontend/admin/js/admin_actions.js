/**
 * admin_actions.js - Quản lý tập trung toàn bộ logic trang Admin
 */

document.addEventListener('DOMContentLoaded', function() {
    // 1. TỰ ĐỘNG TẢI DỮ LIỆU KHI MỞ TRANG
    loadEmployeeList();   // Quản lý nhân viên
    loadWarrantyList();   // Hệ thống bảo hành
    loadRepairProgress(); // Tiến độ sửa chữa

    // --- XỬ LÝ FORM TẠO NHÂN VIÊN ---
    const createEmpForm = document.getElementById('form-create-employee');
    if (createEmpForm) {
        createEmpForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const data = {
                name: document.getElementById('emp_name').value.trim(),
                email: document.getElementById('emp_email').value.trim(),
                password: document.getElementById('emp_password').value.trim(),
                role: document.getElementById('emp_role').value
            };

            fetch('../../backend/api/create_user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(response => {
                if (response.success) {
                    alert("Thành công: " + response.message);
                    $('#createEmployeeModal').modal('hide');
                    loadEmployeeList(); // Tải lại bảng nhân viên ngay lập tức
                } else {
                    alert("Lỗi: " + response.error);
                }
            })
            .catch(err => alert("Lỗi kết nối API tạo người dùng!"));
        });
    }

    // --- XỬ LÝ FORM ĐỔI MẬT KHẨU ---
    const resetPassForm = document.getElementById('form-reset-password');
    if (resetPassForm) {
        resetPassForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const data = {
                id: document.getElementById('reset_emp_id').value,
                new_password: document.getElementById('reset_new_password').value.trim()
            };

            fetch('../../backend/api/reset_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(response => {
                if (response.success) {
                    alert("Thành công: Đã cập nhật mật khẩu mới!");
                    $('#resetPasswordModal').modal('hide');
                } else {
                    alert("Lỗi: " + response.error);
                }
            })
            .catch(err => alert("Lỗi kết nối API đổi mật khẩu!"));
        });
    }
});

/**
 * HÀM TẢI DANH SÁCH NHÂN VIÊN (QUẢN LÝ NỘI BỘ)
 */
function loadEmployeeList() {
    fetch('../../backend/api/get_users.php')
    .then(res => res.json())
    .then(res => {
        const tbody = document.getElementById('admin-user-list');
        if (!tbody) return;
        tbody.innerHTML = '';
        if (res.success && res.data && res.data.length > 0) {
            res.data.forEach(user => {
                let roleBadge = user.role === 'admin' ? '<span class="badge badge-danger">Quản trị viên</span>' :
                                user.role === 'manager' ? '<span class="badge badge-warning">Quản lý</span>' : 
                                '<span class="badge badge-info">Kỹ thuật viên</span>';
                tbody.innerHTML += `
                    <tr>
                        <td><strong>${user.name}</strong></td>
                        <td>${user.email}</td>
                        <td class="text-center">${roleBadge}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" title="Đổi mật khẩu" onclick="openResetPasswordModal(${user.id}, '${user.name}')"><i class="fa fa-key"></i></button>
                            <button class="btn btn-sm btn-outline-danger" title="Xóa tài khoản" onclick="deleteUser(${user.id}, '${user.name}')"><i class="fa fa-trash"></i></button>
                        </td>
                    </tr>`;
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Chưa có dữ liệu nhân viên...</td></tr>';
        }
    })
    .catch(err => console.error("Lỗi API get_users:", err));
}

/**
 * HÀM TẢI HỆ THỐNG BẢO HÀNH (REPORT 1)
 */
function loadWarrantyList() {
    // Lưu ý: Đường dẫn này phải đúng với cấu trúc thư mục frontend của bạn
    fetch('api/get_all_devices.php')
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById('admin-warranty-list');
        if (!tbody) return;
        tbody.innerHTML = '';
        data.forEach(item => {
            let statusClass = item.status === 'active' ? 'text-status-good' : 'text-status-expired';
            let statusText = item.status === 'active' ? 'Đang bảo hành' : 'Hết hạn';
            tbody.innerHTML += `
                <tr>
                    <td><strong>${item.id}</strong></td>
                    <td>${item.device_name}</td>
                    <td>${item.customer_name}</td>
                    <td class="text-center">${item.warranty_end_date}</td>
                    <td class="text-center"><span class="${statusClass}">${statusText}</span></td>
                    <td class="action-col"><button class="btn-idt-fixed btn-red">Gia hạn</button></td>
                </tr>`;
        });
    })
    .catch(err => console.error("Lỗi API get_all_devices:", err));
}

/**
 * HÀM TẢI TIẾN ĐỘ SỬA CHỮA (REPORT 3)
 */
function loadRepairProgress() {
    fetch('api/get_all_tickets.php')
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById('admin-repair-list');
        if (!tbody) return;
        tbody.innerHTML = '';
        data.forEach(item => {
            tbody.innerHTML += `
                <tr>
                    <td><strong>${item.id}</strong></td>
                    <td>${item.device_name}</td>
                    <td>${item.technician_name}</td>
                    <td class="align-middle">
                        <div class="progress idt-progress-bar">
                            <div class="progress-bar bg-info" style="width: ${item.progress}%;"></div>
                        </div>
                    </td>
                    <td class="action-col"><span class="status-btn btn-info-idt">${item.status}</span></td>
                </tr>`;
        });
    })
    .catch(err => console.error("Lỗi API get_all_tickets:", err));
}

// --- HELPER FUNCTIONS ---

function openResetPasswordModal(id, name) {
    document.getElementById('reset_emp_id').value = id;
    document.getElementById('reset_emp_name').innerText = name;
    document.getElementById('reset_new_password').value = '';
    $('#resetPasswordModal').modal('show');
}

function deleteUser(id, name) {
    if (confirm("CẢNH BÁO: Bạn có chắc chắn muốn xóa vĩnh viễn tài khoản của [" + name + "] không?")) {
        
        // SỬA ĐƯỜNG DẪN TẠI ĐÂY: Dùng đúng 2 dấu ../ để lùi ra khỏi frontend
        fetch('../../backend/api/delete_user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
        .then(res => {
            if (!res.ok) throw new Error('Không tìm thấy file API (404)');
            return res.json();
        })
        .then(response => {
            if (response.success) {
                alert("Thành công: Đã xóa tài khoản [" + name + "]");
                loadEmployeeList(); // Tải lại bảng ngay lập tức
            } else {
                alert("Lỗi: " + response.error);
            }
        })
        .catch(err => {
            console.error("Lỗi Fetch:", err);
            alert("Vẫn lỗi kết nối! Hãy nhấn F12, vào tab Network và xem dòng delete_user.php có màu đỏ không.");
        });
    }
}