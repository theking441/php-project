<?php
// Trang thêm bài kiểm tra mới
require_once '../includes/config.php';

// Kiểm tra đăng nhập và quyền quản trị
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    $_SESSION['message'] = 'Bạn không có quyền truy cập trang quản trị';
    $_SESSION['message_type'] = 'danger';
    header('Location: ../index.php');
    exit;
}

// Xử lý thêm bài kiểm tra mới
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy và làm sạch dữ liệu đầu vào
    $title = clean_input($_POST['title']);
    $description = clean_input($_POST['description']);
    $grade_level = intval($_POST['grade_level']);
    $time_limit = intval($_POST['time_limit']);
    
    // Kiểm tra dữ liệu
    if (empty($title) || empty($grade_level) || empty($time_limit)) {
        $error = 'Vui lòng điền đầy đủ thông tin bắt buộc.';
    } elseif ($grade_level < 1 || $grade_level > 5) {
        $error = 'Lớp phải từ 1 đến 5.';
    } elseif ($time_limit < 1 || $time_limit > 60) {
        $error = 'Thời gian phải từ 1 đến 60 phút.';
    } else {
        // Thêm bài kiểm tra vào cơ sở dữ liệu
        $stmt = mysqli_prepare($conn, "INSERT INTO tests (title, description, grade_level, time_limit) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssii", $title, $description, $grade_level, $time_limit);
        
        if (mysqli_stmt_execute($stmt)) {
            $test_id = mysqli_insert_id($conn);
            $_SESSION['message'] = 'Đã tạo bài kiểm tra mới thành công!';
            $_SESSION['message_type'] = 'success';
            
            // Chuyển hướng đến trang quản lý câu hỏi của bài kiểm tra
            header('Location: manage_test_questions.php?test_id=' . $test_id);
            exit;
        } else {
            $error = 'Có lỗi xảy ra khi thêm bài kiểm tra: ' . mysqli_error($conn);
        }
    }
}

// Bao gồm header quản trị
include 'includes/admin_header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1>Tạo bài kiểm tra mới</h1>
        <p class="lead">Tạo bài kiểm tra toán học mới cho học sinh.</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="manage_tests.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại danh sách
        </a>
    </div>
</div>

<div class="card form-card">
    <div class="card-header">
        <h5 class="mb-0">Thông tin bài kiểm tra</h5>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label for="title" class="form-label required-field">Tiêu đề bài kiểm tra</label>
                <input type="text" class="form-control" id="title" name="title" required value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Mô tả</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                <div class="form-text">Mô tả ngắn gọn về bài kiểm tra (không bắt buộc).</div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="grade_level" class="form-label required-field">Lớp</label>
                    <select class="form-select" id="grade_level" name="grade_level" required>
                        <option value="">Chọn lớp</option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo (isset($_POST['grade_level']) && $_POST['grade_level'] == $i) ? 'selected' : ''; ?>>
                                Lớp <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="time_limit" class="form-label required-field">Thời gian làm bài (phút)</label>
                    <input type="number" class="form-control" id="time_limit" name="time_limit" min="1" max="60" required value="<?php echo isset($_POST['time_limit']) ? htmlspecialchars($_POST['time_limit']) : '10'; ?>">
                    <div class="form-text">Thời gian làm bài kiểm tra tính bằng phút (từ 1 đến 60 phút).</div>
                </div>
            </div>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Sau khi tạo bài kiểm tra, bạn sẽ được chuyển đến trang quản lý câu hỏi để thêm câu hỏi vào bài kiểm tra này.
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Tạo bài kiểm tra
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// Bao gồm footer quản trị
include 'includes/admin_footer.php';
?>