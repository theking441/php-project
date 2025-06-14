<?php
// Trang danh sách bài kiểm tra
require_once 'includes/config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Vui lòng đăng nhập để xem bài kiểm tra';
    $_SESSION['message_type'] = 'warning';
    header('Location: login.php');
    exit;
}

// Lấy lớp của người dùng
$user_id = $_SESSION['user_id'];
$user_grade = $_SESSION['grade'] ?? 1;

// Lọc theo lớp
$grade_filter = isset($_GET['grade']) ? intval($_GET['grade']) : $user_grade;

// Lấy các bài kiểm tra phù hợp với lớp đã chọn
$stmt = mysqli_prepare($conn, "
    SELECT t.*, 
           (SELECT COUNT(*) FROM test_questions WHERE test_id = t.id) as question_count,
           CASE 
               WHEN EXISTS (SELECT 1 FROM test_results WHERE test_id = t.id AND user_id = ?) 
               THEN 1 ELSE 0 
           END as is_completed
    FROM tests t
    WHERE t.grade_level = ?
    ORDER BY t.id ASC
");
mysqli_stmt_bind_param($stmt, "ii", $user_id, $grade_filter);
mysqli_stmt_execute($stmt);
$tests_result = mysqli_stmt_get_result($stmt);

// Bao gồm header
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1>Bài kiểm tra toán</h1>
        <p class="lead">Chọn một bài kiểm tra để bắt đầu làm bài và kiểm tra kiến thức của bạn!</p>
    </div>
    <div class="col-md-4 text-end">
        <div class="btn-group" role="group">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <a href="tests.php?grade=<?php echo $i; ?>" class="btn <?php echo ($grade_filter == $i) ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    Lớp <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>
</div>

<div class="row">
    <?php if (mysqli_num_rows($tests_result) > 0): ?>
        <?php while ($test = mysqli_fetch_assoc($tests_result)): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0"><?php echo htmlspecialchars($test['title']); ?></h3>
                        <?php if ($test['is_completed']): ?>
                            <span class="badge bg-success">Đã hoàn thành</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <span class="badge bg-info">Lớp <?php echo $test['grade_level']; ?></span>
                            <span class="badge bg-secondary"><?php echo $test['time_limit']; ?> phút</span>
                            <span class="badge bg-primary"><?php echo $test['question_count']; ?> câu hỏi</span>
                        </div>
                        
                        <p class="card-text"><?php echo htmlspecialchars($test['description']); ?></p>
                        
                        <?php if ($test['is_completed']): ?>
                            <?php
                            // Lấy kết quả bài kiểm tra gần nhất
                            $stmt = mysqli_prepare($conn, "
                                SELECT id, score, completed_at
                                FROM test_results 
                                WHERE test_id = ? AND user_id = ?
                                ORDER BY completed_at DESC
                                LIMIT 1
                            ");
                            mysqli_stmt_bind_param($stmt, "ii", $test['id'], $user_id);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                            $result_data = mysqli_fetch_assoc($result);
                            ?>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <p class="mb-0"><strong>Điểm gần nhất:</strong> 
                                        <span class="badge <?php echo ($result_data['score'] >= 8) ? 'bg-success' : (($result_data['score'] >= 6) ? 'bg-warning' : 'bg-danger'); ?>">
                                            <?php echo $result_data['score']; ?>/10
                                        </span>
                                    </p>
                                    <p class="mb-0 small text-muted"><?php echo date('d/m/Y H:i', strtotime($result_data['completed_at'])); ?></p>
                                </div>
                                <a href="results.php?id=<?php echo $result_data['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    Xem kết quả
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <div class="d-grid gap-2">
                            <a href="test.php?id=<?php echo $test['id']; ?>" class="btn btn-primary">
                                <?php echo $test['is_completed'] ? 'Làm lại bài kiểm tra' : 'Bắt đầu làm bài'; ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-md-12">
            <div class="alert alert-info">
                <p class="mb-0">Hiện tại không có bài kiểm tra nào cho lớp <?php echo $grade_filter; ?>.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// Bao gồm footer
include 'includes/footer.php';
?>