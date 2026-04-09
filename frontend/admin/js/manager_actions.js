
// ==========================================
// THÔNG BÁO NHÂN VIÊN / QUẢN LÝ
// ==========================================
function loadStaffNotifications() {
    fetch('../../backend/api/notifications_staff.php', { credentials: 'include' })
    .then(r => r.json())
    .then(res => {
        const list  = document.getElementById('staff-notif-list');
        const badge = document.getElementById('staff-notif-badge');
        if (!list || !badge) return;

        if (!res.success || res.data.length === 0) {
            list.innerHTML = '<div class="text-center text-muted py-3 small">Không có thông báo nào</div>';
            badge.style.display = 'none';
            return;
        }

        const unread = res.data.filter(n => !parseInt(n.is_read)).length;
        badge.textContent = unread > 9 ? '9+' : unread;
        badge.style.display = unread > 0 ? 'inline-block' : 'none';

        list.innerHTML = res.data.map(n => {
            const bg   = parseInt(n.is_read) ? '' : 'background:#fff8e1;';
            const d    = new Date(n.created_at);
            const time = d.toLocaleString('vi-VN', {day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'});
            return `<div class="dropdown-item py-2 border-bottom" style="white-space:normal;font-size:13px;${bg}cursor:pointer;"
                        onclick="markStaffNotifRead(${n.id}, this)">
                        <div>${n.message}</div>
                        <small class="text-muted">${time}</small>
                    </div>`;
        }).join('');
    })
    .catch(() => {
        const list = document.getElementById('staff-notif-list');
        if (list) list.innerHTML = '<div class="text-center text-danger py-2 small">Lỗi tải thông báo</div>';
    });
}

function markStaffNotifRead(id, el) {
    fetch('../../backend/api/notifications_staff.php', {
        method: 'PUT', headers: {'Content-Type':'application/json'},
        credentials: 'include',
        body: JSON.stringify({ id })
    }).then(() => {
        if (el) el.style.background = '';
        loadStaffNotifications();
    });
}

function markAllStaffNotifRead() {
    fetch('../../backend/api/notifications_staff.php', {
        method: 'PUT', headers: {'Content-Type':'application/json'},
        credentials: 'include',
        body: JSON.stringify({ mark_all: true })
    }).then(() => loadStaffNotifications());
}

document.addEventListener('DOMContentLoaded', function() {
    loadStaffNotifications();
    const bell = document.getElementById('staff-notif-bell');
    if (bell) {
        bell.addEventListener('show.bs.dropdown', function() {
            fetch('../../backend/api/notifications_staff.php', {
                method: 'PUT', headers: {'Content-Type':'application/json'},
                credentials: 'include',
                body: JSON.stringify({ mark_all: true })
            }).then(() => loadStaffNotifications());
        });
    }

    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn && !logoutBtn.dataset.logoutBound) {
        logoutBtn.dataset.logoutBound = '1';
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            fetch('../../backend/api/logout.php', { credentials: 'include' })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        window.location.href = '../../frontend/admin/index.php';
                    } else {
                        alert('Logout thất bại!');
                    }
                })
                .catch(err => console.error(err));
        });
    }
});


$(document).ready(function() {

    // Load danh sách khi mở modal
    $('#modalBaoGia, #modalTaoDonHang').on('show.bs.modal', function() {
        loadRepairTickets();
        loadCustomersForOrder();
    });

    function loadRepairTickets() {
        $.get('../../backend/api/get_repair_tickets.php', function(data) {
            let html = '<option value="">-- Chọn phiếu sửa chữa --</option>';
            data.forEach(t => {
                html += `<option value="${t.id}">Phiếu #${t.id} - ${t.description.substring(0,30)}...</option>`;
            });
            $('#repair_ticket_id, #repair_ticket_id_order').html(html);
        });
    }

    function loadCustomersForOrder() {
        $.get('../../backend/api/get_customers.php', function(data) {
            let html = '<option value="">-- Chọn khách hàng --</option>';
            data.forEach(c => {
                html += `<option value="${c.id}">${c.name} (${c.phone})</option>`;
            });
            $('#customer_id_order').html(html);
        });
    }
    $('#repair_ticket_id').on('change', function() {
    const ticketId = $(this).val();

    if (!ticketId) {
        $('#customer_name_display').val('');
        $('#device_name_display').val('');
        return;
    }

    $.get('../../backend/api/get_repair_ticket_detail.php?id=' + ticketId, function(res) {
        if (res.success) {
            $('#customer_name_display').val(res.data.customer_name);
            $('#device_name_display').val(res.data.device_name);
        } else {
            alert('Lỗi: ' + res.message);
        }
    }, 'json');
});
    // Lưu báo giá
    $('#btnLuuBaoGia').click(function() {
    fetch('../../backend/api/save_quote.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            repair_ticket_id: $('#repair_ticket_id').val(),
            quote_amount: $('#quote_amount').val(),
            note: $('#note_quote').val()
        })
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            alert(res.message);
            $('#modalBaoGia').modal('hide');
            location.reload();
        } else {
            alert(res.message);
        }
    });
});
    // Tạo đơn hàng
    $('#btnTaoDonHang').click(function() {
        $.post('../../backend/api/save_order.php', {
            repair_ticket_id: $('#repair_ticket_id_order').val(),
            customer_id: $('#customer_id_order').val(),
            total_amount: $('#total_amount').val()
        }, function(res) {
            if (res.success) {
                alert(res.message);
                $('#modalTaoDonHang').modal('hide');
                location.reload();
            } else alert(res.message || 'Lỗi');
        }, 'json');
    });

    // In hóa đơn (demo – sẽ hoàn thiện thêm sau)
    window.xuatHoaDon = function() {
        alert('✅ Đang in hóa đơn... (Manager đã xác nhận thanh toán)\n\nHóa đơn mẫu sẽ mở trong tab mới.');
        // Sau này sẽ gọi API in PDF
        window.open('../../backend/api/print_invoice.php', '_blank');
    };
});

function viewDeviceDetail(id) {
    const modal = $('#deviceDetailModal');
    document.getElementById('detail-body').innerHTML = '<div class="text-center py-4"><i class="fa fa-spinner fa-spin fa-2x"></i></div>';
    modal.modal('show');

    fetch('../../backend/api/get_device_detail.php?id=' + id, { credentials: 'include' })
    .then(r => r.json())
    .then(res => {
        if (!res.success) { alert('Lỗi: ' + res.error); return; }
        const d = res.device, ts = res.tickets, ws = res.extensions;
        const daysLeft = Math.ceil((new Date(d.warranty_end_date) - new Date()) / 86400000);
        const wBadge = daysLeft < 0
            ? '<span class="badge badge-danger">Đã hết hạn</span>'
            : daysLeft <= 90
                ? '<span class="badge badge-warning">Sắp hết hạn (' + daysLeft + ' ngày)</span>'
                : '<span class="badge badge-success">Còn hạn</span>';

        let tr = '';
        ts.forEach(t => {
            const stMap = { pending: 'Chờ xử lý', repairing: 'Đang sửa', completed: 'Hoàn tất', cancelled: 'Đã hủy' };
            tr += `<tr>
                <td>#TICK-${t.id}</td>
                <td>${t.description ?? '—'}</td>
                <td>${t.staff_name ?? 'Chưa gán'}</td>
                <td><span class="badge badge-info">${stMap[t.status] ?? t.status}</span></td>
            </tr>`;
        });
        if (!tr) tr = '<tr><td colspan="4" class="text-center text-muted">Chưa có</td></tr>';

        let wr = '';
        ws.forEach(w => {
            wr += `<tr>
                <td>${new Date(w.created_at).toLocaleDateString('vi-VN')}</td>
                <td><del>${w.old_end_date}</del> → <strong class="text-success">${w.new_end_date}</strong></td>
                <td>${Number(w.cost).toLocaleString('vi-VN')} đ</td>
                <td>${w.user_name}</td>
            </tr>`;
        });
        if (!wr) wr = '<tr><td colspan="4" class="text-center text-muted">Chưa có</td></tr>';

        document.getElementById('detail-body').innerHTML = `
            <div class="row mb-3">
                <div class="col-md-6">
                    <p><strong>Tên:</strong> ${d.name}</p>
                    <p><strong>Serial:</strong> ${d.serial_number ?? '—'}</p>
                    <p><strong>Loại:</strong> ${d.type === 'hardware' ? 'Phần cứng' : 'Phần mềm'}</p>
                    <p><strong>Khách hàng:</strong> ${d.customer_name ?? '—'} ${d.customer_phone ? '(' + d.customer_phone + ')' : ''}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Bắt đầu BH:</strong> ${d.warranty_start_date ?? '—'}</p>
                    <p><strong>Hết hạn BH:</strong> ${d.warranty_end_date ?? '—'} ${wBadge}</p>
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
    }).catch(() => alert('Lỗi kết nối server!'));
}

function assignTicket(ticketId) {
    const staffId = document.getElementById('staff_assign_' + ticketId).value;
    const dueDate = document.getElementById('due_date_' + ticketId)?.value || '';
    if (!staffId) { alert('Vui lòng chọn kỹ thuật viên!'); return; }

    const btn = event.currentTarget;
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';

    fetch('../../backend/api/assign_ticket.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ ticket_id: ticketId, staff_id: staffId, due_date: dueDate })
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            alert('Phân công thành công!');
            location.reload();
        } else {
            alert('Lỗi: ' + (result.error || 'Không xác định'));
        }
    })
    .catch(() => alert('Lỗi kết nối server!'))
    .finally(() => { btn.disabled = false; btn.innerHTML = originalHTML; });
}

$(document).ready(function() {
    // Mở modal → load danh sách khách hàng
    $('#modalDevice').on('show.bs.modal', function(e) {
        loadCustomers();
        const button = $(e.relatedTarget);
        if (button.data('id')) {
            // Chế độ Edit
            loadDeviceData(button.data('id'));
        } else {
            // Chế độ Add new
            $('#modalTitle').text('Thêm mới thiết bị');
            $('#formDevice')[0].reset();
            $('#device_id').val('');
        }
    });

    function loadCustomers() {
        $.get('../../backend/api/get_customers.php', function(data) {
            let html = '<option value="">-- Chọn khách hàng --</option>';
            data.forEach(c => {
                html += `<option value="${c.id}">${c.name} (${c.phone || 'Chưa có SĐT'})</option>`;
            });
            $('#customer_id').html(html);
        });
    }

    function loadDeviceData(id) {
        $.get('../../backend/api/get_device.php?id=' + id, function(device) {
            $('#modalTitle').text('Chỉnh sửa thiết bị #' + device.serial_number);
            $('#device_id').val(device.id);
            $('#serial_number').val(device.serial_number);
            $('#name').val(device.name);
            $('#customer_id').val(device.customer_id);
            $('#warranty_end_date').val(device.warranty_end_date);
            $('#type').val(device.type);
            $('#status').val(device.status);
        });
    }

    // Lưu thiết bị (Add & Edit cùng 1 API)
    $('#btnSaveDevice').click(function() {
        $.post('../../backend/api/save_device.php', {
            id: $('#device_id').val(),
            serial_number: $('#serial_number').val().trim(),
            name: $('#name').val().trim(),
            customer_id: $('#customer_id').val(),
            warranty_end_date: $('#warranty_end_date').val(),
            type: $('#type').val(),
            status: $('#status').val()
        }, function(res) {
            if (res.success) {
                alert(res.message);
                $('#modalDevice').modal('hide');
                location.reload(); // Hoặc reload DataTable nếu bạn dùng
            } else {
                alert('Lỗi: ' + res.message);
            }
        }, 'json');
    });
});

