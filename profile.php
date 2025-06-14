<?php
// Trang quản lý hồ sơ cá nhân
require_once 'includes/config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Vui lòng đăng nhập để xem trang hồ sơ';
    $_SESSION['message_type'] = 'warning';
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Lấy thông tin người dùng hiện tại
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Xử lý cập nhật thông tin cá nhân
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Lấy và làm sạch dữ liệu đầu vào
    $first_name = clean_input($_POST['first_name']);
    $last_name = clean_input($_POST['last_name']);
    $grade = intval($_POST['grade']);
    $school_name = clean_input($_POST['school_name']);
    $parent_phone = clean_input($_POST['parent_phone']);
    
    // Kiểm tra dữ liệu
    if ($grade < 1 || $grade > 5) {
        $error = 'Vui lòng chọn lớp từ 1 đến 5';
    } else {
        // Xử lý tải lên ảnh đại diện (nếu có)
        $profile_image = $user['profile_image']; // Giữ nguyên ảnh cũ nếu không có ảnh mới
        
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'images/avatars/';
            
            // Tạo thư mục nếu chưa tồn tại
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Tạo tên file duy nhất
            $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $file_name = $user['username'] . '_' . time() . '.' . $file_extension;
            $target_file = $upload_dir . $file_name;
            
            // Kiểm tra loại file
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array(strtolower($file_extension), $allowed_types)) {
                // Di chuyển file vào thư mục
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                    $profile_image = 'avatars/' . $file_name;
                } else {
                    $error = 'Không thể tải lên ảnh đại diện. Vui lòng thử lại.';
                }
            } else {
                $error = 'Chỉ chấp nhận file hình ảnh định dạng JPG, JPEG, PNG, hoặc GIF.';
            }
        }
        
        if (empty($error)) {
            // Cập nhật thông tin người dùng
            $stmt = mysqli_prepare($conn, "UPDATE users SET first_name = ?, last_name = ?, grade = ?, school_name = ?, parent_phone = ?, profile_image = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ssisssi", $first_name, $last_name, $grade, $school_name, $parent_phone, $profile_image, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Cập nhật thông tin cá nhân thành công!';
                
                // Cập nhật thông tin session
                $_SESSION['first_name'] = $first_name;
                $_SESSION['grade'] = $grade;
                $_SESSION['profile_image'] = $profile_image;
                
                // Tải lại thông tin người dùng
                $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $user = mysqli_fetch_assoc($result);
            } else {
                $error = 'Có lỗi xảy ra khi cập nhật thông tin. Vui lòng thử lại.';
            }
        }
    }
}

// Xử lý cập nhật mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Kiểm tra mật khẩu hiện tại
    if (verify_password($current_password, $user['password'])) {
        // Kiểm tra mật khẩu mới
        if (strlen($new_password) < 6) {
            $error = 'Mật khẩu mới phải có ít nhất 6 ký tự';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Mật khẩu xác nhận không khớp';
        } else {
            // Tạo mật khẩu băm mới
            $password_hash = create_password_hash($new_password);
            
            // Cập nhật mật khẩu
            $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $password_hash, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Đã cập nhật mật khẩu thành công!';
            } else {
                $error = 'Có lỗi xảy ra khi cập nhật mật khẩu. Vui lòng thử lại.';
            }
        }
    } else {
        $error = 'Mật khẩu hiện tại không đúng';
    }
}

// Lấy số lượng huy hiệu
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as badge_count FROM user_badges WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$badge_result = mysqli_stmt_get_result($stmt);
$badge_count = mysqli_fetch_assoc($badge_result)['badge_count'];

// Lấy số bài kiểm tra đã hoàn thành
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as test_count FROM test_results WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$test_result = mysqli_stmt_get_result($stmt);
$test_count = mysqli_fetch_assoc($test_result)['test_count'];

// Bao gồm header
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h1>Hồ sơ cá nhân</h1>
        <p class="lead">Quản lý thông tin cá nhân và theo dõi tiến độ học tập của bạn.</p>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div class="row">
    <!-- Thông tin cá nhân -->
    <div class="col-md-4 mb-4">
        <div class="card profile-card">
            <div class="card-header">
                <h3>Thông tin cá nhân</h3>
            </div>
            <div class="card-body text-center">
                <div class="profile-image-container mb-3">
                    <img src="images/<?php echo $user['profile_image']; ?>" class="profile-image" alt="Ảnh đại diện" onerror="this.src='https://via.placeholder.com/150x150?text=User'">
                </div>
                <h4><?php echo !empty($user['first_name']) ? htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) : htmlspecialchars($user['username']); ?></h4>
                <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                
                <div class="profile-stats">
                    <div class="row">
                        <div class="col-6">
                            <div class="profile-stat">
                                <div class="profile-stat-value"><?php echo $test_count; ?></div>
                                <div class="profile-stat-label">Bài kiểm tra</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="profile-stat">
                                <div class="profile-stat-value"><?php echo $badge_count; ?></div>
                                <div class="profile-stat-label">Huy hiệu</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="profile-details mt-3">
                    <div class="profile-detail-item">
                        <span class="profile-detail-label">Lớp:</span>
                        <span class="profile-detail-value">Lớp <?php echo $user['grade']; ?></span>
                    </div>
                    <?php if (!empty($user['school_name'])): ?>
                    <div class="profile-detail-item">
                        <span class="profile-detail-label">Trường:</span>
                        <span class="profile-detail-value"><?php echo htmlspecialchars($user['school_name']); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="profile-detail-item">
                        <span class="profile-detail-label">Ngày tham gia:</span>
                        <span class="profile-detail-value"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Cập nhật thông tin -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h3>Cập nhật thông tin</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">Tên</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Họ</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                            <div class="form-text">Email không thể thay đổi.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="grade" class="form-label">Lớp</label>
                            <select class="form-select" id="grade" name="grade" required>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($user['grade'] == $i) ? 'selected' : ''; ?>>
                                        Lớp <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="school_name" class="form-label">Trường học</label>
                        <input type="text" class="form-control" id="school_name" name="school_name" value="<?php echo htmlspecialchars($user['school_name']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="parent_phone" class="form-label">Số điện thoại phụ huynh</label>
                        <input type="tel" class="form-control" id="parent_phone" name="parent_phone" value="<?php echo htmlspecialchars($user['parent_phone']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="profile_image" class="form-label">Ảnh đại diện</label>
                        <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*" onchange="updateProfilePicture(this)">
                        <div class="form-text">Chọn ảnh đại diện mới (không bắt buộc).</div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Cập nhật thông tin
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Đổi mật khẩu -->
        <div class="card">
            <div class="card-header">
                <h3>Đổi mật khẩu</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Mật khẩu hiện tại</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Mật khẩu mới</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <div class="form-text">Mật khẩu phải có ít nhất 6 ký tự.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Xác nhận mật khẩu mới</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" name="change_password" class="btn btn-warning">
                            <i class="fas fa-key"></i> Đổi mật khẩu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.profile-card {
    margin-bottom: 1.5rem;
}

.profile-image-container {
    position: relative;
    width: 150px;
    height: 150px;
    margin: 0 auto;
}

.profile-image {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #8A4FFF;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.profile-stats {
    margin: 1.5rem 0;
    padding: 1rem 0;
    border-top: 1px solid #e1e5ea;
    border-bottom: 1px solid #e1e5ea;
}

.profile-stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #8A4FFF;
}

.profile-stat-label {
    font-size: 0.9rem;
    color: #6c757d;
}

.profile-details {
    text-align: left;
}

.profile-detail-item {
    margin-bottom: 0.5rem;
}

.profile-detail-label {
    font-weight: bold;
    color: #6c757d;
    margin-right: 0.5rem;
}

.profile-detail-value {
    color: #333;
}
</style>

<?php
// Bao gồm footer
include 'includes/footer.php';
?>