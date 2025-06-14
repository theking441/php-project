<?php
/**
 * Tệp này sửa lỗi đăng nhập tài khoản admin
 * Chạy một lần để cập nhật tài khoản admin trong cơ sở dữ liệu
 */
require_once 'includes/config.php';

// Kiểm tra xem đã có kết nối đến cơ sở dữ liệu chưa
if (!$conn) {
    die("Không thể kết nối đến cơ sở dữ liệu. Vui lòng kiểm tra lại thông tin kết nối trong includes/config.php");
}

echo "<h1>Đang sửa lỗi tài khoản admin...</h1>";

// Kiểm tra xem bảng users đã tồn tại chưa
$check_table = "SHOW TABLES LIKE 'users'";
$result = mysqli_query($conn, $check_table);

if (mysqli_num_rows($result) == 0) {
    echo "<p>Bảng 'users' chưa tồn tại. Vui lòng nhập cơ sở dữ liệu từ tệp database.sql trước.</p>";
    exit;
}

// Kiểm tra xem tài khoản admin đã tồn tại chưa
$check_admin = "SELECT * FROM users WHERE username = 'admin'";
$result = mysqli_query($conn, $check_admin);

if (mysqli_num_rows($result) > 0) {
    // Cập nhật tài khoản admin hiện có
    echo "<p>Tài khoản admin đã tồn tại. Đang cập nhật...</p>";
    
    // Mật khẩu: admin123
    $password_hash = '$2y$10$8tFHL8Rl9FmgRV7n9BgO2eMmjhVjL0mYfGQQxJXRZzr7ZVtqevqDm';
    
    $update_query = "UPDATE users SET 
                    password = '$password_hash',
                    profile_image = 'default_avatar.png',
                    is_admin = 1
                    WHERE username = 'admin'";
    
    if (mysqli_query($conn, $update_query)) {
        echo "<p style='color: green;'>Đã cập nhật tài khoản admin thành công!</p>";
    } else {
        echo "<p style='color: red;'>Lỗi khi cập nhật tài khoản admin: " . mysqli_error($conn) . "</p>";
    }
} else {
    // Tạo tài khoản admin mới
    echo "<p>Tài khoản admin chưa tồn tại. Đang tạo mới...</p>";
    
    // Mật khẩu: admin123
    $password_hash = '$2y$10$8tFHL8Rl9FmgRV7n9BgO2eMmjhVjL0mYfGQQxJXRZzr7ZVtqevqDm';
    
    $insert_query = "INSERT INTO users (username, email, password, first_name, last_name, grade, profile_image, is_admin) 
                     VALUES ('admin', 'admin@example.com', '$password_hash', 'Admin', 'User', 5, 'default_avatar.png', 1)";
    
    if (mysqli_query($conn, $insert_query)) {
        echo "<p style='color: green;'>Đã tạo tài khoản admin thành công!</p>";
    } else {
        echo "<p style='color: red;'>Lỗi khi tạo tài khoản admin: " . mysqli_error($conn) . "</p>";
    }
}

echo "<h2>Thông tin đăng nhập admin:</h2>";
echo "<p><strong>Tên đăng nhập:</strong> admin</p>";
echo "<p><strong>Mật khẩu:</strong> admin123</p>";
echo "<p><a href='login.php'>Đi đến trang đăng nhập</a></p>";
?>

<style>
    body {
        font-family: Arial, sans-serif;
        line-height: 1.6;
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }
    h1 {
        color: #8A4FFF;
    }
    p {
        margin-bottom: 15px;
    }
</style>