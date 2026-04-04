/**
 * manager_actions.js - Logic trang Manager
 * [FIX] Đổi $.ajax (gửi form-data) sang fetch JSON để đồng bộ với assign_ticket.php
 */

function assignTicket(ticketId) {
    var staffId = $('#staff_assign_' + ticketId).val();

    if (!staffId) {
        alert("Vui lòng chọn một kỹ thuật viên để giao việc!");
        return;
    }

    if (!confirm("Xác nhận giao phiếu #TICK-" + ticketId + " cho kỹ thuật viên này?")) return;

    var btn = event.currentTarget;
    var originalHTML = btn ? btn.innerHTML : '';
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>'; }

    fetch('../../backend/api/assign_ticket.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ ticket_id: parseInt(ticketId), staff_id: parseInt(staffId) })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert(res.message);
            location.reload();
        } else {
            alert("Lỗi: " + (res.error || "Không xác định"));
        }
    })
    .catch(() => alert("Không kết nối được với máy chủ."))
    .finally(() => { if (btn) { btn.disabled = false; btn.innerHTML = originalHTML; } });
}

// ==========================================
// ĐĂNG XUẤT NHÂN VIÊN NỘI BỘ
// ==========================================
function logoutStaff() {
    fetch("../../backend/api/logout.php", {
        method: "GET", credentials: "include",
        headers: { "Accept": "application/json" }
    })
    .then(r => r.json())
    .then(() => { window.location.href = "index.php"; })
    .catch(() => { window.location.href = "index.php"; });
}
