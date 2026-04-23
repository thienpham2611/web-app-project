/**
 * staff_actions.js - Quản lý logic dành riêng cho nhân viên kỹ thuật
 */

document.addEventListener("DOMContentLoaded", function() {
    loadMyTickets();
});

// 1. Tải danh sách công việc được giao
function loadMyTickets() {
    fetch('../../backend/api/get_my_tickets.php', {credentials:'include'})
    .then(r => { 
        if(r.status===401){ window.location.href='index.php'; return null; } 
        return r.json(); 
    })
    .then(res => {
        if (!res) return;
        const tbody = document.getElementById('tech-repair-list');
        if (!tbody) return;
        tbody.innerHTML = '';

        if (!res.success || !res.data || res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-3">Bạn hiện chưa có công việc nào được giao.</td></tr>';
            return;
        }

        res.data.forEach(item => {
            let sc='badge-secondary', st='Chờ xử lý';
            if(item.status==='repairing'){sc='badge-info'; st='Đang sửa';}
            if(item.status==='completed'){sc='badge-success'; st='Hoàn tất';}
            if(item.status==='cancelled'){sc='badge-danger'; st='Đã hủy';}

            const bar = parseInt(item.progress)||0;
            const barColor = bar>=90?'bg-success':bar<30?'bg-danger':'bg-info';

            // Xử lý deadline
            let deadlineHtml = '<span class="text-muted">—</span>';
            if (item.due_date) {
                const today = new Date(); today.setHours(0,0,0,0);
                const due = new Date(item.due_date);
                const daysLeft = Math.ceil((due - today) / 86400000);
                const dueFmt = due.toLocaleDateString('vi-VN');
                if (daysLeft < 0) {
                    deadlineHtml = `<span class="badge badge-danger p-1">⚠ Quá hạn ${Math.abs(daysLeft)} ngày<br>${dueFmt}</span>`;
                } else if (daysLeft <= 2) {
                    deadlineHtml = `<span class="badge badge-warning p-1">🔔 Còn ${daysLeft} ngày<br>${dueFmt}</span>`;
                } else {
                    deadlineHtml = `<small class="text-success">${dueFmt}<br>(còn ${daysLeft} ngày)</small>`;
                }
            }

            // Thêm logic hiển thị trạng thái báo giá
            let quoteHtml = '';
            if (item.estimated_cost > 0) {
                if (item.customer_approval === 'waiting') {
                    quoteHtml = '<div class="mt-1"><span class="badge badge-warning"><i class="fa fa-spinner fa-spin"></i> Khách đang chờ duyệt giá</span></div>';
                } else if (item.customer_approval === 'approved') {
                    quoteHtml = '<div class="mt-1"><span class="badge badge-success"><i class="fa fa-check"></i> Khách đã ĐỒNG Ý giá</span></div>';
                } else if (item.customer_approval === 'rejected') {
                    quoteHtml = '<div class="mt-1"><span class="badge badge-danger"><i class="fa fa-times"></i> Khách TỪ CHỐI giá</span></div>';
                }
            }

            tbody.innerHTML += `<tr>
                <td><strong>#RT-${item.id}</strong></td>
                <td>
                    ${item.device_name??'—'}<br>
                    <small class="text-muted">S/N: ${item.serial_number??'—'}</small>
                    ${quoteHtml} </td>
                <td>${item.customer_name??'—'}<br><small class="text-muted">${item.customer_phone??''}</small></td>
                <td class="align-middle">
                    <div class="progress idt-progress-bar" style="margin-bottom:3px; height: 8px;">
                        <div class="progress-bar ${barColor}" style="width:${bar}%;"></div>
                    </div>
                    <small class="font-weight-bold">${bar}%</small>
                </td>
                <td class="text-center">${deadlineHtml}</td>
                <td class="text-center"><span class="badge ${sc} p-2">${st}</span></td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary" onclick="openUpdateModal(${item.id}, '${item.status}', ${bar}, ${item.estimated_cost || 0}, '${item.customer_approval || 'waiting'}')">
                        <i class="fa fa-edit"></i> Cập nhật
                    </button>
                </td>
            </tr>`;
        });
        checkDeadlineNotifications(res.data);
    }).catch(err => {
        console.error(err);
        document.getElementById('tech-repair-list').innerHTML = '<tr><td colspan="7" class="text-center text-danger">Lỗi tải dữ liệu.</td></tr>';
    });
}

// 2. Mở hộp thoại Modal và điền dữ liệu cũ
function openUpdateModal(ticketId, currentStatus, currentProgress, estimatedCost, approval) {
    document.getElementById('modal_ticket_id').value = ticketId;
    document.getElementById('modal_note').value = ''; 
    document.getElementById('modal_estimated_cost').value = estimatedCost == 0 ? '' : estimatedCost;

    // Hiển thị cảnh báo trạng thái duyệt của khách
    const alertBox = document.getElementById('approval_status_alert');
    const radioRepairing = document.getElementById('st_repairing');
    const radioCompleted = document.getElementById('st_completed');
    const costInput = document.getElementById('modal_estimated_cost');

    // Mặc định mở khóa
    radioRepairing.disabled = false;
    radioCompleted.disabled = false;
    costInput.disabled = false;

    if (estimatedCost > 0) {
        if (approval === 'waiting') {
            alertBox.innerHTML = '<span class="text-warning"><i class="fa fa-spinner fa-spin"></i> Đang chờ khách hàng duyệt báo giá. Chưa thể tiến hành sửa!</span>';
            radioRepairing.disabled = true; // Khóa nút sửa
            radioCompleted.disabled = true;
        } else if (approval === 'approved') {
            alertBox.innerHTML = '<span class="text-success"><i class="fa fa-check-circle"></i> Khách hàng đã ĐỒNG Ý báo giá. Có thể tiến hành sửa.</span>';
            costInput.disabled = true; // Khách đã đồng ý thì không được sửa giá nữa
        } else if (approval === 'rejected') {
            alertBox.innerHTML = '<span class="text-danger"><i class="fa fa-times-circle"></i> Khách hàng đã TỪ CHỐI sửa chữa.</span>';
            radioRepairing.disabled = true;
            radioCompleted.disabled = true;
            document.getElementById('st_cancelled').checked = true; // Tự động chọn hủy
        }
    } else {
        alertBox.innerHTML = '<span class="text-secondary"><i class="fa fa-info-circle"></i> Vui lòng nhập báo giá nếu thiết bị tính phí. Khách hàng sẽ nhận được thông báo để duyệt.</span>';
    }

    // Các phần check radio và slider giữ nguyên như code cũ của bạn...
    if (currentStatus === 'completed') document.getElementById('st_completed').checked = true;
    else if (currentStatus === 'cancelled') document.getElementById('st_cancelled').checked = true;
    else document.getElementById('st_repairing').checked = true;

    document.getElementById('modal_progress').value = currentProgress;
    document.getElementById('progress_display').innerText = currentProgress + '%';

    toggleProgress();
    loadTicketHistory(ticketId);
    $('#updateTicketModal').modal('show');
}

// 3. Hàm tải Timeline từ API
function loadTicketHistory(ticketId) {
    const container = document.getElementById('ticket_timeline');
    container.innerHTML = '<small class="text-muted">Đang tải...</small>';

    fetch(`../../backend/api/repair_logs.php?ticket_id=${ticketId}`, {credentials:'include'})
    .then(r => r.json())
    .then(res => {
        if(!res.success || res.data.length === 0) {
            container.innerHTML = '<small class="text-muted">Chưa có ghi chú nào trước đó.</small>';
            return;
        }
        
        let html = '<div class="idt-timeline-mini" style="border-left: 2px solid #ddd; padding-left: 15px;">';
        res.data.forEach(log => {
            html += `
                <div class="mb-2 pb-2 border-bottom">
                    <div class="d-flex justify-content-between">
                        <strong style="font-size: 12px;">${log.user_name}</strong>
                        <small class="text-muted" style="font-size: 10px;">${log.created_at}</small>
                    </div>
                    <div style="font-size: 13px; color: #333;">${log.action}</div>
                    ${log.note ? `<div class="text-info" style="font-size: 12px; font-style: italic;">Ghi chú: ${log.note}</div>` : ''}
                </div>`;
        });
        html += '</div>';
        container.innerHTML = html;
    })
    .catch(() => container.innerHTML = '<small class="text-danger">Lỗi tải lịch sử.</small>');
}

// 4. Logic: Tự động chỉnh tiến độ theo Trạng thái (Hoàn tất = 100%, Hủy = 0%)
function toggleProgress() {
    const isCompleted = document.getElementById('st_completed').checked;
    const isCancelled = document.getElementById('st_cancelled').checked;
    const wrapper = document.getElementById('progress_wrapper');
    const slider = document.getElementById('modal_progress');
    const display = document.getElementById('progress_display');

    if (isCompleted) {
        slider.value = 100;
        display.innerText = '100%';
        wrapper.style.display = 'none'; // Đã xong thì ẩn thanh kéo đi
    } else if (isCancelled) {
        slider.value = 0;
        display.innerText = '0%';
        wrapper.style.display = 'none'; // Hủy thì ẩn thanh kéo đi
    } else {
        wrapper.style.display = 'block'; // Đang sửa thì cho phép kéo
    }
}

// 5. Gửi dữ liệu cập nhật về Backend
async function submitTicketUpdate() {
    const ticketId = document.getElementById('modal_ticket_id').value;
    const status = document.querySelector('input[name="modal_status"]:checked').value;
    const progress = parseInt(document.getElementById('modal_progress').value);
    const note = document.getElementById('modal_note').value.trim();
    const estimatedCost = document.getElementById('modal_estimated_cost').value;

    try {
        // API 1: Cập nhật Trạng thái và Tiến độ vào bảng repair_tickets
        let res1 = await fetch('../../backend/api/repair_tickets.php', {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            credentials: 'include',
            body: JSON.stringify({ id: ticketId, status: status, progress: progress, estimated_cost: estimatedCost })
        }).then(r => r.json());

        if (!res1.success) throw new Error(res1.error);

        // API 2: Nếu nhân viên có nhập Ghi chú, lưu vào bảng repair_logs
        if (note !== '') {
            let actionText = `Cập nhật tiến độ: ${progress}% (Trạng thái: ${status})`;
            let res2 = await fetch('../../backend/api/repair_logs.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                credentials: 'include',
                body: JSON.stringify({
                    repair_ticket_id: ticketId,
                    action: actionText,
                    note: note
                })
            }).then(r => r.json());
            
            if (!res2.success) console.warn("Lỗi lưu log:", res2.error);
        }

        alert('✅ Đã cập nhật thành công!');
        $('#updateTicketModal').modal('hide'); // Đóng Modal
        loadMyTickets(); // Load lại bảng ngay lập tức
        loadStaffNotifications();

    } catch (err) {
        console.error("Lỗi:", err);
        alert('❌ Có lỗi xảy ra: ' + err.message);
    }
}


// ==========================================
// THÔNG BÁO NHÂN VIÊN
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
        if (unread > 0) {
            badge.textContent = unread > 9 ? '9+' : unread;
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }

        list.innerHTML = res.data.map(n => {
            const bg    = parseInt(n.is_read) ? '' : 'background:#fff8e1;';
            const time  = n.created_at ? new Date(n.created_at).toLocaleString('vi-VN', {day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'}) : '';
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
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ id })
    }).then(() => {
        if (el) el.style.background = '';
        loadStaffNotifications();
    });
}

function markAllStaffNotifRead() {
    fetch('../../backend/api/notifications_staff.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ mark_all: true })
    }).then(() => loadStaffNotifications());
}

// Tự động tạo thông báo nhắc deadline sắp đến (≤ 2 ngày) nếu chưa có
function checkDeadlineNotifications(tickets) {
    const today = new Date(); today.setHours(0,0,0,0);
    tickets.forEach(item => {
        if (!item.due_date) return;
        const due = new Date(item.due_date);
        const daysLeft = Math.ceil((due - today) / 86400000);
        if (daysLeft >= 0 && daysLeft <= 2) {
            const msg = daysLeft === 0
                ? `⚠️ Phiếu #RT-${item.id} đến hạn HÔM NAY! Hãy hoàn thành ngay.`
                : `🔔 Phiếu #RT-${item.id} còn ${daysLeft} ngày đến deadline (${new Date(item.due_date).toLocaleDateString('vi-VN')}).`;

            fetch('../../backend/api/notifications_staff.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ message: msg })
            });
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    loadStaffNotifications();
    const bell = document.getElementById('staff-notif-bell');
    if (bell) {
        bell.addEventListener('show.bs.dropdown', function() {
            // Tính vị trí dropdown theo bell icon
            
            // Mark all read rồi load
            fetch('../../backend/api/notifications_staff.php', {
                method: 'PUT', headers: {'Content-Type':'application/json'},
                credentials: 'include',
                body: JSON.stringify({ mark_all: true })
            }).then(() => loadStaffNotifications());
        });
    }
});

// 6. Đăng xuất (Hàm cũ giữ nguyên)
function logoutStaff() {
    if(!confirm("Đăng xuất khỏi hệ thống?")) return;
    fetch("../../backend/api/logout.php", { credentials: "include" })
    .then(() => { window.location.href = "index.php"; });
}