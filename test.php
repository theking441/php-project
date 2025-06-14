<?php
// Trang làm bài kiểm tra
require_once 'includes/config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Vui lòng đăng nhập để làm bài kiểm tra';
    $_SESSION['message_type'] = 'warning';
    header('Location: login.php');
    exit;
}

// Kiểm tra ID bài kiểm tra
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = 'Bài kiểm tra không hợp lệ';
    $_SESSION['message_type'] = 'danger';
    header('Location: tests.php');
    exit;
}

$test_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

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
    $questions[] = $row;
}

// Tiêu đề trang
$page_title = 'Bài kiểm tra: ' . $test['title'];

// Bao gồm header
include 'includes/header.php';
?>

<div class="test-container">
    <!-- Decorative shapes -->
    <div class="shape-decoration shape-circle circle-1"></div>
    <div class="shape-decoration shape-circle circle-2"></div>
    <div class="shape-decoration shape-square square-1"></div>
    <div class="shape-decoration shape-triangle triangle-1"></div>
    
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="mb-3"><?php echo htmlspecialchars($test['title']); ?></h1>
            <p class="lead"><?php echo htmlspecialchars($test['description']); ?></p>
            
            <div class="test-level level-<?php echo $test['grade_level']; ?> mb-3">
                Lớp <?php echo $test['grade_level']; ?>
            </div>
        </div>
        <div class="col-md-4">
            <div class="test-timer-container text-center">
                <span id="test-timer" class="test-timer" data-time-limit="<?php echo $test['time_limit']; ?>">
                    <?php echo $test['time_limit']; ?>:00
                </span>
                <div class="mt-2">Thời gian làm bài</div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="progress-tracker text-center">
                <?php foreach ($questions as $index => $question): ?>
                    <div class="progress-item" data-question="<?php echo $index + 1; ?>">
                        <?php echo $index + 1; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <form id="testForm" method="POST" action="submit_test.php">
        <input type="hidden" name="test_id" value="<?php echo $test_id; ?>">
        <input type="hidden" name="start_time" value="<?php echo time(); ?>">
        
        <?php foreach ($questions as $index => $question): ?>
            <div class="question-card" id="question-<?php echo $index + 1; ?>" <?php echo $index > 0 ? 'style="display: none;"' : ''; ?>>
                <div class="question-number">
                    <span class="badge bg-primary rounded-pill">Câu hỏi <?php echo $index + 1; ?> / <?php echo count($questions); ?></span>
                </div>
                <div class="question-text process-math">
                    <?php echo htmlspecialchars($question['question_text']); ?>
                </div>
                
                <div class="options-container">
                    <input type="hidden" id="answer_<?php echo $question['id']; ?>" name="answers[<?php echo $question['id']; ?>]" value="">
                    
                    <button type="button" class="option-btn" data-option="A" data-question-id="<?php echo $question['id']; ?>">
                        <span class="option-letter">A</span> 
                        <span class="option-text process-math"><?php echo htmlspecialchars($question['option_a']); ?></span>
                        <span class="option-check"><i class="fas fa-check-circle"></i></span>
                    </button>
                    
                    <button type="button" class="option-btn" data-option="B" data-question-id="<?php echo $question['id']; ?>">
                        <span class="option-letter">B</span> 
                        <span class="option-text process-math"><?php echo htmlspecialchars($question['option_b']); ?></span>
                        <span class="option-check"><i class="fas fa-check-circle"></i></span>
                    </button>
                    
                    <button type="button" class="option-btn" data-option="C" data-question-id="<?php echo $question['id']; ?>">
                        <span class="option-letter">C</span> 
                        <span class="option-text process-math"><?php echo htmlspecialchars($question['option_c']); ?></span>
                        <span class="option-check"><i class="fas fa-check-circle"></i></span>
                    </button>
                    
                    <button type="button" class="option-btn" data-option="D" data-question-id="<?php echo $question['id']; ?>">
                        <span class="option-letter">D</span> 
                        <span class="option-text process-math"><?php echo htmlspecialchars($question['option_d']); ?></span>
                        <span class="option-check"><i class="fas fa-check-circle"></i></span>
                    </button>
                </div>
                
                <div class="question-navigation mt-4 text-center">
                    <?php if ($index > 0): ?>
                        <button type="button" class="btn btn-outline-secondary prev-question">
                            <i class="fas fa-arrow-left"></i> Câu trước
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($index < count($questions) - 1): ?>
                        <button type="button" class="btn btn-primary next-question">
                            Câu tiếp theo <i class="fas fa-arrow-right"></i>
                        </button>
                    <?php else: ?>
                        <button type="button" id="submitTestBtn" class="btn btn-success btn-lg">
                            <i class="fas fa-paper-plane"></i> Nộp bài
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </form>
</div>

<!-- Các nhân vật hoạt hình khuyến khích -->
<div class="character-container text-center mt-4" style="display: none;">
    <img src="images/encourage_character.svg" alt="Nhân vật khuyến khích" class="character" height="100">
    <p class="character-message">Cố lên bạn! Bạn làm rất tốt!</p>
</div>

<script src="js/test_timer.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Khởi tạo các biến
    const questions = document.querySelectorAll('.question-card');
    const progressItems = document.querySelectorAll('.progress-item');
    const prevButtons = document.querySelectorAll('.prev-question');
    const nextButtons = document.querySelectorAll('.next-question');
    const submitButton = document.getElementById('submitTestBtn');
    let currentQuestion = 0;
    
    // Đánh dấu câu hỏi hiện tại
    progressItems[currentQuestion].classList.add('current');
    
    // Xử lý sự kiện nút câu trước
    prevButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (currentQuestion > 0) {
                // Ẩn câu hỏi hiện tại
                questions[currentQuestion].style.display = 'none';
                // Hiện câu hỏi trước
                currentQuestion--;
                questions[currentQuestion].style.display = 'block';
                // Cập nhật theo dõi tiến độ
                updateProgressTracker();
                // Hiện nhân vật ngẫu nhiên
                showRandomCharacter();
            }
        });
    });
    
    // Xử lý sự kiện nút câu tiếp theo
    nextButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (currentQuestion < questions.length - 1) {
                // Ẩn câu hỏi hiện tại
                questions[currentQuestion].style.display = 'none';
                // Hiện câu hỏi tiếp theo
                currentQuestion++;
                questions[currentQuestion].style.display = 'block';
                // Cập nhật theo dõi tiến độ
                updateProgressTracker();
                // Hiện nhân vật ngẫu nhiên
                showRandomCharacter();
            }
        });
    });
    
    // Xử lý sự kiện nút chọn đáp án
    const optionButtons = document.querySelectorAll('.option-btn');
    optionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const questionId = this.getAttribute('data-question-id');
            const option = this.getAttribute('data-option');
            
            // Bỏ chọn các nút khác trong cùng câu hỏi
            document.querySelectorAll(`.option-btn[data-question-id="${questionId}"]`).forEach(btn => {
                btn.classList.remove('selected');
            });
            
            // Đánh dấu nút này là đã chọn
            this.classList.add('selected');
            
            // Lưu câu trả lời
            document.getElementById(`answer_${questionId}`).value = option;
            
            // Cập nhật theo dõi tiến độ
            const questionIndex = Array.from(questions).findIndex(q => q.contains(this));
            progressItems[questionIndex].classList.add('completed');
            
            // Tự động chuyển sang câu tiếp theo sau khi chọn
            setTimeout(() => {
                if (currentQuestion < questions.length - 1) {
                    // Ẩn câu hỏi hiện tại
                    questions[currentQuestion].style.display = 'none';
                    // Hiện câu hỏi tiếp theo
                    currentQuestion++;
                    questions[currentQuestion].style.display = 'block';
                    // Cập nhật theo dõi tiến độ
                    updateProgressTracker();
                }
            }, 500);
        });
    });
    
    // Xử lý sự kiện nút nộp bài
    if (submitButton) {
        submitButton.addEventListener('click', function() {
            // Đếm số câu đã trả lời
            const answeredQuestions = document.querySelectorAll('input[name^="answers["][value!=""]').length;
            const totalQuestions = questions.length;
            
            if (answeredQuestions < totalQuestions) {
                if (!confirm(`Bạn mới trả lời ${answeredQuestions}/${totalQuestions} câu hỏi. Bạn có chắc chắn muốn nộp bài?`)) {
                    return;
                }
            }
            
            // Gửi form
            document.getElementById('testForm').submit();
        });
    }
    
    // Xử lý sự kiện click vào mục tiến độ
    progressItems.forEach((item, index) => {
        item.addEventListener('click', function() {
            // Ẩn câu hỏi hiện tại
            questions[currentQuestion].style.display = 'none';
            // Hiện câu hỏi được chọn
            currentQuestion = index;
            questions[currentQuestion].style.display = 'block';
            // Cập nhật theo dõi tiến độ
            updateProgressTracker();
        });
    });
    
    // Cập nhật theo dõi tiến độ
    function updateProgressTracker() {
        progressItems.forEach((item, index) => {
            item.classList.remove('current');
            if (index === currentQuestion) {
                item.classList.add('current');
            }
        });
    }
    
    // Hiển thị nhân vật ngẫu nhiên để khuyến khích
    function showRandomCharacter() {
        // Xác suất hiển thị nhân vật (20%)
        if (Math.random() < 0.2) {
            const characterContainer = document.querySelector('.character-container');
            const characterImage = characterContainer.querySelector('img');
            const characterMessage = characterContainer.querySelector('.character-message');
            
            // Các thông điệp khuyến khích
            const messages = [
                "Cố lên bạn! Bạn làm rất tốt!",
                "Tuyệt vời! Tiếp tục nào!",
                "Bạn thật thông minh!",
                "Giỏi lắm! Hãy hoàn thành nốt bài kiểm tra nhé!",
                "Wow! Bạn đang làm rất tốt đấy!"
            ];
            
            // Các nhân vật
            const characters = [
                "encourage_character.svg",
                "star_character.svg",
                "robot_character.svg",
                "wizard_character.svg",
                "animal_character.svg"
            ];
            
            // Chọn ngẫu nhiên
            const randomMessage = messages[Math.floor(Math.random() * messages.length)];
            const randomCharacter = characters[Math.floor(Math.random() * characters.length)];
            
            // Cập nhật nhân vật và thông điệp
            characterImage.src = `images/${randomCharacter}`;
            characterMessage.textContent = randomMessage;
            
            // Hiển thị
            characterContainer.style.display = 'block';
            
            // Ẩn sau 3 giây
            setTimeout(() => {
                characterContainer.style.display = 'none';
            }, 3000);
        }
    }
});
</script>

<?php
// Bao gồm footer
include 'includes/footer.php';
?>