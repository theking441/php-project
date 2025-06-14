<?php
// Trang đăng xuất
require_once 'includes/config.php';

// Xóa tất cả các biến session
$_SESSION = array();

// Xóa cookie session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hủy session
session_destroy();

// Thông báo đăng xuất thành công
$_SESSION['message'] = 'Bạn đã đăng xuất thành công!';
$_SESSION['message_type'] = 'success';

// Chuyển hướng về trang chủ
header('Location: index.php');
exit;
?>