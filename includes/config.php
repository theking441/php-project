<?php
// Cấu hình kết nối cơ sở dữ liệu
$db_host = 'localhost';
$db_user = 'root';     // Thay đổi nếu bạn đã thiết lập tên người dùng khác trong XAMPP
$db_pass = '';         // Thay đổi nếu bạn đã thiết lập mật khẩu trong XAMPP
$db_name = 'math_test_db';

// Tạo kết nối
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Kiểm tra kết nối
if (!$conn) {
    die("Kết nối thất bại: " . mysqli_connect_error());
}

// Đặt charset là utf8mb4
mysqli_set_charset($conn, "utf8mb4");

// Hàm bảo vệ khỏi SQL Injection
function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}

// Hàm tạo mật khẩu băm
function create_password_hash($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Hàm kiểm tra mật khẩu
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// Hàm hiển thị thông báo lỗi
function display_error($message) {
    return "<div class='alert alert-danger'>{$message}</div>";
}

// Hàm hiển thị thông báo thành công
function display_success($message) {
    return "<div class='alert alert-success'>{$message}</div>";
}

// Bắt đầu session
session_start();
?>