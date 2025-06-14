<?php
// Trang quản lý bài kiểm tra
require_once '../includes/config.php';

// Kiểm tra đăng nhập và quyền quản trị
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    $_SESSION['message'] = 'Bạn không có quyền truy cập trang quản trị';
    $_SESSION['message_type'] = 'danger';
    header('Location: ../index.php');
    exit;
}

// Xử lý xóa bài kiểm tra
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $test_id = intval($_GET['id']);
    
    // Kiểm tra xem bài kiểm tra có đang được sử dụng không
    $check_query = "SELECT COUNT(*) as count FROM test_results WHERE test_id = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "i", $test_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $usage_data = mysqli_fetch_assoc($result);
    
    if ($usage_data['count'] > 0) {
        $_SESSION['message'] = 'Không thể xóa bài kiểm tra này vì nó đã được sử dụng bởi học sinh.';
        $_SESSION['message_type'] = 'danger';
    } else {
        // Xóa câu hỏi trong bài kiểm tra trước
        $delete_questions = "DELETE FROM test_questions WHERE test_id = ?";
        $stmt = mysqli_prepare($conn, $delete_questions);
        mysqli_stmt_bind_param($stmt, "i", $test_id);
        mysqli_stmt_execute($stmt);
        
        // Sau đó xóa bài kiểm tra
        $delete_test = "DELETE FROM tests WHERE id = ?";
        $stmt = mysqli_prepare($conn, $delete_test);
        mysqli_stmt_bind_param($stmt, "i", $test_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = 'Đã xóa bài kiểm tra thành công.';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Không thể xóa bài kiểm tra: ' . mysqli_error($conn);
            $_SESSION['message_type'] = 'danger';
        }
    }
    
    header('Location: manage_tests.php');
    exit;
}

// Thiết lập phân trang
$records_per_page = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

// Thiết lập bộ lọc
$grade_filter = isset($_GET['grade']) ? intval($_GET['grade']) : 0;

// Xây dựng câu lệnh truy vấn với bộ lọc
$where_clause = '';
$params = [];
$types = '';

if ($grade_filter > 0) {
    $where_clause = 'WHERE grade_level = ?';
    $params[] = $grade_filter;
    $types = 'i';
}

// Đếm tổng số bản ghi
$count_query = "SELECT COUNT(*) as total FROM tests $where_clause";
$stmt = mysqli_prepare($conn, $count_query);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$count_result = mysqli_stmt_get_result($stmt);
$count_data = mysqli_fetch_assoc($count_result);
$total_records = $count_data['total'];
$total_pages = ceil($total_records / $records_per_page);

// Lấy dữ liệu bài kiểm tra
$query = "
    SELECT t.*,
           (SELECT COUNT(*) FROM test_questions WHERE test_id = t.id) as question_count,
           (SELECT COUNT(*) FROM test_results WHERE test_id = t.id) as result_count
    FROM tests t
    $where_clause
    ORDER BY t.id DESC
    LIMIT ?, ?
";

$stmt = mysqli_prepare($conn, $query);

if (!empty($params)) {
    $params[] = $offset;
    $params[] = $records_per_page;
    mysqli_stmt_bind_param($stmt, $types . 'ii', ...$params);
} else {
    mysqli_stmt_bind_param($stmt, 'ii', $offset, $records_per_page);
}

mysqli_stmt_execute($stmt);
$tests_result = mysqli_stmt_get_result($stmt);

// Bao gồm header quản trị
include 'includes/admin_header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1>Quản lý bài kiểm tra</h1>
        <p class="lead">Tạo, chỉnh sửa và quản lý các bài kiểm tra toán học.</p>
    </div>
    <div class="col-md-6 text-end">
        <a href="add_test.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tạo bài kiểm tra mới
        </a>
    </div>
</div>

<!-- Bộ lọc -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-filter"></i> Lọc bài kiểm tra
        </h5>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row">
            <div class="col-md-4">
                <label for="grade" class="form-label">Lớp</label>
                <select class="form-select" id="grade" name="grade">
                    <option value="0">Tất cả các lớp</option>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($grade_filter === $i) ? 'selected' : ''; ?>>
                            Lớp <?php echo $i; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-search"></i> Lọc
                </button>
                <a href="manage_tests.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Đặt lại
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Danh sách bài kiểm tra -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Danh sách bài kiểm tra (<?php echo $total_records; ?> bài kiểm tra)</h5>
    </div>
    <div class="card-body">
        <?php if (mysqli_num_rows($tests_result) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tiêu đề</th>
                            <th>Lớp</th>
                            <th>Thời gian</th>
                            <th>Số câu hỏi</th>
                            <th>Lượt làm</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($test = mysqli_fetch_assoc($tests_result)): ?>
                            <tr>
                                <td><?php echo $test['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($test['title']); ?></strong>
                                    <?php if (!empty($test['description'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($test['description'], 0, 50) . (strlen($test['description']) > 50 ? '...' : '')); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>Lớp <?php echo $test['grade_level']; ?></td>
                                <td><?php echo $test['time_limit']; ?> phút</td>
                                <td>
                                    <span class="badge bg-info"><?php echo $test['question_count']; ?> câu</span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo $test['result_count']; ?> lượt</span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="view_test.php?id=<?php echo $test['id']; ?>" class="btn btn-sm btn-info" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_test.php?id=<?php echo $test['id']; ?>" class="btn btn-sm btn-primary" title="Chỉnh sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="manage_test_questions.php?test_id=<?php echo $test['id']; ?>" class="btn btn-sm btn-warning" title="Quản lý câu hỏi">
                                            <i class="fas fa-list-ol"></i>
                                        </a>
                                        <?php if ($test['result_count'] == 0): ?>
                                            <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $test['id']; ?>)" class="btn btn-sm btn-danger" title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Phân trang -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center mt-4">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1<?php echo !empty($grade_filter) ? '&grade=' . $grade_filter : ''; ?>">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($grade_filter) ? '&grade=' . $grade_filter : ''; ?>">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++): 
                        ?>
                            <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($grade_filter) ? '&grade=' . $grade_filter : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($grade_filter) ? '&grade=' . $grade_filter : ''; ?>">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo !empty($grade_filter) ? '&grade=' . $grade_filter : ''; ?>">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center py-4">
                <img src="../images/empty_tests.svg" alt="Không có bài kiểm tra" height="120" class="mb-3" onerror="this.src='https://via.placeholder.com/120x120?text=No+Tests'">
                <p>Chưa có bài kiểm tra nào<?php echo $grade_filter > 0 ? ' cho lớp ' . $grade_filter : ''; ?>.</p>
                <a href="add_test.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Tạo bài kiểm tra mới
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal xác nhận xóa -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận xóa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa bài kiểm tra này không? Hành động này không thể hoàn tác.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Xóa</a>
            </div>
        </div>
    </div>
</div>

<script>
    // Hàm xác nhận xóa
    function confirmDelete(id) {
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        document.getElementById('confirmDeleteBtn').href = 'manage_tests.php?action=delete&id=' + id;
        modal.show();
    }
</script>

<?php
// Bao gồm footer quản trị
include 'includes/admin_footer.php';
?>