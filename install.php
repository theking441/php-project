<?php
/**
 * Tệp cài đặt tự động cho Math Friends
 * Công cụ này sẽ giúp cài đặt cơ sở dữ liệu và cấu hình tự động
 */

// Ngăn chặn truy cập trực tiếp nếu đã cài đặt
if (file_exists('includes/config.php') && filesize('includes/config.php') > 0) {
    $config_content = file_get_contents('includes/config.php');
    if (strpos($config_content, 'DB_INSTALLED') !== false) {
        die('Ứng dụng đã được cài đặt. Nếu bạn muốn cài đặt lại, hãy xóa tệp includes/config.php và chạy lại trang này.');
    }
}

// Thiết lập thông số mặc định
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'math_test_db';
$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$message = '';
$error = '';

// Ghi đè giá trị từ form nếu có
if (isset($_POST['db_name'])) {
    $db_name = trim($_POST['db_name']);
}

// Xử lý biểu mẫu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 1 && isset($_POST['db_host'], $_POST['db_user'], $_POST['db_name'])) {
        $db_host = trim($_POST['db_host']);
        $db_user = trim($_POST['db_user']);
        $db_pass = isset($_POST['db_pass']) ? $_POST['db_pass'] : '';
        $db_name = trim($_POST['db_name']);
        
        // Kiểm tra kết nối cơ sở dữ liệu
        $conn = @mysqli_connect($db_host, $db_user, $db_pass);
        
        if (!$conn) {
            $error = 'Không thể kết nối đến cơ sở dữ liệu: ' . mysqli_connect_error();
        } else {
            // Tạo cơ sở dữ liệu nếu chưa tồn tại
            if (!mysqli_select_db($conn, $db_name)) {
                $sql = "CREATE DATABASE IF NOT EXISTS `$db_name`";
                if (mysqli_query($conn, $sql)) {
                    if (mysqli_select_db($conn, $db_name)) {
                        $message = "Đã tạo cơ sở dữ liệu '$db_name' thành công!";
                    } else {
                        $error = 'Không thể chọn cơ sở dữ liệu: ' . mysqli_error($conn);
                        mysqli_close($conn);
                        $conn = null;
                    }
                } else {
                    $error = 'Không thể tạo cơ sở dữ liệu: ' . mysqli_error($conn);
                    mysqli_close($conn);
                    $conn = null;
                }
            }
            
            if ($conn) {
                // Chuyển đến bước tiếp theo
                header('Location: install.php?step=2&db_host=' . urlencode($db_host) 
                    . '&db_user=' . urlencode($db_user) 
                    . '&db_pass=' . urlencode($db_pass) 
                    . '&db_name=' . urlencode($db_name));
                exit;
            }
        }
    } elseif ($step === 2 && isset($_POST['install']) && isset($_GET['db_host'], $_GET['db_user'], $_GET['db_name'])) {
        $db_host = $_GET['db_host'];
        $db_user = $_GET['db_user'];
        $db_pass = isset($_GET['db_pass']) ? $_GET['db_pass'] : '';
        $db_name = $_GET['db_name'];
        
        // Kết nối cơ sở dữ liệu
        $conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);
        
        if (!$conn) {
            $error = 'Không thể kết nối đến cơ sở dữ liệu: ' . mysqli_connect_error();
        } else {
            // Đọc tệp SQL
            $sql_file = file_get_contents('database.sql');
            
            // Chia các câu lệnh SQL
            $queries = explode(';', $sql_file);
            $success = true;
            
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    if (!mysqli_query($conn, $query)) {
                        $error = 'Lỗi khi thực thi SQL: ' . mysqli_error($conn);
                        $success = false;
                        break;
                    }
                }
            }
            
            if ($success) {
                // Tạo tệp cấu hình
                $config_content = '<?php
// Cấu hình kết nối cơ sở dữ liệu
$db_host = \'' . addslashes($db_host) . '\';
$db_user = \'' . addslashes($db_user) . '\';
$db_pass = \'' . addslashes($db_pass) . '\';
$db_name = \'' . addslashes($db_name) . '\';

// Đánh dấu đã cài đặt
define(\'DB_INSTALLED\', true);

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
    return "<div class=\'alert alert-danger\'>{$message}</div>";
}

// Hàm hiển thị thông báo thành công
function display_success($message) {
    return "<div class=\'alert alert-success\'>{$message}</div>";
}

// Bắt đầu session
session_start();
?>';
                
                // Đảm bảo thư mục tồn tại
                if (!file_exists('includes')) {
                    mkdir('includes', 0755, true);
                }
                
                // Ghi tệp cấu hình
                if (file_put_contents('includes/config.php', $config_content)) {
                    // Tạo các thư mục cần thiết
                    $dirs = ['images', 'images/avatars', 'images/badges'];
                    foreach ($dirs as $dir) {
                        if (!file_exists($dir)) {
                            mkdir($dir, 0755, true);
                        }
                    }
                    
                    // Chuyển đến bước cuối cùng
                    header('Location: install.php?step=3');
                    exit;
                } else {
                    $error = 'Không thể tạo tệp cấu hình. Vui lòng đảm bảo PHP có quyền ghi vào thư mục includes/';
                }
            }
            
            mysqli_close($conn);
        }
    } elseif ($step === 3 && isset($_POST['finish'])) {
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt Math Friends</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f7f9fc;
            font-family: 'Rounded Mplus 1c', 'Quicksand', 'Comic Sans MS', sans-serif;
        }
        .install-container {
            max-width: 800px;
            margin: 50px auto;
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        .install-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .install-header h1 {
            color: #8A4FFF;
            font-weight: bold;
        }
        .install-step {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .step-item {
            flex: 1;
            text-align: center;
            position: relative;
        }
        .step-item:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 20px;
            right: -50%;
            width: 100%;
            height: 2px;
            background-color: #e1e5ea;
            z-index: 1;
        }
        .step-number {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 40px;
            height: 40px;
            background-color: #e1e5ea;
            color: #6c757d;
            border-radius: 50%;
            font-weight: bold;
            margin-bottom: 10px;
            position: relative;
            z-index: 2;
        }
        .step-item.active .step-number {
            background-color: #8A4FFF;
            color: #fff;
        }
        .step-item.completed .step-number {
            background-color: #76E383;
            color: #fff;
        }
        .step-item.completed:not(:last-child)::after {
            background-color: #76E383;
        }
        .step-label {
            font-weight: 600;
            color: #6c757d;
        }
        .step-item.active .step-label {
            color: #8A4FFF;
        }
        .step-item.completed .step-label {
            color: #76E383;
        }
        .form-label {
            font-weight: 600;
        }
        .btn-primary {
            background-color: #8A4FFF;
            border-color: #8A4FFF;
        }
        .btn-primary:hover {
            background-color: #7A3FDF;
            border-color: #7A3FDF;
        }
        .success-animation {
            text-align: center;
            margin: 30px 0;
        }
        .success-icon {
            font-size: 80px;
            color: #76E383;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <h1>Math Friends - Trình cài đặt</h1>
            <p class="lead">Nền tảng kiểm tra toán học dành cho học sinh tiểu học</p>
        </div>
        
        <div class="install-step">
            <div class="step-item <?php echo ($step >= 1) ? 'active' : ''; ?> <?php echo ($step > 1) ? 'completed' : ''; ?>">
                <div class="step-number"><?php echo ($step > 1) ? '<i class="fas fa-check"></i>' : '1'; ?></div>
                <div class="step-label">Cấu hình cơ sở dữ liệu</div>
            </div>
            <div class="step-item <?php echo ($step >= 2) ? 'active' : ''; ?> <?php echo ($step > 2) ? 'completed' : ''; ?>">
                <div class="step-number"><?php echo ($step > 2) ? '<i class="fas fa-check"></i>' : '2'; ?></div>
                <div class="step-label">Cài đặt dữ liệu</div>
            </div>
            <div class="step-item <?php echo ($step >= 3) ? 'active' : ''; ?>">
                <div class="step-number"><?php echo ($step > 3) ? '<i class="fas fa-check"></i>' : '3'; ?></div>
                <div class="step-label">Hoàn thành</div>
            </div>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($step === 1): ?>
            <div class="step-content">
                <h3 class="mb-4">Bước 1: Cấu hình cơ sở dữ liệu</h3>
                <p>Nhập thông tin kết nối cơ sở dữ liệu MySQL của bạn:</p>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="db_host" class="form-label">Máy chủ cơ sở dữ liệu</label>
                        <input type="text" class="form-control" id="db_host" name="db_host" value="<?php echo htmlspecialchars($db_host); ?>" required>
                        <div class="form-text">Thường là "localhost" cho XAMPP</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="db_user" class="form-label">Tên người dùng cơ sở dữ liệu</label>
                        <input type="text" class="form-control" id="db_user" name="db_user" value="<?php echo htmlspecialchars($db_user); ?>" required>
                        <div class="form-text">Mặc định là "root" cho XAMPP</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="db_pass" class="form-label">Mật khẩu cơ sở dữ liệu</label>
                        <input type="password" class="form-control" id="db_pass" name="db_pass" value="<?php echo htmlspecialchars($db_pass); ?>">
                        <div class="form-text">Thường để trống cho XAMPP</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="db_name" class="form-label">Tên cơ sở dữ liệu</label>
                        <input type="text" class="form-control" id="db_name" name="db_name" value="<?php echo htmlspecialchars($db_name); ?>" required>
                        <div class="form-text">Cơ sở dữ liệu sẽ được tạo nếu chưa tồn tại</div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-database"></i> Kiểm tra kết nối
                        </button>
                    </div>
                </form>
            </div>
        <?php elseif ($step === 2): ?>
            <div class="step-content">
                <h3 class="mb-4">Bước 2: Cài đặt dữ liệu</h3>
                <p>Cơ sở dữ liệu đã được kết nối thành công. Bây giờ chúng ta sẽ cài đặt các bảng và dữ liệu mẫu.</p>
                
                <div class="alert alert-info">
                    <strong>Thông tin kết nối:</strong><br>
                    Máy chủ: <?php echo htmlspecialchars($db_host); ?><br>
                    Tên người dùng: <?php echo htmlspecialchars($db_user); ?><br>
                    Cơ sở dữ liệu: <?php echo htmlspecialchars($db_name); ?>
                </div>
                
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> Cảnh báo: Quá trình này sẽ xóa tất cả dữ liệu hiện có trong cơ sở dữ liệu <strong><?php echo htmlspecialchars($db_name); ?></strong> và thay thế bằng cấu trúc mới.
                </div>
                
                <form method="POST" action="">
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="confirm" required>
                        <label class="form-check-label" for="confirm">Tôi hiểu và muốn tiếp tục</label>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <input type="hidden" name="install" value="1">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-cogs"></i> Cài đặt cơ sở dữ liệu
                        </button>
                    </div>
                </form>
            </div>
        <?php elseif ($step === 3): ?>
            <div class="step-content text-center">
                <h3 class="mb-4">Bước 3: Hoàn thành cài đặt</h3>
                
                <div class="success-animation">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h4>Cài đặt thành công!</h4>
                </div>
                
                <div class="alert alert-success">
                    <p>Math Friends đã được cài đặt thành công trên máy chủ của bạn.</p>
                    <p>Bạn có thể đăng nhập với tài khoản mặc định:</p>
                    <strong>Tên đăng nhập:</strong> admin<br>
                    <strong>Mật khẩu:</strong> admin123
                </div>
                
                <div class="alert alert-warning">
                    <strong>Quan trọng:</strong> Vì lý do bảo mật, hãy xóa tệp <code>install.php</code> sau khi cài đặt thành công.
                </div>
                
                <form method="POST" action="">
                    <div class="d-grid gap-2">
                        <input type="hidden" name="finish" value="1">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-home"></i> Truy cập trang chủ
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>