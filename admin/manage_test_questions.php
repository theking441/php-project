<?php
// Trang quản lý câu hỏi trong bài kiểm tra
require_once '../includes/config.php';

// Kiểm tra đăng nhập và quyền quản trị
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    $_SESSION['message'] = 'Bạn không có quyền truy cập trang quản trị';
    $_SESSION['message_type'] = 'danger';
    header('Location: ../index.php');
    exit;
}

// Kiểm tra ID bài kiểm tra
if (!isset($_GET['test_id']) || !is_numeric($_GET['test_id'])) {
    $_SESSION['message'] = 'Bài kiểm tra không hợp lệ';
    $_SESSION['message_type'] = 'danger';
    header('Location: manage_tests.php');
    exit;
}

$test_id = intval($_GET['test_id']);

// Lấy thông tin bài kiểm tra
$stmt = mysqli_prepare($conn, "SELECT * FROM tests WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $test_id);
mysqli_stmt_execute($stmt);
$test_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($test_result) === 0) {
    $_SESSION['message'] = 'Bài kiểm tra không tồn tại';
    $_SESSION['message_type'] = 'danger';
    header('Location: manage_tests.php');
    exit;
}

$test = mysqli_fetch_assoc($test_result);

// Xử lý thêm/xóa câu hỏi
$error = '';
$success = '';

// Xóa câu hỏi khỏi bài kiểm tra
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['question_id'])) {
    $question_id = intval($_GET['question_id']);
    
    $stmt = mysqli_prepare($conn, "DELETE FROM test_questions WHERE test_id = ? AND question_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $test_id, $question_id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Cập nhật lại thứ tự câu hỏi
        $update_order_query = "
            SET @row_number = 0;
            UPDATE test_questions 
            SET question_order = (@row_number:=@row_number + 1) 
            WHERE test_id = ? 
            ORDER BY question_order ASC;
        ";
        
        mysqli_multi_query($conn, $update_order_query);
        mysqli_next_result($conn); // Bỏ qua kết quả của câu lệnh SET
        
        $stmt = mysqli_prepare($conn, "UPDATE test_questions SET question_order = (@row_number:=@row_number + 1) WHERE test_id = ? ORDER BY question_order ASC");
        mysqli_stmt_bind_param($stmt, "i", $test_id);
        mysqli_stmt_execute($stmt);
        
        $_SESSION['message'] = 'Đã xóa câu hỏi khỏi bài kiểm tra.';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Không thể xóa câu hỏi: ' . mysqli_error($conn);
        $_SESSION['message_type'] = 'danger';
    }
    
    header('Location: manage_test_questions.php?test_id=' . $test_id);
    exit;
}

// Thêm câu hỏi vào bài kiểm tra
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_questions'])) {
    if (isset($_POST['question_ids']) && is_array($_POST['question_ids']) && !empty($_POST['question_ids'])) {
        $question_ids = array_map('intval', $_POST['question_ids']);
        
        // Lấy số thứ tự lớn nhất hiện tại
        $max_order_query = "SELECT MAX(question_order) as max_order FROM test_questions WHERE test_id = ?";
        $stmt = mysqli_prepare($conn, $max_order_query);
        mysqli_stmt_bind_param($stmt, "i", $test_id);
        mysqli_stmt_execute($stmt);
        $max_order_result = mysqli_stmt_get_result($stmt);
        $max_order_data = mysqli_fetch_assoc($max_order_result);
        $current_max_order = $max_order_data['max_order'] ?: 0;
        
        // Thêm từng câu hỏi
        $success_count = 0;
        foreach ($question_ids as $question_id) {
            // Kiểm tra xem câu hỏi đã tồn tại trong bài kiểm tra chưa
            $check_query = "SELECT id FROM test_questions WHERE test_id = ? AND question_id = ?";
            $stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($stmt, "ii", $test_id, $question_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            
            if (mysqli_stmt_num_rows($stmt) === 0) {
                $current_max_order++;
                $insert_query = "INSERT INTO test_questions (test_id, question_id, question_order) VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($stmt, "iii", $test_id, $question_id, $current_max_order);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success_count++;
                }
            }
        }
        
        if ($success_count > 0) {
            $_SESSION['message'] = "Đã thêm {$success_count} câu hỏi vào bài kiểm tra.";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Không có câu hỏi mới nào được thêm. Các câu hỏi có thể đã tồn tại trong bài kiểm tra.';
            $_SESSION['message_type'] = 'warning';
        }
        
        header('Location: manage_test_questions.php?test_id=' . $test_id);
        exit;
    } else {
        $error = 'Vui lòng chọn ít nhất một câu hỏi để thêm vào bài kiểm tra.';
    }
}

// Lấy danh sách câu hỏi trong bài kiểm tra
$questions_query = "
    SELECT q.*, tq.question_order 
    FROM test_questions tq
    JOIN math_questions q ON tq.question_id = q.id
    WHERE tq.test_id = ?
    ORDER BY tq.question_order ASC
";

$stmt = mysqli_prepare($conn, $questions_query);
mysqli_stmt_bind_param($stmt, "i", $test_id);
mysqli_stmt_execute($stmt);
$questions_result = mysqli_stmt_get_result($stmt);

// Lấy danh sách câu hỏi có thể thêm vào bài kiểm tra (cùng lớp, chưa có trong bài kiểm tra)
$available_questions_query = "
    SELECT q.* 
    FROM math_questions q
    WHERE q.grade_level = ?
    AND q.id NOT IN (
        SELECT question_id FROM test_questions WHERE test_id = ?
    )
    ORDER BY q.id DESC
";

$stmt = mysqli_prepare($conn, $available_questions_query);
mysqli_stmt_bind_param($stmt, "ii", $test['grade_level'], $test_id);
mysqli_stmt_execute($stmt);
$available_questions_result = mysqli_stmt_get_result($stmt);

// Bao gồm header quản trị
include 'includes/admin_header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1>Quản lý câu hỏi</h1>
        <p class="lead">Quản lý câu hỏi trong bài kiểm tra: <strong><?php echo htmlspecialchars($test['title']); ?></strong></p>
        <div class="mb-3">
            <span class="badge bg-info">Lớp <?php echo $test['grade_level']; ?></span>
            <span class="badge bg-secondary"><?php echo $test['time_limit']; ?> phút</span>
        </div>
    </div>
    <div class="col-md-4 text-end">
        <a href="manage_tests.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại danh sách bài kiểm tra
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Danh sách câu hỏi trong bài kiểm tra -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Câu hỏi trong bài kiểm tra (<?php echo mysqli_num_rows($questions_result); ?> câu)</h5>
    </div>
    <div class="card-body">
        <?php if (mysqli_num_rows($questions_result) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="5%">STT</th>
                            <th width="50%">Câu hỏi</th>
                            <th width="15%">Đáp án</th>
                            <th width="15%">Độ khó</th>
                            <th width="15%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($question = mysqli_fetch_assoc($questions_result)): ?>
                            <tr>
                                <td><?php echo $question['question_order']; ?></td>
                                <td>
                                    <div class="process-math"><?php echo htmlspecialchars($question['question_text']); ?></div>
                                </td>
                                <td><?php echo $question['correct_answer']; ?></td>
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
                                        <a href="manage_test_questions.php?test_id=<?php echo $test_id; ?>&action=remove&question_id=<?php echo $question['id']; ?>" class="btn btn-sm btn-danger" title="Xóa khỏi bài kiểm tra" onclick="return confirm('Bạn có chắc chắn muốn xóa câu hỏi này khỏi bài kiểm tra?');">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <img src="../images/empty_questions.svg" alt="Không có câu hỏi" height="120" class="mb-3" onerror="this.src='https://via.placeholder.com/120x120?text=No+Questions'">
                <p>Bài kiểm tra này chưa có câu hỏi nào. Hãy thêm câu hỏi vào bài kiểm tra bên dưới.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Thêm câu hỏi vào bài kiểm tra -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Thêm câu hỏi vào bài kiểm tra</h5>
    </div>
    <div class="card-body">
        <?php if (mysqli_num_rows($available_questions_result) > 0): ?>
            <form method="POST" action="">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="5%">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="select-all">
                                    </div>
                                </th>
                                <th width="50%">Câu hỏi</th>
                                <th width="15%">Đáp án</th>
                                <th width="15%">Độ khó</th>
                                <th width="15%">Chủ đề</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($question = mysqli_fetch_assoc($available_questions_result)): ?>
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input question-checkbox" type="checkbox" name="question_ids[]" value="<?php echo $question['id']; ?>">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="process-math"><?php echo htmlspecialchars($question['question_text']); ?></div>
                                    </td>
                                    <td><?php echo $question['correct_answer']; ?></td>
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
                                    <td><?php echo htmlspecialchars($question['topic']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <div>
                        <button type="button" id="select-all-btn" class="btn btn-outline-primary">
                            <i class="fas fa-check-square"></i> Chọn tất cả
                        </button>
                        <button type="button" id="deselect-all-btn" class="btn btn-outline-secondary">
                            <i class="fas fa-square"></i> Bỏ chọn tất cả
                        </button>
                    </div>
                    <button type="submit" name="add_questions" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Thêm câu hỏi đã chọn
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="text-center py-4">
                <p>Không có câu hỏi nào phù hợp để thêm vào bài kiểm tra. Tất cả câu hỏi lớp <?php echo $test['grade_level']; ?> đã được thêm vào bài kiểm tra này.</p>
                <a href="add_question.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Tạo câu hỏi mới
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Xử lý chọn/bỏ chọn tất cả
        const selectAllCheckbox = document.getElementById('select-all');
        const questionCheckboxes = document.querySelectorAll('.question-checkbox');
        const selectAllBtn = document.getElementById('select-all-btn');
        const deselectAllBtn = document.getElementById('deselect-all-btn');
        
        if (selectAllCheckbox && questionCheckboxes.length > 0) {
            selectAllCheckbox.addEventListener('change', function() {
                const isChecked = this.checked;
                questionCheckboxes.forEach(checkbox => {
                    checkbox.checked = isChecked;
                });
            });
            
            selectAllBtn.addEventListener('click', function() {
                questionCheckboxes.forEach(checkbox => {
                    checkbox.checked = true;
                });
                selectAllCheckbox.checked = true;
            });
            
            deselectAllBtn.addEventListener('click', function() {
                questionCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
                selectAllCheckbox.checked = false;
            });
        }
        
        // Xử lý các công thức toán học
        if (typeof processMathFormulas === 'function') {
            processMathFormulas();
        }
    });
</script>

<?php
// Bao gồm footer quản trị
include 'includes/admin_footer.php';
?>