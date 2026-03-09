<?php
require_once "../config/database.php";

$requiredRoles = ['admin', 'manager', 'staff'];
require_once "../middleware/check_auth.php";

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':    handleGet($conn);    break;
    case 'POST':   handlePost($conn);   break;
    case 'PUT':    handlePut($conn);    break;
    case 'DELETE': handleDelete($conn); break;
    default:
        http_response_code(405);
        echo json_encode(["success" => false, "error" => "Phương thức không được phép"]);
}

// ──────────────────────────────────────────
// GET: đơn hàng + items
// ──────────────────────────────────────────
function handleGet($conn) {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);

        // Lấy order
        $stmt = mysqli_prepare($conn,
            "SELECT o.*, c.name AS customer_name
             FROM orders o
             LEFT JOIN customers c ON c.id = o.customer_id
             WHERE o.id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!$order) {
            http_response_code(404);
            echo json_encode(["success" => false, "error" => "Không tìm thấy đơn hàng"]);
            return;
        }

        // Lấy items
        $stmtI = mysqli_prepare($conn,
            "SELECT oi.*, d.name AS device_name, d.serial_number
             FROM order_items oi
             LEFT JOIN devices d ON d.id = oi.device_id
             WHERE oi.order_id = ?");
        mysqli_stmt_bind_param($stmtI, "i", $id);
        mysqli_stmt_execute($stmtI);
        $resultI = mysqli_stmt_get_result($stmtI);
        $items = [];
        while ($row = mysqli_fetch_assoc($resultI)) $items[] = $row;
        $order['items'] = $items;

        echo json_encode(["success" => true, "data" => $order]);
        return;
    }

    // Danh sách orders
    $where  = [];
    $params = [];
    $types  = '';

    if (!empty($_GET['customer_id'])) {
        $where[]  = "o.customer_id = ?";
        $params[] = intval($_GET['customer_id']);
        $types   .= 'i';
    }
    if (!empty($_GET['status'])) {
        $where[]  = "o.status = ?";
        $params[] = $_GET['status'];
        $types   .= 's';
    }

    $sql = "SELECT o.*, c.name AS customer_name
            FROM orders o
            LEFT JOIN customers c ON c.id = o.customer_id";
    if ($where) $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " ORDER BY o.id DESC";

    $stmt = mysqli_prepare($conn, $sql);
    if ($types) mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) $data[] = $row;
    echo json_encode(["success" => true, "data" => $data]);
}

// ──────────────────────────────────────────
// POST: tạo đơn hàng (kèm items)
// ──────────────────────────────────────────
function handlePost($conn) {
    $input = json_decode(file_get_contents("php://input"), true) ?? [];

    $customer_id  = intval($input['customer_id'] ?? 0);
    $order_date   = $input['order_date'] ?? date('Y-m-d');
    $status       = $input['status'] ?? 'unpaid';
    $items        = $input['items'] ?? [];   // [{device_id, price, quantity}]

    if ($customer_id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Thiếu mã khách hàng"]);
        return;
    }

    if (!in_array($status, ['paid', 'unpaid', 'cancelled'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Trạng thái không hợp lệ"]);
        return;
    }

    // Tính tổng tiền
    $total = 0;
    foreach ($items as $item) {
        $total += floatval($item['price'] ?? 0) * intval($item['quantity'] ?? 1);
    }

    mysqli_begin_transaction($conn);

    try {
        // Insert order
        $stmt = mysqli_prepare($conn,
            "INSERT INTO orders (customer_id, order_date, total_amount, status)
             VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isds", $customer_id, $order_date, $total, $status);
        if (!mysqli_stmt_execute($stmt)) throw new Exception("Tạo đơn hàng thất bại");
        $order_id = mysqli_insert_id($conn);

        // Insert items
        foreach ($items as $item) {
            $device_id = intval($item['device_id'] ?? 0);
            $price     = floatval($item['price'] ?? 0);
            $qty       = intval($item['quantity'] ?? 1);

            if ($device_id <= 0) continue;

            $stmtI = mysqli_prepare($conn,
                "INSERT INTO order_items (order_id, device_id, price, quantity) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmtI, "iidi", $order_id, $device_id, $price, $qty);
            if (!mysqli_stmt_execute($stmtI)) throw new Exception("Thêm sản phẩm vào đơn thất bại");
        }

        mysqli_commit($conn);
        http_response_code(201);
        echo json_encode(["success" => true, "message" => "Tạo đơn hàng thành công", "id" => $order_id]);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        http_response_code(500);
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
}

// ──────────────────────────────────────────
// PUT: cập nhật trạng thái / ngày / tổng tiền
// ──────────────────────────────────────────
function handlePut($conn) {
    $input = json_decode(file_get_contents("php://input"), true) ?? [];

    $id     = intval($input['id'] ?? 0);
    $status = $input['status'] ?? null;

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Thiếu mã đơn hàng"]);
        return;
    }

    if ($status && !in_array($status, ['paid', 'unpaid', 'cancelled'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Trạng thái không hợp lệ"]);
        return;
    }

    $sets   = [];
    $params = [];
    $types  = '';

    if ($status) { $sets[] = "status=?"; $params[] = $status; $types .= 's'; }
    if (isset($input['total_amount'])) { $sets[] = "total_amount=?"; $params[] = floatval($input['total_amount']); $types .= 'd'; }
    if (isset($input['order_date']))   { $sets[] = "order_date=?";   $params[] = $input['order_date']; $types .= 's'; }

    if (!$sets) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Không có gì để cập nhật"]);
        return;
    }

    $params[] = $id; $types .= 'i';
    $stmt = mysqli_prepare($conn, "UPDATE orders SET " . implode(", ", $sets) . " WHERE id=?");
    mysqli_stmt_bind_param($stmt, $types, ...$params);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(["success" => true, "message" => "Cập nhật đơn hàng thành công"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Cập nhật thất bại"]);
    }
}

// ──────────────────────────────────────────
// DELETE: chỉ admin/manager
// ──────────────────────────────────────────
function handleDelete($conn) {
    if (!in_array($_SESSION['role'], ['admin', 'manager'])) {
        http_response_code(403);
        echo json_encode(["success" => false, "error" => "Không có quyền thực hiện"]);
        return;
    }

    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Thiếu mã đơn hàng"]);
        return;
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM orders WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(["success" => true, "message" => "Xóa đơn hàng thành công"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Xóa thất bại"]);
    }
}
