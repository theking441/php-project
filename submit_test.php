<?php
// Trang xử lý nộp bài kiểm tra
require_once 'includes/config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Vui lòng đăng nhập để nộp bài kiểm tra';
    $_SESSION['message_type'] = 'warning';
    header('Location: login.php');
    exit;
}

// Kiểm tra dữ liệu POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['message'] = 'Phương thức không hợp lệ';
    $_SESSION['message_type'] = 'danger';
    header('Location: tests.php');
    exit;
}

// Lấy dữ liệu từ form
$test_id = isset($_POST['test_id']) ? intval($_POST['test_id']) : 0;
$user_id = $_SESSION['user_id'];
$start_time = isset($_POST['start_time']) ? intval($_POST['start_time']) : 0;
$end_time = time();
$completion_time = $end_time - $start_time; // Thời gian hoàn thành (giây)

// Kiểm tra bài kiểm tra hợp lệ
if ($test_id <= 0) {
    $_SESSION['message'] = 'Bài kiểm tra không hợp lệ';
    $_SESSION['message_type'] = 'danger';
    header('Location: tests.php');
    exit;
}

// Lấy thông tin bài kiểm tra
$stmt = mysqli_prepare($conn, "SELECT * FROM tests WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $test_id);
mysqli_stmt_execute($stmt);
$test_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($test_result) === 0) {
    $_SESSION['message'] = 'Bài kiểm tra không tồn tại';
    $_SESSION['message_type'] = 'danger';
    header('Location: tests.php');
    exit;
}

$test = mysqli_fetch_assoc($test_result);

// Lấy câu hỏi trong bài kiểm tra
$stmt = mysqli_prepare($conn, "
    SELECT q.*, tq.question_order
    FROM test_questions tq
    JOIN math_questions q ON tq.question_id = q.id
    WHERE tq.test_id = ?
    ORDER BY tq.question_order ASC
");
mysqli_stmt_bind_param($stmt, "i", $test_id);
mysqli_stmt_execute($stmt);
$questions_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($questions_result) === 0) {
    $_SESSION['message'] = 'Bài kiểm tra này chưa có câu hỏi';
    $_SESSION['message_type'] = 'warning';
    header('Location: tests.php');
    exit;
}

// Chuyển kết quả thành mảng câu hỏi
$questions = [];
while ($row = mysqli_fetch_assoc($questions_result)) {
    $questions[$row['id']] = $row;
}

// Lấy câu trả lời từ form
$answers = isset($_POST['answers']) ? $_POST['answers'] : [];

// Tính điểm
$correct_count = 0;
$total_questions = count($questions);
$user_answers = [];

foreach ($questions as $question_id => $question) {
    $user_answer = isset($answers[$question_id]) ? $answers[$question_id] : '';
    $is_correct = ($user_answer === $question['correct_answer']);
    
    if ($is_correct) {
        $correct_count++;
    }
    
    $user_answers[] = [
        'question_id' => $question_id,
        'user_answer' => $user_answer,
        'is_correct' => $is_correct
    ];
}

// Tính điểm trên thang 10
$score = ($total_questions > 0) ? (($correct_count / $total_questions) * 10) : 0;
$score = round($score, 1); // Làm tròn đến 1 chữ số thập phân

// Bắt đầu transaction
mysqli_begin_transaction($conn);

try {
    // Thêm kết quả kiểm tra vào cơ sở dữ liệu
    $stmt = mysqli_prepare($conn, "
        INSERT INTO test_results (user_id, test_id, score, completion_time)
        VALUES (?, ?, ?, ?)
    ");
    mysqli_stmt_bind_param($stmt, "iidd", $user_id, $test_id, $score, $completion_time);
    mysqli_stmt_execute($stmt);
    
    // Lấy ID kết quả vừa thêm
    $result_id = mysqli_insert_id($conn);
    
    // Thêm câu trả lời của người dùng
    foreach ($user_answers as $answer) {
        $stmt = mysqli_prepare($conn, "
            INSERT INTO user_answers (test_result_id, question_id, user_answer, is_correct)
            VALUES (?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($stmt, "iisi", $result_id, $answer['question_id'], $answer['user_answer'], $answer['is_correct']);
        mysqli_stmt_execute($stmt);
    }
    
    // Kiểm tra huy hiệu
    $earned_badges = [];
    
    // Kiểm tra huy hiệu "Nhà toán học tập sự" (hoàn thành bài kiểm tra đầu tiên)
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as test_count FROM test_results WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $test_count = mysqli_fetch_assoc($result)['test_count'];
    
    if ($test_count === 1) {
        $badge_id = 1; // ID của huy hiệu "Nhà toán học tập sự"
        addUserBadge($conn, $user_id, $badge_id);
        $earned_badges[] = $badge_id;
    }
    
    // Kiểm tra huy hiệu "Siêu sao toán học" (đạt điểm 10/10)
    if ($score === 10) {
        $badge_id = 2; // ID của huy hiệu "Siêu sao toán học"
        if (addUserBadge($conn, $user_id, $badge_id)) {
            $earned_badges[] = $badge_id;
        }
    }
    
    // Kiểm tra huy hiệu "Tia chớp" (hoàn thành bài kiểm tra trong thời gian ngắn)
    // Nếu thời gian hoàn thành ít hơn một nửa thời gian cho phép
    $time_limit_seconds = $test['time_limit'] * 60;
    if ($completion_time < ($time_limit_seconds / 2)) {
        $badge_id = 3; // ID của huy hiệu "Tia chớp"
        if (addUserBadge($conn, $user_id, $badge_id)) {
            $earned_badges[] = $badge_id;
        }
    }
    
    // Kiểm tra huy hiệu "Người kiên trì" (hoàn thành 5 bài kiểm tra)
    if ($test_count === 5) {
        $badge_id = 4; // ID của huy hiệu "Người kiên trì"
        if (addUserBadge($conn, $user_id, $badge_id)) {
            $earned_badges[] = $badge_id;
        }
    }
    
    // Kiểm tra huy hiệu "Bậc thầy toán học" (đạt điểm tối đa trong 3 bài kiểm tra liên tiếp)
    if ($score === 10) {
        $stmt = mysqli_prepare($conn, "
            SELECT COUNT(*) as perfect_streak
            FROM (
                SELECT score
                FROM test_results
                WHERE user_id = ?
                ORDER BY completed_at DESC
                LIMIT 3
            ) as recent_tests
            WHERE score = 10
        ");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $perfect_streak = mysqli_fetch_assoc($result)['perfect_streak'];
        
        if ($perfect_streak === 3) {
            $badge_id = 5; // ID của huy hiệu "Bậc thầy toán học"
            if (addUserBadge($conn, $user_id, $badge_id)) {
                $earned_badges[] = $badge_id;
            }
        }
    }
    
    // Lưu các huy hiệu mới vào session để hiển thị trong trang kết quả
    $_SESSION['earned_badges'] = $earned_badges;
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Chuyển hướng đến trang kết quả
    header('Location: results.php?id=' . $result_id);
    exit;
    
} catch (Exception $e) {
    // Rollback nếu có lỗi
    mysqli_rollback($conn);
    
    $_SESSION['message'] = 'Đã xảy ra lỗi khi lưu kết quả: ' . $e->getMessage();
    $_SESSION['message_type'] = 'danger';
    header('Location: tests.php');
    exit;
}

// Hàm thêm huy hiệu cho người dùng
function addUserBadge($conn, $user_id, $badge_id) {
    // Kiểm tra xem người dùng đã có huy hiệu này chưa
    $stmt = mysqli_prepare($conn, "SELECT id FROM user_badges WHERE user_id = ? AND badge_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $badge_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    // Nếu chưa có, thêm mới
    if (mysqli_stmt_num_rows($stmt) === 0) {
        $stmt = mysqli_prepare($conn, "INSERT INTO user_badges (user_id, badge_id) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $badge_id);
        mysqli_stmt_execute($stmt);
        return true;
    }
    
    return false;
}
?>