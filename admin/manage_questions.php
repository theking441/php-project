<?php
// Trang quản lý câu hỏi
require_once '../includes/config.php';

// Kiểm tra đăng nhập và quyền quản trị
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    $_SESSION['message'] = 'Bạn không có quyền truy cập trang quản trị';
    $_SESSION['message_type'] = 'danger';
    header('Location: ../index.php');
    exit;
}

// Xử lý xóa câu hỏi
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $question_id = intval($_GET['id']);
    
    // Kiểm tra xem câu hỏi có đang được sử dụng trong bài kiểm tra nào không
    $check_query = "SELECT COUNT(*) as count FROM test_questions WHERE question_id = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "i", $question_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $usage_data = mysqli_fetch_assoc($result);
    
    if ($usage_data['count'] > 0) {
        $_SESSION['message'] = 'Không thể xóa câu hỏi này vì nó đang được sử dụng trong một hoặc nhiều bài kiểm tra.';
        $_SESSION['message_type'] = 'danger';
    } else {
        $delete_query = "DELETE FROM math_questions WHERE id = ?";
        $stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt, "i", $question_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = 'Đã xóa câu hỏi thành công.';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Không thể xóa câu hỏi: ' . mysqli_error($conn);
            $_SESSION['message_type'] = 'danger';
        }
    }
    
    header('Location: manage_questions.php');
    exit;
}

// Thiết lập phân trang
$records_per_page = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

// Thiết lập bộ lọc
$grade_filter = isset($_GET['grade']) ? intval($_GET['grade']) : 0;
$topic_filter = isset($_GET['topic']) ? clean_input($_GET['topic']) : '';
$difficulty_filter = isset($_GET['difficulty']) ? clean_input($_GET['difficulty']) : '';

// Xây dựng câu lệnh truy vấn với bộ lọc
$where_clauses = [];
$params = [];
$types = "";

if ($grade_filter > 0) {
    $where_clauses[] = "grade_level = ?";
    $params[] = $grade_filter;
    $types .= "i";
}

if (!empty($topic_filter)) {
    $where_clauses[] = "topic = ?";
    $params[] = $topic_filter;
    $types .= "s";
}

if (!empty($difficulty_filter)) {
    $where_clauses[] = "difficulty = ?";
    $params[] = $difficulty_filter;
    $types .= "s";
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Đếm tổng số bản ghi
$count_query = "SELECT COUNT(*) as total FROM math_questions $where_sql";
$stmt = mysqli_prepare($conn, $count_query);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$count_result = mysqli_stmt_get_result($stmt);
$count_data = mysqli_fetch_assoc($count_result);
$total_records = $count_data['total'];
$total_pages = ceil($total_records / $records_per_page);

// Lấy dữ liệu câu hỏi
$query = "SELECT * FROM math_questions $where_sql ORDER BY id DESC LIMIT ?, ?";
$stmt = mysqli_prepare($conn, $query);

if (!empty($params)) {
    $params[] = $offset;
    $params[] = $records_per_page;
    mysqli_stmt_bind_param($stmt, $types . "ii", ...$params);
} else {
    mysqli_stmt_bind_param($stmt, "ii", $offset, $records_per_page);
}

mysqli_stmt_execute($stmt);
$questions_result = mysqli_stmt_get_result($stmt);

// Lấy danh sách chủ đề để hiển thị trong bộ lọc
$topics_query = "SELECT DISTINCT topic FROM math_questions ORDER BY topic";
$topics_result = mysqli_query($conn, $topics_query);

// Bao gồm header quản trị
include 'includes/admin_header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1>Quản lý câu hỏi</h1>
        <p class="lead">Thêm, sửa, và xóa câu hỏi toán học.</p>
    </div>
    <div class="col-md-6 text-end">
        <a href="add_question.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Thêm câu hỏi mới
        </a>
    </div>
</div>

<!-- Bộ lọc -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-filter"></i> Bộ lọc
        </h5>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-3">
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
            
            <div class="col-md-3">
                <label for="topic" class="form-label">Chủ đề</label>
                <select class="form-select" id="topic" name="topic">
                    <option value="">Tất cả chủ đề</option>
                    <?php while ($topic = mysqli_fetch_assoc($topics_result)): ?>
                        <option value="<?php echo htmlspecialchars($topic['topic']); ?>" <?php echo ($topic_filter === $topic['topic']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($topic['topic']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="difficulty" class="form-label">Độ khó</label>
                <select class="form-select" id="difficulty" name="difficulty">
                    <option value="">Tất cả độ khó</option>
                    <option value="easy" <?php echo ($difficulty_filter === 'easy') ? 'selected' : ''; ?>>Dễ</option>
                    <option value="medium" <?php echo ($difficulty_filter === 'medium') ? 'selected' : ''; ?>>Trung bình</option>
                    <option value="hard" <?php echo ($difficulty_filter === 'hard') ? 'selected' : ''; ?>>Khó</option>
                </select>
            </div>
            
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-search"></i> Lọc
                </button>
                <a href="manage_questions.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Đặt lại
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Danh sách câu hỏi -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Danh sách câu hỏi (<?php echo $total_records; ?> câu hỏi)</h5>
    </div>
    <div class="card-body">
        <?php if (mysqli_num_rows($questions_result) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Câu hỏi</th>
                            <th>Lớp</th>
                            <th>Chủ đề</th>
                            <th>Độ khó</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($question = mysqli_fetch_assoc($questions_result)): ?>
                            <tr>
                                <td><?php echo $question['id']; ?></td>
                                <td>
                                    <?php 
                                        $question_text = $question['question_text'];
                                        echo (strlen($question_text) > 100) ? htmlspecialchars(substr($question_text, 0, 100) . '...') : htmlspecialchars($question_text);
                                    ?>
                                </td>
                                <td>Lớp <?php echo $question['grade_level']; ?></td>
                                <td><?php echo htmlspecialchars($question['topic']); ?></td>
                                <td>
                                    <span class="badge <?php 
                                        echo ($question['difficulty'] === 'easy') ? 'bg-success' : 
                                            (($question['difficulty'] === 'medium') ? 'bg-warning' : 'bg-danger'); 
                                    ?>">
                                        <?php 
                                            echo ($question['difficulty'] === 'easy') ? 'Dễ' : 
                                                (($question['difficulty'] === 'medium') ? 'Trung bình' : 'Khó'); 
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="view_question.php?id=<?php echo $question['id']; ?>" class="btn btn-sm btn-info" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_question.php?id=<?php echo $question['id']; ?>" class="btn btn-sm btn-primary" title="Chỉnh sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $question['id']; ?>)" class="btn btn-sm btn-danger" title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </a>
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
                                <a class="page-link" href="?page=1<?php echo !empty($grade_filter) ? '&grade=' . $grade_filter : ''; ?><?php echo !empty($topic_filter) ? '&topic=' . urlencode($topic_filter) : ''; ?><?php echo !empty($difficulty_filter) ? '&difficulty=' . urlencode($difficulty_filter) : ''; ?>">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($grade_filter) ? '&grade=' . $grade_filter : ''; ?><?php echo !empty($topic_filter) ? '&topic=' . urlencode($topic_filter) : ''; ?><?php echo !empty($difficulty_filter) ? '&difficulty=' . urlencode($difficulty_filter) : ''; ?>">
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
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($grade_filter) ? '&grade=' . $grade_filter : ''; ?><?php echo !empty($topic_filter) ? '&topic=' . urlencode($topic_filter) : ''; ?><?php echo !empty($difficulty_filter) ? '&difficulty=' . urlencode($difficulty_filter) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($grade_filter) ? '&grade=' . $grade_filter : ''; ?><?php echo !empty($topic_filter) ? '&topic=' . urlencode($topic_filter) : ''; ?><?php echo !empty($difficulty_filter) ? '&difficulty=' . urlencode($difficulty_filter) : ''; ?>">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo !empty($grade_filter) ? '&grade=' . $grade_filter : ''; ?><?php echo !empty($topic_filter) ? '&topic=' . urlencode($topic_filter) : ''; ?><?php echo !empty($difficulty_filter) ? '&difficulty=' . urlencode($difficulty_filter) : ''; ?>">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center py-4">
                <img src="../images/empty_questions.svg" alt="Không có câu hỏi" height="120" class="mb-3">
                <p>Không tìm thấy câu hỏi nào phù hợp với bộ lọc.</p>
                <a href="add_question.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Thêm câu hỏi mới
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
                <p>Bạn có chắc chắn muốn xóa câu hỏi này không? Hành động này không thể hoàn tác.</p>
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
        document.getElementById('confirmDeleteBtn').href = 'manage_questions.php?action=delete&id=' + id;
        modal.show();
    }
</script>

<?php
// Bao gồm footer quản trị
include 'includes/admin_footer.php';
?>