/**
 * admin_actions.js - Logic trang Admin
 */

document.addEventListener('DOMContentLoaded', function () {
    loadEmployeeList();
    loadWarrantyList();
    loadRepairProgress();

    // Tự động kiểm tra bảo hành sắp hết hạn
    fetch('../../backend/api/check_warranty_expiry.php')
        .then(r => r.json())
        .then(r => { if (r.notified > 0) console.log('Tạo ' + r.notified + ' thông báo BH.'); })
        .catch(() => {});

    // Form tạo nhân viên
    const createEmpForm = document.getElementById('form-create-employee');
    if (createEmpForm) {
        createEmpForm.addEventListener('submit', function (e) {
            e.preventDefault();
            fetch('../../backend/api/create_user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    name:     document.getElementById('emp_name').value.trim(),
                    email:    document.getElementById('emp_email').value.trim(),
                    password: document.getElementById('emp_password').value.trim(),
                    role:     document.getElementById('emp_role').value
                })
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) { alert('✅ ' + res.message); $('#createEmployeeModal').modal('hide'); loadEmployeeList(); }
                else alert('❌ ' + res.error);
            })
            .catch(() => alert('Lỗi kết nối!'));
        });
    }

    // Form đổi mật khẩu
    const resetPassForm = document.getElementById('form-reset-password');
    if (resetPassForm) {
        resetPassForm.addEventListener('submit', function (e) {
            e.preventDefault();
            fetch('../../backend/api/reset_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id:           document.getElementById('reset_emp_id').value,
                    new_password: document.getElementById('reset_new_password').value.trim()
                })
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) { alert('✅ Đã cập nhật mật khẩu!'); $('#resetPasswordModal').modal('hide'); }
                else alert('❌ ' + res.error);
            })
            .catch(() => alert('Lỗi kết nối!'));
        });
    }
});

function loadEmployeeList() {
    fetch('../../backend/api/get_users.php')
    .then(r => r.json())
    .then(res => {
        const tbody = document.getElementById('admin-user-list');
        if (!tbody) return;
        tbody.innerHTML = '';
        if (!res.success || !res.data || res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Chưa có dữ liệu...</td></tr>';
            return;
        }
        res.data.forEach(u => {
            const badge = u.role === 'admin' ? '<span class="badge badge-danger">Quản trị viên</span>'
                        : u.role === 'manager' ? '<span class="badge badge-warning">Quản lý</span>'
                        : '<span class="badge badge-info">Kỹ thuật viên</span>';
            tbody.innerHTML += `<tr>
                <td><strong>${u.name}</strong></td>
                <td>${u.email}</td>
                <td class="text-center">${badge}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary" onclick="openResetPasswordModal(${u.id},'${u.name}')"><i class="fa fa-key"></i></button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(${u.id},'${u.name}')"><i class="fa fa-trash"></i></button>
                </td></tr>`;
        });
    }).catch(err => console.error('Lỗi get_users:', err));
}

function loadWarrantyList() {
    fetch('../../backend/api/devices.php')
    .then(r => { if (r.status===401) return null; return r.json(); })
    .then(res => {
        if (!res) return;
        const tbody = document.getElementById('admin-warranty-list');
        if (!tbody) return;
        tbody.innerHTML = '';
        if (!res.success || !res.data || res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Chưa có thiết bị...</td></tr>';
            return;
        }
        res.data.forEach(item => {
            const sc = item.status==='active' ? 'text-status-good' : item.status==='repairing' ? 'text-warning' : 'text-status-expired';
            const st = item.status==='active' ? 'Đang bảo hành' : item.status==='repairing' ? 'Đang sửa' : 'Hết hạn';
            tbody.innerHTML += `<tr>
                <td><strong>#TB-${item.id}</strong></td>
                <td>${item.name}</td>
                <td>${item.customer_name??'—'}</td>
                <td>${item.serial_number??'—'}</td>
                <td class="text-center"><span class="${sc}">${st}</span></td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-info" onclick="viewDeviceDetail(${item.id})">
                        <i class="fa fa-eye"></i> Xem chi tiết
                    </button>
                </td></tr>`;
        });
    }).catch(err => console.error('Lỗi devices:', err));
}

function loadRepairProgress() {
    fetch('../../backend/api/repair_tickets.php')
    .then(r => { if (r.status===401) return null; return r.json(); })
    .then(res => {
        if (!res) return;
        const tbody = document.getElementById('admin-repair-list');
        if (!tbody) return;
        tbody.innerHTML = '';
        if (!res.success || !res.data || res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Chưa có phiếu...</td></tr>';
            return;
        }
        res.data.forEach(item => {
            let sc='badge-secondary', st=item.status;
            if(item.status==='pending')   { sc='badge-secondary'; st='Chờ xử lý'; }
            if(item.status==='repairing') { sc='badge-info';      st='Đang sửa'; }
            if(item.status==='completed') { sc='badge-success';   st='Hoàn tất'; }
            if(item.status==='cancelled') { sc='badge-danger';    st='Đã hủy'; }
            tbody.innerHTML += `<tr>
                <td><strong>#RT-${item.id}</strong></td>
                <td>${item.device_name??'—'}</td>
                <td>${item.staff_name??'<span class="text-muted">Chưa có</span>'}</td>
                <td>${item.description??'—'}</td>
                <td class="text-center"><span class="badge ${sc}">${st}</span></td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary" onclick="openAssignModal(${item.id},'#RT-${item.id}')">
                        <i class="fa fa-user-plus"></i> Bổ nhiệm
                    </button>
                </td></tr>`;
        });
    }).catch(err => console.error('Lỗi repair_tickets:', err));
}

function viewDeviceDetail(id) {
    fetch('../../backend/api/get_device_detail.php?id=' + id)
    .then(r => r.json())
    .then(res => {
        if (!res.success) { alert('Lỗi: ' + res.error); return; }
        const d=res.device, ts=res.tickets, ws=res.extensions;
        const daysLeft = Math.ceil((new Date(d.warranty_end_date)-new Date())/86400000);
        const wBadge = daysLeft<0 ? '<span class="badge badge-danger">Đã hết hạn</span>'
                     : daysLeft<=90 ? '<span class="badge badge-warning">Sắp hết hạn ('+daysLeft+' ngày)</span>'
                     : '<span class="badge badge-success">Còn hạn</span>';

        let tr=''; ts.forEach(t => {
            const stMap={pending:'Chờ xử lý',repairing:'Đang sửa',completed:'Hoàn tất',cancelled:'Đã hủy'};
            tr+=`<tr><td>#RT-${t.id}</td><td>${t.description??'—'}</td><td>${t.staff_name??'Chưa gán'}</td><td><span class="badge badge-info">${stMap[t.status]??t.status}</span></td></tr>`;
        });
        if(!tr) tr='<tr><td colspan="4" class="text-center text-muted">Chưa có</td></tr>';

        let wr=''; ws.forEach(w => {
            wr+=`<tr><td>${new Date(w.created_at).toLocaleDateString('vi-VN')}</td><td><del>${w.old_end_date}</del> → <strong class="text-success">${w.new_end_date}</strong></td><td>${Number(w.cost).toLocaleString('vi-VN')} đ</td><td>${w.user_name}</td></tr>`;
        });
        if(!wr) wr='<tr><td colspan="4" class="text-center text-muted">Chưa có</td></tr>';

        document.getElementById('detail-body').innerHTML = `
            <div class="row mb-3">
                <div class="col-md-6">
                    <p><strong>Tên:</strong> ${d.name}</p>
                    <p><strong>Serial:</strong> ${d.serial_number??'—'}</p>
                    <p><strong>Loại:</strong> ${d.type==='hardware'?'Phần cứng':'Phần mềm'}</p>
                    <p><strong>Khách hàng:</strong> ${d.customer_name??'—'} ${d.customer_phone?'('+d.customer_phone+')':''}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Bắt đầu BH:</strong> ${d.warranty_start_date??'—'}</p>
                    <p><strong>Hết hạn BH:</strong> ${d.warranty_end_date??'—'} ${wBadge}</p>
                    <p><strong>Trạng thái:</strong> ${d.status}</p>
                </div>
            </div>
            <h6 class="font-weight-bold border-bottom pb-1">Lịch sử phiếu sửa chữa</h6>
            <table class="table table-sm table-bordered mb-3">
                <thead class="thead-light"><tr><th>Mã</th><th>Mô tả</th><th>KTV</th><th>Trạng thái</th></tr></thead>
                <tbody>${tr}</tbody>
            </table>
            <h6 class="font-weight-bold border-bottom pb-1">Lịch sử gia hạn bảo hành</h6>
            <table class="table table-sm table-bordered">
                <thead class="thead-light"><tr><th>Ngày</th><th>Thay đổi</th><th>Chi phí</th><th>Người thực hiện</th></tr></thead>
                <tbody>${wr}</tbody>
            </table>`;
        document.getElementById('detail-modal-title').innerText = 'Chi tiết: ' + d.name;
        $('#deviceDetailModal').modal('show');
    }).catch(() => alert('Lỗi kết nối!'));
}

function openAssignModal(ticketId, label) {
    document.getElementById('assign_ticket_id').value = ticketId;
    document.getElementById('assign_ticket_label').innerText = label;
    const sel = document.getElementById('assign_staff_id');
    sel.innerHTML = '<option value="">-- Đang tải... --</option>';
    fetch('../../backend/api/get_users.php').then(r=>r.json()).then(res=>{
        sel.innerHTML = '<option value="">-- Chọn kỹ thuật viên --</option>';
        (res.data||[]).filter(u=>u.role==='staff'||u.role==='manager')
            .forEach(u=>{ sel.innerHTML+=`<option value="${u.id}">${u.name} (${u.role==='manager'?'Quản lý':'KTV'})</option>`; });
    });
    $('#assignStaffModal').modal('show');
}

function submitAssign() {
    const ticketId=document.getElementById('assign_ticket_id').value;
    const staffId=document.getElementById('assign_staff_id').value;
    if (!staffId) { alert('Vui lòng chọn kỹ thuật viên!'); return; }
    fetch('../../backend/api/assign_ticket.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ticket_id:parseInt(ticketId), staff_id:parseInt(staffId)})
    }).then(r=>r.json()).then(res=>{
        if (res.success) { alert('✅ '+res.message); $('#assignStaffModal').modal('hide'); loadRepairProgress(); }
        else alert('❌ '+res.error);
    }).catch(()=>alert('Lỗi kết nối!'));
}

function openResetPasswordModal(id, name) {
    document.getElementById('reset_emp_id').value = id;
    document.getElementById('reset_emp_name').innerText = name;
    document.getElementById('reset_new_password').value = '';
    $('#resetPasswordModal').modal('show');
}

function deleteUser(id, name) {
    if (!confirm('Xóa tài khoản ['+name+']?')) return;
    fetch('../../backend/api/delete_user.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({id})
    }).then(r=>r.json()).then(res=>{
        if (res.success) { alert('✅ Đã xóa ['+name+']'); loadEmployeeList(); }
        else alert('❌ '+res.error);
    }).catch(()=>alert('Lỗi kết nối!'));
}
