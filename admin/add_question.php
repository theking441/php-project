<?php
// Trang thêm câu hỏi mới
require_once '../includes/config.php';

// Kiểm tra đăng nhập và quyền quản trị
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    $_SESSION['message'] = 'Bạn không có quyền truy cập trang quản trị';
    $_SESSION['message_type'] = 'danger';
    header('Location: ../index.php');
    exit;
}

// Xử lý thêm câu hỏi mới
$error = '';
$success = '';
$preview_data = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra xem có phải là tạo câu hỏi mới hay chỉ xem trước
    $is_preview = isset($_POST['preview']);
    
    // Lấy và làm sạch dữ liệu đầu vào
    $question_text = clean_input($_POST['question_text']);
    $option_a = clean_input($_POST['option_a']);
    $option_b = clean_input($_POST['option_b']);
    $option_c = clean_input($_POST['option_c']);
    $option_d = clean_input($_POST['option_d']);
    $correct_answer = clean_input($_POST['correct_answer']);
    $explanation = clean_input($_POST['explanation']);
    $grade_level = intval($_POST['grade_level']);
    $difficulty = clean_input($_POST['difficulty']);
    $topic = clean_input($_POST['topic']);
    
    // Kiểm tra dữ liệu
    if (empty($question_text) || empty($option_a) || empty($option_b) || empty($option_c) || empty($option_d) || 
        empty($correct_answer) || empty($grade_level) || empty($difficulty) || empty($topic)) {
        $error = 'Vui lòng điền đầy đủ thông tin bắt buộc.';
    } elseif (!in_array($correct_answer, ['A', 'B', 'C', 'D'])) {
        $error = 'Đáp án đúng phải là A, B, C hoặc D.';
    } elseif ($grade_level < 1 || $grade_level > 5) {
        $error = 'Lớp phải từ 1 đến 5.';
    } elseif (!in_array($difficulty, ['easy', 'medium', 'hard'])) {
        $error = 'Độ khó phải là dễ, trung bình hoặc khó.';
    } else {
        // Tạo dữ liệu xem trước
        $preview_data = [
            'question_text' => $question_text,
            'option_a' => $option_a,
            'option_b' => $option_b,
            'option_c' => $option_c,
            'option_d' => $option_d,
            'correct_answer' => $correct_answer,
            'explanation' => $explanation,
            'grade_level' => $grade_level,
            'difficulty' => $difficulty,
            'topic' => $topic
        ];
        
        // Nếu không phải xem trước và không có lỗi, thêm câu hỏi vào cơ sở dữ liệu
        if (!$is_preview) {
            $stmt = mysqli_prepare($conn, "INSERT INTO math_questions (question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, grade_level, difficulty, topic) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sssssssiss", $question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer, $explanation, $grade_level, $difficulty, $topic);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = 'Thêm câu hỏi mới thành công!';
                $_SESSION['message_type'] = 'success';
                header('Location: manage_questions.php');
                exit;
            } else {
                $error = 'Có lỗi xảy ra khi thêm câu hỏi: ' . mysqli_error($conn);
            }
        }
    }
}

// Lấy danh sách chủ đề hiện có
$topics_query = "SELECT DISTINCT topic FROM math_questions ORDER BY topic";
$topics_result = mysqli_query($conn, $topics_query);

// Bao gồm header quản trị
include 'includes/admin_header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1>Thêm câu hỏi mới</h1>
        <p class="lead">Tạo câu hỏi toán học mới cho bài kiểm tra.</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="manage_questions.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại danh sách
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-<?php echo $preview_data ? '8' : '12'; ?>">
        <div class="card form-card">
            <div class="card-header">
                <h5 class="mb-0">Thông tin câu hỏi</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" id="questionForm">
                    <div class="mb-3">
                        <label for="question_text" class="form-label required-field">Nội dung câu hỏi</label>
                        <textarea class="form-control process-math" id="question_text" name="question_text" rows="3" required><?php echo isset($preview_data['question_text']) ? htmlspecialchars($preview_data['question_text']) : ''; ?></textarea>
                        <div class="form-text">Bạn có thể sử dụng công thức toán học bằng cách viết giữa các ký tự $$ ... $$ (ví dụ: $$\frac{1}{2}$$)</div>
                    </div>

                    <!-- Trình soạn thảo công thức toán học -->
                    <div class="card mb-3">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-square-root-alt"></i> Soạn thảo công thức toán học</h5>
                        </div>
                        <div class="card-body" id="math-editor-container">
                            <div class="mb-3">
                                <label for="formula-input" class="form-label">Nhập công thức LaTeX</label>
                                <input type="text" class="form-control" id="formula-input" placeholder="Ví dụ: \frac{1}{2} \times 3 = \frac{3}{2}">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Xem trước</label>
                                <div class="border rounded p-3 bg-light" id="formula-preview">
                                    <em>Xem trước công thức</em>
                                </div>
                            </div>
                            
                            <button type="button" class="btn btn-info" id="insert-formula">
                                <i class="fas fa-plus"></i> Chèn công thức vào câu hỏi
                            </button>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="option_a" class="form-label required-field">Đáp án A</label>
                            <input type="text" class="form-control" id="option_a" name="option_a" value="<?php echo isset($preview_data['option_a']) ? htmlspecialchars($preview_data['option_a']) : ''; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="option_b" class="form-label required-field">Đáp án B</label>
                            <input type="text" class="form-control" id="option_b" name="option_b" value="<?php echo isset($preview_data['option_b']) ? htmlspecialchars($preview_data['option_b']) : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="option_c" class="form-label required-field">Đáp án C</label>
                            <input type="text" class="form-control" id="option_c" name="option_c" value="<?php echo isset($preview_data['option_c']) ? htmlspecialchars($preview_data['option_c']) : ''; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="option_d" class="form-label required-field">Đáp án D</label>
                            <input type="text" class="form-control" id="option_d" name="option_d" value="<?php echo isset($preview_data['option_d']) ? htmlspecialchars($preview_data['option_d']) : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="correct_answer" class="form-label required-field">Đáp án đúng</label>
                        <select class="form-select" id="correct_answer" name="correct_answer" required>
                            <option value="">Chọn đáp án đúng</option>
                            <option value="A" <?php echo (isset($preview_data['correct_answer']) && $preview_data['correct_answer'] === 'A') ? 'selected' : ''; ?>>A</option>
                            <option value="B" <?php echo (isset($preview_data['correct_answer']) && $preview_data['correct_answer'] === 'B') ? 'selected' : ''; ?>>B</option>
                            <option value="C" <?php echo (isset($preview_data['correct_answer']) && $preview_data['correct_answer'] === 'C') ? 'selected' : ''; ?>>C</option>
                            <option value="D" <?php echo (isset($preview_data['correct_answer']) && $preview_data['correct_answer'] === 'D') ? 'selected' : ''; ?>>D</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="explanation" class="form-label">Giải thích</label>
                        <textarea class="form-control" id="explanation" name="explanation" rows="3"><?php echo isset($preview_data['explanation']) ? htmlspecialchars($preview_data['explanation']) : ''; ?></textarea>
                        <div class="form-text">Giải thích tại sao đáp án này là đúng (sẽ hiển thị sau khi học sinh trả lời).</div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="grade_level" class="form-label required-field">Lớp</label>
                            <select class="form-select" id="grade_level" name="grade_level" required>
                                <option value="">Chọn lớp</option>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo (isset($preview_data['grade_level']) && $preview_data['grade_level'] == $i) ? 'selected' : ''; ?>>
                                        Lớp <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="difficulty" class="form-label required-field">Độ khó</label>
                            <select class="form-select" id="difficulty" name="difficulty" required>
                                <option value="">Chọn độ khó</option>
                                <option value="easy" <?php echo (isset($preview_data['difficulty']) && $preview_data['difficulty'] === 'easy') ? 'selected' : ''; ?>>Dễ</option>
                                <option value="medium" <?php echo (isset($preview_data['difficulty']) && $preview_data['difficulty'] === 'medium') ? 'selected' : ''; ?>>Trung bình</option>
                                <option value="hard" <?php echo (isset($preview_data['difficulty']) && $preview_data['difficulty'] === 'hard') ? 'selected' : ''; ?>>Khó</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="topic" class="form-label required-field">Chủ đề</label>
                            <input type="text" class="form-control" id="topic" name="topic" list="topic-list" value="<?php echo isset($preview_data['topic']) ? htmlspecialchars($preview_data['topic']) : ''; ?>" required>
                            <datalist id="topic-list">
                                <?php mysqli_data_seek($topics_result, 0); ?>
                                <?php while ($topic = mysqli_fetch_assoc($topics_result)): ?>
                                    <option value="<?php echo htmlspecialchars($topic['topic']); ?>">
                                <?php endwhile; ?>
                            </datalist>
                            <div class="form-text">Bạn có thể chọn chủ đề hiện có hoặc nhập chủ đề mới.</div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <button type="submit" name="preview" class="btn btn-outline-primary w-100">
                                <i class="fas fa-eye"></i> Xem trước
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button type="submit" name="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save"></i> Lưu câu hỏi
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php if ($preview_data): ?>
    <div class="col-md-4">
        <div class="card sticky-top" style="top: 20px;">
            <div class="card-header">
                <h5 class="mb-0">Xem trước câu hỏi</h5>
            </div>
            <div class="card-body">
                <div class="question-preview">
                    <div class="question-preview-title">Câu hỏi</div>
                    <p><?php echo htmlspecialchars($preview_data['question_text']); ?></p>
                    
                    <div class="options-container mt-3">
                        <div class="question-option <?php echo ($preview_data['correct_answer'] === 'A') ? 'correct' : ''; ?>">
                            <span class="option-letter">A.</span> <?php echo htmlspecialchars($preview_data['option_a']); ?>
                            <?php if ($preview_data['correct_answer'] === 'A'): ?>
                                <span class="float-end text-success"><i class="fas fa-check"></i></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="question-option <?php echo ($preview_data['correct_answer'] === 'B') ? 'correct' : ''; ?>">
                            <span class="option-letter">B.</span> <?php echo htmlspecialchars($preview_data['option_b']); ?>
                            <?php if ($preview_data['correct_answer'] === 'B'): ?>
                                <span class="float-end text-success"><i class="fas fa-check"></i></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="question-option <?php echo ($preview_data['correct_answer'] === 'C') ? 'correct' : ''; ?>">
                            <span class="option-letter">C.</span> <?php echo htmlspecialchars($preview_data['option_c']); ?>
                            <?php if ($preview_data['correct_answer'] === 'C'): ?>
                                <span class="float-end text-success"><i class="fas fa-check"></i></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="question-option <?php echo ($preview_data['correct_answer'] === 'D') ? 'correct' : ''; ?>">
                            <span class="option-letter">D.</span> <?php echo htmlspecialchars($preview_data['option_d']); ?>
                            <?php if ($preview_data['correct_answer'] === 'D'): ?>
                                <span class="float-end text-success"><i class="fas fa-check"></i></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($preview_data['explanation'])): ?>
                        <div class="explanation mt-3">
                            <strong>Giải thích:</strong> <?php echo htmlspecialchars($preview_data['explanation']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="question-info mt-3">
                        <div class="badge bg-info me-2">Lớp <?php echo $preview_data['grade_level']; ?></div>
                        <div class="badge <?php 
                            echo ($preview_data['difficulty'] === 'easy') ? 'bg-success' : 
                                (($preview_data['difficulty'] === 'medium') ? 'bg-warning' : 'bg-danger'); 
                        ?> me-2">
                            <?php 
                                echo ($preview_data['difficulty'] === 'easy') ? 'Dễ' : 
                                    (($preview_data['difficulty'] === 'medium') ? 'Trung bình' : 'Khó'); 
                            ?>
                        </div>
                        <div class="badge bg-secondary"><?php echo htmlspecialchars($preview_data['topic']); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
// Bao gồm footer quản trị
include 'includes/admin_footer.php';
?>