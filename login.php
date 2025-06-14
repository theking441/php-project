<?php
// Trang đăng nhập
require_once 'includes/config.php';

// Kiểm tra nếu đã đăng nhập
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Xử lý đăng nhập
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy và làm sạch dữ liệu đầu vào
    $username = clean_input($_POST['username']);
    $password = $_POST['password'];
    
    // Kiểm tra xác thực
    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập tên đăng nhập và mật khẩu';
    } else {
        // Tìm người dùng trong cơ sở dữ liệu
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Xác minh mật khẩu
            if (verify_password($password, $user['password'])) {
                // Đăng nhập thành công
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['grade'] = $user['grade'];
                $_SESSION['profile_image'] = $user['profile_image'];
                $_SESSION['is_admin'] = (bool)$user['is_admin'];
                
                // Chuyển hướng đến trang bảng điều khiển
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Mật khẩu không đúng';
            }
        } else {
            $error = 'Không tìm thấy tên đăng nhập';
        }
    }
}

// Bao gồm header
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card">
            <div class="card-header text-center">
                <h2>Đăng nhập</h2>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form id="loginForm" method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">Tên đăng nhập</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Nhập tên đăng nhập">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Nhập mật khẩu">
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember">
                        <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Đăng nhập</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <p class="mb-0">Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
            </div>
        </div>
        
        <!-- Hình ảnh minh họa -->
        <div class="text-center mt-4">
            <img src="images/login_character.svg" alt="Math Character" height="150" class="character">
        </div>
    </div>
</div>

<?php
// Bao gồm footer
include 'includes/footer.php';
?>