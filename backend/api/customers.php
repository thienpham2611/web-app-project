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
// GET: danh sách hoặc 1 khách hàng
// ──────────────────────────────────────────
function handleGet($conn) {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = mysqli_prepare($conn, "SELECT id, name, phone, email, address, created_at, updated_at FROM customers WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$row) {
            http_response_code(404);
            echo json_encode(["success" => false, "error" => "Không tìm thấy khách hàng"]);
            return;
        }
        echo json_encode(["success" => true, "data" => $row]);
        return;
    }

    $search = trim($_GET['search'] ?? '');
    if ($search !== '') {
        $like = "%$search%";
        $stmt = mysqli_prepare($conn,
            "SELECT id, name, phone, email, address, created_at, updated_at
             FROM customers WHERE name LIKE ? OR phone LIKE ? OR email LIKE ? ORDER BY id DESC");
        mysqli_stmt_bind_param($stmt, "sss", $like, $like, $like);
    } else {
        $stmt = mysqli_prepare($conn,
            "SELECT id, name, phone, email, address, created_at, updated_at
             FROM customers ORDER BY id DESC");
    }

    mysqli_stmt_execute($stmt);
    $data = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    echo json_encode(["success" => true, "data" => $data]);
}

// ──────────────────────────────────────────
// POST: tạo mới — chỉ admin/manager
// ──────────────────────────────────────────
function handlePost($conn) {
    // [FIX 1] Staff không được tạo khách hàng
    if (!in_array($_SESSION['role'], ['admin', 'manager'])) {
        http_response_code(403);
        echo json_encode(["success" => false, "error" => "Không có quyền thực hiện"]);
        return;
    }

    $input   = json_decode(file_get_contents("php://input"), true) ?? $_POST;
    $name    = trim($input['name'] ?? '');
    $phone   = trim($input['phone'] ?? '');
    $email   = trim($input['email'] ?? '');
    $address = trim($input['address'] ?? '');
    $password = trim($input['password'] ?? '');

    if ($name === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Tên khách hàng là bắt buộc"]);
        return;
    }

    // [FIX 2] Password bắt buộc khi tạo từ nội bộ, hash bcrypt
    if ($password === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Mật khẩu là bắt buộc"]);
        return;
    }
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // [FIX 3] Kiểm tra email trùng trước khi INSERT
    if ($email !== '') {
        $chk = mysqli_prepare($conn, "SELECT id FROM customers WHERE email = ?");
        mysqli_stmt_bind_param($chk, "s", $email);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        if (mysqli_stmt_num_rows($chk) > 0) {
            http_response_code(409);
            echo json_encode(["success" => false, "error" => "Email đã được sử dụng"]);
            return;
        }
    }

    $stmt = mysqli_prepare($conn,
        "INSERT INTO customers (name, phone, email, password, address) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sssss", $name, $phone, $email, $hashedPassword, $address);

    if (mysqli_stmt_execute($stmt)) {
        http_response_code(201);
        echo json_encode(["success" => true, "message" => "Tạo khách hàng thành công", "id" => mysqli_insert_id($conn)]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Tạo khách hàng thất bại"]);
    }
}

// ──────────────────────────────────────────
// PUT: cập nhật — chỉ admin/manager
// ──────────────────────────────────────────
function handlePut($conn) {
    // [FIX 1] Staff không được sửa khách hàng
    if (!in_array($_SESSION['role'], ['admin', 'manager'])) {
        http_response_code(403);
        echo json_encode(["success" => false, "error" => "Không có quyền thực hiện"]);
        return;
    }

    $input   = json_decode(file_get_contents("php://input"), true) ?? [];
    $id      = intval($input['id'] ?? 0);
    $name    = trim($input['name'] ?? '');
    $phone   = trim($input['phone'] ?? '');
    $email   = trim($input['email'] ?? '');
    $address = trim($input['address'] ?? '');

    if ($id <= 0 || $name === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Dữ liệu không hợp lệ"]);
        return;
    }

    // [FIX 3] Kiểm tra email trùng với khách hàng KHÁC trước khi UPDATE
    if ($email !== '') {
        $chk = mysqli_prepare($conn, "SELECT id FROM customers WHERE email = ? AND id != ?");
        mysqli_stmt_bind_param($chk, "si", $email, $id);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        if (mysqli_stmt_num_rows($chk) > 0) {
            http_response_code(409);
            echo json_encode(["success" => false, "error" => "Email đã được sử dụng bởi khách hàng khác"]);
            return;
        }
    }

    $stmt = mysqli_prepare($conn,
        "UPDATE customers SET name=?, phone=?, email=?, address=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "ssssi", $name, $phone, $email, $address, $id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(["success" => true, "message" => "Cập nhật khách hàng thành công"]);
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
        echo json_encode(["success" => false, "error" => "Thiếu mã khách hàng"]);
        return;
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM customers WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(["success" => true, "message" => "Xóa khách hàng thành công"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Xóa thất bại"]);
    }
}
