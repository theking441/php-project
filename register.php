<?php
// Trang đăng ký
require_once 'includes/config.php';

// Kiểm tra nếu đã đăng nhập
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Xử lý đăng ký
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy và làm sạch dữ liệu đầu vào
    $username = clean_input($_POST['username']);
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = clean_input($_POST['first_name']);
    $last_name = clean_input($_POST['last_name']);
    $grade = intval($_POST['grade']);
    $school_name = clean_input($_POST['school_name']);
    $parent_phone = clean_input($_POST['parent_phone']);
    
    // Kiểm tra thông tin
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($grade)) {
        $error = 'Vui lòng điền đầy đủ thông tin bắt buộc';
    } elseif ($password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự';
    } elseif ($grade < 1 || $grade > 5) {
        $error = 'Vui lòng chọn lớp từ 1 đến 5';
    } else {
        // Kiểm tra tên đăng nhập đã tồn tại chưa
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = 'Tên đăng nhập đã tồn tại, vui lòng chọn tên khác';
        } else {
            // Kiểm tra email đã tồn tại chưa
            $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $error = 'Email đã được sử dụng, vui lòng sử dụng email khác';
            } else {
                // Tạo mật khẩu băm
                $password_hash = create_password_hash($password);
                
                // Xử lý hình ảnh đại diện (nếu có)
                $profile_image = 'default_avatar.png'; // Mặc định
                
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = 'images/avatars/';
                    
                    // Tạo thư mục nếu chưa tồn tại
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Tạo tên file duy nhất
                    $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                    $file_name = $username . '_' . time() . '.' . $file_extension;
                    $target_file = $upload_dir . $file_name;
                    
                    // Kiểm tra loại file
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                    if (in_array(strtolower($file_extension), $allowed_types)) {
                        // Di chuyển file vào thư mục
                        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                            $profile_image = 'avatars/' . $file_name;
                        }
                    }
                }
                
                // Thêm người dùng vào cơ sở dữ liệu
                $stmt = mysqli_prepare($conn, "INSERT INTO users (username, email, password, first_name, last_name, grade, school_name, parent_phone, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "sssssssss", $username, $email, $password_hash, $first_name, $last_name, $grade, $school_name, $parent_phone, $profile_image);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Đăng ký thành công
                    $success = 'Đăng ký thành công! Bạn có thể đăng nhập ngay bây giờ.';
                } else {
                    $error = 'Có lỗi xảy ra khi đăng ký. Vui lòng thử lại sau.';
                }
            }
        }
    }
}

// Bao gồm header
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header text-center">
                <h2>Đăng ký tài khoản</h2>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                    <div class="text-center mb-4">
                        <a href="login.php" class="btn btn-primary">Đăng nhập ngay</a>
                    </div>
                <?php else: ?>
                    <form id="registerForm" method="POST" action="" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Tên đăng nhập *</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">Mật khẩu *</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div class="form-text">Mật khẩu phải có ít nhất 6 ký tự</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Xác nhận mật khẩu *</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">Tên</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Họ</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="grade" class="form-label">Lớp *</label>
                                    <select class="form-select" id="grade" name="grade" required>
                                        <option value="">Chọn lớp</option>
                                        <option value="1">Lớp 1</option>
                                        <option value="2">Lớp 2</option>
                                        <option value="3">Lớp 3</option>
                                        <option value="4">Lớp 4</option>
                                        <option value="5">Lớp 5</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="school_name" class="form-label">Trường học</label>
                                    <input type="text" class="form-control" id="school_name" name="school_name">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="parent_phone" class="form-label">Số điện thoại phụ huynh</label>
                                    <input type="tel" class="form-control" id="parent_phone" name="parent_phone">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="profile_image" class="form-label">Ảnh đại diện</label>
                            <div class="input-group">
                                <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*" onchange="updateProfilePicture(this)">
                                <label class="input-group-text" for="profile_image"><i class="fas fa-upload"></i></label>
                            </div>
                            <div class="form-text">Chọn ảnh đại diện (không bắt buộc)</div>
                        </div>
                        
                        <div class="text-center mb-3">
                            <img id="profile-preview" src="images/default_avatar.png" class="avatar-img" alt="Ảnh đại diện" style="width: 100px; height: 100px;">
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" required>
                            <label class="form-check-label" for="terms">Tôi đồng ý với <a href="#">Điều khoản sử dụng</a> và <a href="#">Chính sách bảo mật</a> *</label>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Đăng ký</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
            <div class="card-footer text-center">
                <p class="mb-0">Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
            </div>
        </div>
    </div>
</div>

<?php
// Bao gồm footer
include 'includes/footer.php';
?>