
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
    // Không cần load lại ngay vì server đã render sẵn,
    // nhưng gọi 1 lần để chuẩn bị prevUnreadCount cho polling
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

    // ============================================
    // MODAL GIA HẠN BẢO HÀNH
    // ============================================

    // Khi modal mở: reset form và load danh sách khách hàng
    $('#modalGiaHanBH').on('show.bs.modal', function () {
        resetGiaHanForm();
        loadCustomersForGiaHan();
    });

    function resetGiaHanForm() {
        $('#gh_customer_id').val('');
        $('#gh_device_id').html('<option value="">-- Chọn thiết bị --</option>');
        $('#gh_device_block').hide();
        $('#gh_device_info').hide();
        $('#gh_extend_block').hide();
        $('#gh_new_end_date').val('');
        $('#gh_cost').val('');
        $('#gh_note').val('');
        $('#btnLuuGiaHan').prop('disabled', true);
        window._ghCurrentEndDate = null;
    }

    function loadCustomersForGiaHan() {
        $.get('../../backend/api/get_customers.php', function (data) {
            let html = '<option value="">-- Chọn khách hàng --</option>';
            if (Array.isArray(data)) {
                data.forEach(c => {
                    html += `<option value="${c.id}">${escHtml(c.name)} (${escHtml(c.phone)})</option>`;
                });
            }
            $('#gh_customer_id').html(html);
        }).fail(function () {
            alert('Lỗi tải danh sách khách hàng.');
        });
    }

    // Khi chọn khách hàng → load thiết bị của khách đó
    $('#gh_customer_id').on('change', function () {
        const customerId = $(this).val();

        // Reset các bước sau
        $('#gh_device_id').html('<option value="">-- Chọn thiết bị --</option>');
        $('#gh_device_info').hide();
        $('#gh_extend_block').hide();
        $('#btnLuuGiaHan').prop('disabled', true);
        window._ghCurrentEndDate = null;

        if (!customerId) {
            $('#gh_device_block').hide();
            return;
        }

        // Gọi API devices, lọc theo customer
        $.get('../../backend/api/devices.php?customer_id=' + customerId, function (res) {
            const devices = res.success ? res.data : (Array.isArray(res) ? res : []);
            let html = '<option value="">-- Chọn thiết bị --</option>';
            if (devices.length === 0) {
                html = '<option value="" disabled>Khách hàng này chưa có thiết bị nào</option>';
            } else {
                devices.forEach(d => {
                    const expiry = d.warranty_end_date
                        ? ' | Hết hạn: ' + new Date(d.warranty_end_date).toLocaleDateString('vi-VN')
                        : ' | Chưa có BH';
                    html += `<option value="${d.id}"
                                data-serial="${escHtml(d.serial_number || '')}"
                                data-end="${d.warranty_end_date || ''}">${escHtml(d.name)}${expiry}</option>`;
                });
            }
            $('#gh_device_id').html(html);
            $('#gh_device_block').show();
        }).fail(function () {
            alert('Lỗi tải danh sách thiết bị.');
        });
    });

    // Khi chọn thiết bị → hiện thông tin và form nhập gia hạn
    $('#gh_device_id').on('change', function () {
        const opt = $(this).find('option:selected');
        const deviceId = $(this).val();

        $('#gh_device_info').hide();
        $('#gh_extend_block').hide();
        $('#btnLuuGiaHan').prop('disabled', true);
        window._ghCurrentEndDate = null;

        if (!deviceId) return;

        const serial  = opt.data('serial') || '—';
        const endDate = opt.data('end') || '';
        window._ghCurrentEndDate = endDate;

        // Hiển thị thông tin thiết bị
        $('#gh_serial').text(serial);
        if (endDate) {
            const d = new Date(endDate);
            const isPast = d < new Date();
            $('#gh_current_end').html(
                d.toLocaleDateString('vi-VN') +
                (isPast ? ' <span class="badge bg-danger ms-1">Đã hết hạn</span>'
                        : ' <span class="badge bg-success ms-1">Còn hiệu lực</span>')
            );
            // min của ngày mới = ngày hết hạn cũ + 1 ngày
            const minDate = new Date(d);
            minDate.setDate(minDate.getDate() + 1);
            $('#gh_new_end_date').attr('min', minDate.toISOString().split('T')[0]);
        } else {
            $('#gh_current_end').html('<span class="text-muted">Chưa có bảo hành</span>');
            // Nếu chưa có BH thì cho nhập từ ngày mai trở đi
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            $('#gh_new_end_date').attr('min', tomorrow.toISOString().split('T')[0]);
        }

        $('#gh_device_info').show();
        $('#gh_extend_block').show();
        $('#btnLuuGiaHan').prop('disabled', false);
    });

    // Lưu gia hạn
    $('#btnLuuGiaHan').click(function () {
        const customerId = $('#gh_customer_id').val();
        const deviceId   = $('#gh_device_id').val();
        const newEndDate = $('#gh_new_end_date').val();
        const cost       = $('#gh_cost').val();
        const note       = $('#gh_note').val().trim();

        if (!customerId || !deviceId || !newEndDate || !cost) {
            alert('Vui lòng điền đầy đủ các trường bắt buộc!');
            return;
        }

        // Validate: ngày mới phải sau ngày cũ
        if (window._ghCurrentEndDate && newEndDate <= window._ghCurrentEndDate) {
            alert('Ngày hết hạn mới phải sau ngày hết hạn hiện tại!');
            return;
        }

        const btn = this;
        $(btn).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Đang lưu...');

        fetch('../../backend/api/extend_warranty.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({
                customer_id : parseInt(customerId),
                device_id   : parseInt(deviceId),
                new_end_date: newEndDate,
                cost        : parseFloat(cost),
                note        : note
            })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                alert('✅ Gia hạn bảo hành thành công!\nHóa đơn: ' + res.invoice_number);
                $('#modalGiaHanBH').modal('hide');
                location.reload();
            } else {
                alert('❌ Lỗi: ' + (res.error || res.message || 'Không xác định'));
            }
        })
        .catch(() => alert('❌ Lỗi kết nối máy chủ!'))
        .finally(() => {
            $(btn).prop('disabled', false).html('<i class="fa fa-save"></i> Lưu gia hạn');
        });
    });
    // Tạo đơn hàng
    // Xử lý thêm/xóa dòng chi tiết phí sửa chữa
    $('#btnAddItem').click(function() {
        $('#order_items_body').append(`
            <tr>
                <td><input type="text" class="form-control item-name" placeholder="Tên mục phí" required></td>
                <td><input type="number" class="form-control item-price" placeholder="Giá tiền" required></td>
                <td class="text-center"><button type="button" class="btn btn-danger btn-sm btn-remove-item"><i class="fa fa-trash"></i></button></td>
            </tr>
        `);
    });
    $(document).on('click', '.btn-remove-item', function() {
        $(this).closest('tr').remove();
    });

    // Tạo đơn hàng (Bắn dữ liệu sang orders.php)
    $('#btnTaoDonHang').click(function() {
        const customer_id = $('#customer_id_order').val();
        const ticket_id = $('#repair_ticket_id_order').val();
        const items = [];
        
        // Gom dữ liệu từ các dòng trong bảng
        $('#order_items_body tr').each(function() {
            const name = $(this).find('.item-name').val().trim();
            const price = parseFloat($(this).find('.item-price').val());
            if (name && price > 0) {
                items.push({ item_name: name, price: price, quantity: 1 });
            }
        });

        if (!customer_id) { alert('Vui lòng chọn khách hàng!'); return; }
        if (items.length === 0) { alert('Vui lòng thêm ít nhất 1 mục phí sửa chữa!'); return; }

        // Gọi API orders.php (phương thức POST)
        fetch('../../backend/api/orders.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                customer_id: customer_id,
                repair_ticket_id: ticket_id,
                order_type: 'repair',
                status: 'unpaid',
                items: items
            })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                alert('Tạo hóa đơn thành công!');
                $('#modalTaoDonHang').modal('hide');
                location.reload();
            } else {
                alert('Lỗi: ' + res.error);
            }
        }).catch(err => alert('Lỗi kết nối máy chủ!'));
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
            // Chỉ reload 2 bảng liên quan, không cần F5 cả trang
            loadPendingTickets();
            loadOngoingTickets();
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

// ==========================================
// AJAX RELOAD: Tải lại Bảng 1 (Phiếu chờ phân công)
// ==========================================
function loadPendingTickets() {
    const tbody = document.getElementById('pending-tickets-tbody');
    if (!tbody) return;

    fetch('../../backend/api/repair_tickets.php?status=pending&unassigned=1', { credentials: 'include' })
        .then(r => r.ok ? r.json() : null)
        .then(res => {
            if (!res || !res.success) return;
            const data = res.data;

            if (!data || data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Tuyệt vời! Hiện không có yêu cầu nào đang tồn đọng.</td></tr>';
                return;
            }

            const canAssign = ['admin', 'manager'].includes(CURRENT_ROLE);

            tbody.innerHTML = data.map(tick => {
                const staffOptions = (typeof STAFF_LIST !== 'undefined' ? STAFF_LIST : [])
                    .map(s => `<option value="${s.id}">${escHtml(s.name)}</option>`)
                    .join('');

                const assignCols = canAssign ? `
                    <td class="text-center">
                        <input type="date" class="form-control form-control-sm mb-1"
                               id="due_date_${tick.id}" min="${new Date().toISOString().slice(0,10)}"
                               placeholder="Hạn hoàn thành">
                        <select class="form-control form-control-sm" id="staff_assign_${tick.id}">
                            <option value="">-- Chọn thợ --</option>
                            ${staffOptions}
                        </select>
                    </td>
                    <td class="text-center action-col">
                        <button class="btn btn-sm btn-success" onclick="assignTicket(${tick.id})">
                            <i class="fa fa-check"></i> Chốt
                        </button>
                    </td>` : '';

                return `<tr>
                    <td class="text-center"><strong>#TICK-${tick.id}</strong></td>
                    <td class="text-center">
                        ${escHtml(tick.customer_name || '—')}<br>
                        <small class="text-muted"><i class="fa fa-phone"></i> ${escHtml(tick.customer_phone || 'Không có')}</small>
                    </td>
                    <td class="text-center">${escHtml(tick.device_name || '—')}</td>
                    <td class="text-center">${escHtml(tick.description || '—')}</td>
                    ${assignCols}
                </tr>`;
            }).join('');
        })
        .catch(() => {}); // im lặng nếu mạng tạm thời lỗi
}

// ==========================================
// AJAX RELOAD: Tải lại Bảng 3 (Theo dõi tiến độ sửa chữa)
// ==========================================
function loadOngoingTickets() {
    const tbody = document.getElementById('ongoing-tickets-tbody');
    if (!tbody) return;

    fetch('../../backend/api/repair_tickets.php?statuses=pending,repairing&assigned=1', { credentials: 'include' })
        .then(r => r.ok ? r.json() : null)
        .then(res => {
            if (!res || !res.success) return;
            const data = res.data;

            if (!data || data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Không có phiếu nào đang xử lý.</td></tr>';
                return;
            }

            tbody.innerHTML = data.map(tick => {
                const progress = parseInt(tick.progress) || 0;
                const barColor = progress >= 90 ? 'bg-success' : progress < 30 ? 'bg-danger' : 'bg-info';

                // Badge trạng thái duyệt giá
                let approvalBadge = '';
                if (parseFloat(tick.estimated_cost) > 0) {
                    if (tick.customer_approval === 'waiting') {
                        approvalBadge = '<div class="mt-1"><span class="badge badge-warning"><i class="fa fa-spinner fa-spin"></i> Khách đang chờ duyệt giá</span></div>';
                    } else if (tick.customer_approval === 'approved') {
                        approvalBadge = '<div class="mt-1"><span class="badge badge-success"><i class="fa fa-check"></i> Khách đã ĐỒNG Ý giá</span></div>';
                    } else if (tick.customer_approval === 'rejected') {
                        approvalBadge = '<div class="mt-1"><span class="badge badge-danger"><i class="fa fa-times"></i> Khách TỪ CHỐI giá</span></div>';
                    }
                }

                const btnClass   = tick.status === 'repairing' ? 'btn-warning-idt' : 'btn-info-idt';
                const statusText = tick.status === 'repairing' ? 'Đang sửa chữa' : 'Chờ xử lý';

                return `<tr>
                    <td class="text-center"><strong>#TICK-${tick.id}</strong></td>
                    <td class="text-center">
                        ${escHtml(tick.device_name || '—')}
                        ${approvalBadge}
                    </td>
                    <td class="text-center">${escHtml(tick.staff_name || 'Chờ phân công')}</td>
                    <td class="align-middle">
                        <div class="progress idt-progress-bar" style="margin-bottom:5px;">
                            <div class="progress-bar ${barColor}" style="width:${progress}%;"></div>
                        </div>
                        <div class="text-center"><small class="font-weight-bold">${progress}%</small></div>
                    </td>
                    <td class="text-center action-col">
                        <span class="status-btn ${btnClass}">${statusText}</span>
                    </td>
                </tr>`;
            }).join('');
        })
        .catch(() => {});
}

// Helper: escape HTML để tránh XSS khi render dữ liệu từ API
function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

// ==========================================
// POLLING: Tự động cập nhật thông báo & bảng phiếu mỗi 30 giây
// ==========================================
(function startManagerPolling() {
    let prevUnreadCount = 0;

    setInterval(function() {
        // 1. Reload cả 2 bảng phiếu để thấy trạng thái customer_approval mới nhất
        loadPendingTickets();
        loadOngoingTickets();

        // 2. Kiểm tra thông báo mới — chỉ re-render badge nếu có thay đổi
        fetch('../../backend/api/notifications_staff.php', { credentials: 'include' })
            .then(r => r.ok ? r.json() : null)
            .then(res => {
                if (!res || !res.success) return;
                const unread = res.data.filter(n => !parseInt(n.is_read)).length;
                if (unread > prevUnreadCount) {
                    loadStaffNotifications();
                    _flashTabTitle('🔔 Có thông báo mới!');
                }
                prevUnreadCount = unread;
            })
            .catch(() => {});
    }, 30000); // 30 giây / lần
})();

// Nháy tiêu đề tab browser để thu hút sự chú ý
let _flashInterval = null;
function _flashTabTitle(msg) {
    if (_flashInterval) return;
    const original = document.title;
    let show = true, count = 0;
    _flashInterval = setInterval(function() {
        document.title = show ? msg : original;
        show = !show;
        count++;
        if (count >= 10) {
            clearInterval(_flashInterval);
            _flashInterval = null;
            document.title = original;
        }
    }, 800);
}

