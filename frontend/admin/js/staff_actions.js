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
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">Bạn hiện chưa có công việc nào được giao.</td></tr>';
            return;
        }

        res.data.forEach(item => {
            let sc='badge-secondary', st='Chờ xử lý';
            if(item.status==='repairing'){sc='badge-info'; st='Đang sửa';}
            if(item.status==='completed'){sc='badge-success'; st='Hoàn tất';}
            if(item.status==='cancelled'){sc='badge-danger'; st='Đã hủy';}

            const bar = parseInt(item.progress)||0;
            const barColor = bar>=90?'bg-success':bar<30?'bg-danger':'bg-info';

            // [ĐÃ SỬA]: Gọi hàm openUpdateModal thay vì updateTicketStatus cũ
            tbody.innerHTML += `<tr>
                <td><strong>#RT-${item.id}</strong></td>
                <td>${item.device_name??'—'}<br><small class="text-muted">S/N: ${item.serial_number??'—'}</small></td>
                <td>${item.customer_name??'—'}<br><small class="text-muted">${item.customer_phone??''}</small></td>
                <td class="align-middle">
                    <div class="progress idt-progress-bar" style="margin-bottom:3px; height: 8px;">
                        <div class="progress-bar ${barColor}" style="width:${bar}%;"></div>
                    </div>
                    <small class="font-weight-bold">${bar}%</small>
                </td>
                <td class="text-center"><span class="badge ${sc} p-2">${st}</span></td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary" onclick="openUpdateModal(${item.id}, '${item.status}', ${bar})">
                        <i class="fa fa-edit"></i> Cập nhật
                    </button>
                </td>
            </tr>`;
        });
    }).catch(err => {
        console.error(err);
        document.getElementById('tech-repair-list').innerHTML = '<tr><td colspan="6" class="text-center text-danger">Lỗi tải dữ liệu.</td></tr>';
    });
}

// 2. Mở hộp thoại Modal và điền dữ liệu cũ
function openUpdateModal(ticketId, currentStatus, currentProgress) {
    document.getElementById('modal_ticket_id').value = ticketId;
    document.getElementById('modal_note').value = ''; // Reset ghi chú

    // Check đúng radio button trạng thái hiện tại
    if (currentStatus === 'completed') {
        document.getElementById('st_completed').checked = true;
    } else if (currentStatus === 'cancelled') {
        document.getElementById('st_cancelled').checked = true;
    } else {
        document.getElementById('st_repairing').checked = true;
    }

    // Set thanh kéo tiến độ
    const progressInput = document.getElementById('modal_progress');
    progressInput.value = currentProgress;
    document.getElementById('progress_display').innerText = currentProgress + '%';

    toggleProgress(); // Ẩn/hiện thanh kéo tùy trạng thái
    loadTicketHistory(ticketId); // Tải lịch sử xử lý
    $('#updateTicketModal').modal('show'); // Hiển thị modal (Yêu cầu có jQuery & Bootstrap)
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

    try {
        // API 1: Cập nhật Trạng thái và Tiến độ vào bảng repair_tickets
        let res1 = await fetch('../../backend/api/repair_tickets.php', {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            credentials: 'include',
            body: JSON.stringify({ id: ticketId, status: status, progress: progress })
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

    } catch (err) {
        console.error("Lỗi:", err);
        alert('❌ Có lỗi xảy ra: ' + err.message);
    }
}

// 6. Đăng xuất (Hàm cũ giữ nguyên)
function logoutStaff() {
    if(!confirm("Đăng xuất khỏi hệ thống?")) return;
    fetch("../../backend/api/logout.php", { credentials: "include" })
    .then(() => { window.location.href = "index.php"; });
}

// 7. Xem lịch sử xử lý
function viewTimeline(ticketId) {
    // Bạn có thể mở modal tương tự như ở trang nhanvien.php 
    // để load file repair_logs.php?ticket_id=...
    alert("Tính năng đang tải lịch sử cho Case #RT-" + ticketId);
}