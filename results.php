<?php
// Trang hiển thị kết quả bài kiểm tra
require_once 'includes/config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Vui lòng đăng nhập để xem kết quả bài kiểm tra';
    $_SESSION['message_type'] = 'warning';
    header('Location: login.php');
    exit;
}

// Kiểm tra ID kết quả
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = 'Kết quả bài kiểm tra không hợp lệ';
    $_SESSION['message_type'] = 'danger';
    header('Location: dashboard.php');
    exit;
}

$result_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];

// Lấy thông tin kết quả bài kiểm tra
if ($is_admin) {
    // Admin có thể xem kết quả của bất kỳ ai
    $stmt = mysqli_prepare($conn, "
        SELECT r.*, t.title, t.description, t.grade_level, t.time_limit
        FROM test_results r
        JOIN tests t ON r.test_id = t.id
        WHERE r.id = ?
    ");
    mysqli_stmt_bind_param($stmt, "i", $result_id);
} else {
    // Người dùng chỉ xem được kết quả của chính họ
    $stmt = mysqli_prepare($conn, "
        SELECT r.*, t.title, t.description, t.grade_level, t.time_limit
        FROM test_results r
        JOIN tests t ON r.test_id = t.id
        WHERE r.id = ? AND r.user_id = ?
    ");
    mysqli_stmt_bind_param($stmt, "ii", $result_id, $user_id);
}

mysqli_stmt_execute($stmt);
$result_data = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result_data) === 0) {
    $_SESSION['message'] = 'Không tìm thấy kết quả bài kiểm tra hoặc bạn không có quyền xem kết quả này';
    $_SESSION['message_type'] = 'danger';
    header('Location: dashboard.php');
    exit;
}

$test_result = mysqli_fetch_assoc($result_data);

// Lấy các câu trả lời của người dùng
$stmt = mysqli_prepare($conn, "
    SELECT a.*, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d, q.correct_answer, q.explanation
    FROM user_answers a
    JOIN math_questions q ON a.question_id = q.id
    WHERE a.test_result_id = ?
    ORDER BY q.id ASC
");
mysqli_stmt_bind_param($stmt, "i", $result_id);
mysqli_stmt_execute($stmt);
$answers_result = mysqli_stmt_get_result($stmt);

// Số câu đúng và tổng số câu
$correct_count = 0;
$total_questions = mysqli_num_rows($answers_result);
$answers = [];

while ($answer = mysqli_fetch_assoc($answers_result)) {
    $answers[] = $answer;
    if ($answer['is_correct']) {
        $correct_count++;
    }
}

// Phân loại kết quả
$result_category = '';
$result_message = '';
$result_character = '';

if ($test_result['score'] >= 9) {
    $result_category = 'excellent';
    $result_message = 'Xuất sắc! Bạn là một thiên tài toán học!';
    $result_character = 'star_character.svg';
} elseif ($test_result['score'] >= 7) {
    $result_category = 'good';
    $result_message = 'Rất tốt! Bạn đã làm rất tốt bài kiểm tra này!';
    $result_character = 'happy_character.svg';
} elseif ($test_result['score'] >= 5) {
    $result_category = 'average';
    $result_message = 'Khá tốt! Hãy tiếp tục luyện tập để đạt kết quả cao hơn!';
    $result_character = 'think_character.svg';
} else {
    $result_category = 'poor';
    $result_message = 'Cần cố gắng hơn! Đừng bỏ cuộc, hãy tiếp tục luyện tập!';
    $result_character = 'sad_character.svg';
}

// Lấy các huy hiệu đã đạt được (nếu có)
$earned_badges = [];
if (isset($_SESSION['earned_badges']) && !empty($_SESSION['earned_badges'])) {
    $badge_ids = $_SESSION['earned_badges'];
    $placeholders = implode(',', array_fill(0, count($badge_ids), '?'));
    
    $query = "SELECT * FROM badges WHERE id IN ($placeholders)";
    $stmt = mysqli_prepare($conn, $query);
    
    // Bind các ID huy hiệu
    $types = str_repeat('i', count($badge_ids));
    mysqli_stmt_bind_param($stmt, $types, ...$badge_ids);
    
    mysqli_stmt_execute($stmt);
    $badges_result = mysqli_stmt_get_result($stmt);
    
    while ($badge = mysqli_fetch_assoc($badges_result)) {
        $earned_badges[] = $badge;
    }
    
    // Xóa huy hiệu đã kiểm tra khỏi session
    unset($_SESSION['earned_badges']);
}

// Bao gồm header
include 'includes/header.php';
?>

<div class="row justify-content-center mb-4">
    <div class="col-md-10">
        <h1 class="mb-3">Kết quả bài kiểm tra</h1>
        <h3><?php echo htmlspecialchars($test_result['title']); ?></h3>
        <p class="lead"><?php echo htmlspecialchars($test_result['description']); ?></p>
    </div>
</div>

<!-- Tóm tắt kết quả -->
<div class="row justify-content-center mb-5">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body text-center">
                <div class="result-animation">
                    <div class="result-score <?php echo $result_category; ?>"><?php echo $test_result['score']; ?></div>
                    <div class="result-message"><?php echo $result_message; ?></div>
                    <img src="images/<?php echo $result_character; ?>" alt="Character" class="character" height="150">
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="result-stat">
                            <div class="result-stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="result-stat-value"><?php echo $correct_count; ?>/<?php echo $total_questions; ?></div>
                            <div class="result-stat-label">Câu trả lời đúng</div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="result-stat">
                            <div class="result-stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="result-stat-value">
                                <?php 
                                    $minutes = floor($test_result['completion_time'] / 60);
                                    $seconds = $test_result['completion_time'] % 60;
                                    echo sprintf('%02d:%02d', $minutes, $seconds);
                                ?>
                            </div>
                            <div class="result-stat-label">Thời gian hoàn thành</div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="result-stat">
                            <div class="result-stat-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="result-stat-value">
                                <?php echo date('d/m/Y', strtotime($test_result['completed_at'])); ?>
                            </div>
                            <div class="result-stat-label">Ngày hoàn thành</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Huy hiệu đạt được -->
<?php if (!empty($earned_badges)): ?>
<div class="row justify-content-center mb-5">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3>Chúc mừng! Bạn đã đạt được huy hiệu mới!</h3>
            </div>
            <div class="card-body">
                <div class="row justify-content-center">
                    <?php foreach ($earned_badges as $badge): ?>
                        <div class="col-md-4 text-center">
                            <div class="new-badge mb-4">
                                <img src="images/<?php echo $badge['image_path']; ?>" alt="<?php echo htmlspecialchars($badge['name']); ?>" class="badge-img new-badge">
                                <h5 class="mt-3"><?php echo htmlspecialchars($badge['name']); ?></h5>
                                <p><?php echo htmlspecialchars($badge['description']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Chi tiết câu trả lời -->
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card">
            <div class="card-header">
                <h3>Chi tiết câu trả lời</h3>
            </div>
            <div class="card-body">
                <?php foreach ($answers as $index => $answer): ?>
                    <div class="answer-detail mb-4">
                        <div class="question-text">
                            <span class="question-number"><?php echo $index + 1; ?>.</span>
                            <?php echo htmlspecialchars($answer['question_text']); ?>
                        </div>
                        
                        <div class="options-container mt-3">
                            <div class="option-item <?php echo ($answer['user_answer'] === 'A') ? ($answer['is_correct'] ? 'correct' : 'incorrect') : (($answer['correct_answer'] === 'A') ? 'correct-answer' : ''); ?>">
                                <span class="option-letter">A.</span>
                                <?php echo htmlspecialchars($answer['option_a']); ?>
                                <?php if ($answer['user_answer'] === 'A'): ?>
                                    <span class="user-choice"><i class="fas <?php echo $answer['is_correct'] ? 'fa-check' : 'fa-times'; ?>"></i></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="option-item <?php echo ($answer['user_answer'] === 'B') ? ($answer['is_correct'] ? 'correct' : 'incorrect') : (($answer['correct_answer'] === 'B') ? 'correct-answer' : ''); ?>">
                                <span class="option-letter">B.</span>
                                <?php echo htmlspecialchars($answer['option_b']); ?>
                                <?php if ($answer['user_answer'] === 'B'): ?>
                                    <span class="user-choice"><i class="fas <?php echo $answer['is_correct'] ? 'fa-check' : 'fa-times'; ?>"></i></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="option-item <?php echo ($answer['user_answer'] === 'C') ? ($answer['is_correct'] ? 'correct' : 'incorrect') : (($answer['correct_answer'] === 'C') ? 'correct-answer' : ''); ?>">
                                <span class="option-letter">C.</span>
                                <?php echo htmlspecialchars($answer['option_c']); ?>
                                <?php if ($answer['user_answer'] === 'C'): ?>
                                    <span class="user-choice"><i class="fas <?php echo $answer['is_correct'] ? 'fa-check' : 'fa-times'; ?>"></i></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="option-item <?php echo ($answer['user_answer'] === 'D') ? ($answer['is_correct'] ? 'correct' : 'incorrect') : (($answer['correct_answer'] === 'D') ? 'correct-answer' : ''); ?>">
                                <span class="option-letter">D.</span>
                                <?php echo htmlspecialchars($answer['option_d']); ?>
                                <?php if ($answer['user_answer'] === 'D'): ?>
                                    <span class="user-choice"><i class="fas <?php echo $answer['is_correct'] ? 'fa-check' : 'fa-times'; ?>"></i></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($answer['explanation'])): ?>
                        <div class="explanation mt-2">
                            <strong>Giải thích:</strong> <?php echo htmlspecialchars($answer['explanation']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
