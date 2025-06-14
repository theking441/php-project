-- Tạo cơ sở dữ liệu
CREATE DATABASE IF NOT EXISTS math_test_db;
USE math_test_db;

-- Tạo bảng người dùng
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    grade INT NOT NULL COMMENT 'Lớp 1-5',
    school_name VARCHAR(100),
    parent_phone VARCHAR(20),
    profile_image VARCHAR(255) DEFAULT 'default_avatar.png',
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tạo bảng câu hỏi toán
CREATE TABLE IF NOT EXISTS math_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_text TEXT NOT NULL,
    option_a TEXT NOT NULL,
    option_b TEXT NOT NULL,
    option_c TEXT NOT NULL,
    option_d TEXT NOT NULL,
    correct_answer CHAR(1) NOT NULL COMMENT 'A, B, C hoặc D',
    explanation TEXT,
    grade_level INT NOT NULL COMMENT 'Lớp 1-5',
    difficulty ENUM('easy', 'medium', 'hard') NOT NULL,
    topic VARCHAR(50) NOT NULL COMMENT 'phép cộng, phép trừ, ...',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tạo bảng bài kiểm tra
CREATE TABLE IF NOT EXISTS tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    grade_level INT NOT NULL COMMENT 'Lớp 1-5',
    time_limit INT NOT NULL COMMENT 'Thời gian làm bài (phút)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tạo bảng câu hỏi trong bài kiểm tra
CREATE TABLE IF NOT EXISTS test_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_id INT NOT NULL,
    question_id INT NOT NULL,
    question_order INT NOT NULL,
    FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES math_questions(id) ON DELETE CASCADE
);

-- Tạo bảng kết quả kiểm tra
CREATE TABLE IF NOT EXISTS test_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    test_id INT NOT NULL,
    score FLOAT NOT NULL COMMENT 'Điểm số trên 10',
    completion_time INT NOT NULL COMMENT 'Thời gian hoàn thành (giây)',
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE
);

-- Tạo bảng câu trả lời của người dùng
CREATE TABLE IF NOT EXISTS user_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_result_id INT NOT NULL,
    question_id INT NOT NULL,
    user_answer CHAR(1) COMMENT 'A, B, C hoặc D',
    is_correct BOOLEAN NOT NULL,
    FOREIGN KEY (test_result_id) REFERENCES test_results(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES math_questions(id) ON DELETE CASCADE
);

-- Tạo bảng huy hiệu
CREATE TABLE IF NOT EXISTS badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    image_path VARCHAR(255) NOT NULL
);

-- Tạo bảng người dùng - huy hiệu
CREATE TABLE IF NOT EXISTS user_badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    badge_id INT NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
);

-- Chèn dữ liệu mẫu cho bảng huy hiệu
INSERT INTO badges (name, description, image_path) VALUES
('Nhà toán học tập sự', 'Hoàn thành bài kiểm tra đầu tiên', 'badges/beginner.png'),
('Siêu sao toán học', 'Đạt điểm 10/10 trong bài kiểm tra', 'badges/star.png'),
('Tia chớp', 'Hoàn thành bài kiểm tra trong thời gian ngắn', 'badges/lightning.png'),
('Người kiên trì', 'Hoàn thành 5 bài kiểm tra', 'badges/persistent.png'),
('Bậc thầy toán học', 'Đạt điểm tối đa trong 3 bài kiểm tra liên tiếp', 'badges/master.png');

-- Chèn dữ liệu mẫu cho tài khoản Admin
INSERT INTO users (id, username, email, password, first_name, last_name, grade, profile_image, is_admin) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$8tFHL8Rl9FmgRV7n9BgO2eMmjhVjL0mYfGQQxJXRZzr7ZVtqevqDm', 'Admin', 'User', 5, 'default_avatar.png', 1);
-- Mật khẩu mặc định: admin123

-- Chèn dữ liệu mẫu cho câu hỏi toán (lớp 1)
INSERT INTO math_questions (question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, grade_level, difficulty, topic) VALUES
('5 + 3 = ?', '7', '8', '9', '10', 'B', '5 cộng với 3 bằng 8', 1, 'easy', 'phép cộng'),
('10 - 4 = ?', '4', '5', '6', '7', 'C', '10 trừ đi 4 bằng 6', 1, 'easy', 'phép trừ'),
('2 + 2 + 2 = ?', '4', '6', '8', '10', 'B', '2 cộng 2 cộng 2 bằng 6', 1, 'easy', 'phép cộng'),
('5 + 5 = ?', '5', '10', '15', '20', 'B', '5 cộng với 5 bằng 10', 1, 'easy', 'phép cộng'),
('8 - 3 = ?', '3', '4', '5', '6', 'C', '8 trừ đi 3 bằng 5', 1, 'easy', 'phép trừ');

-- Chèn dữ liệu mẫu cho câu hỏi toán (lớp 2)
INSERT INTO math_questions (question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, grade_level, difficulty, topic) VALUES
('12 + 8 = ?', '18', '19', '20', '21', 'C', '12 cộng với 8 bằng 20', 2, 'easy', 'phép cộng'),
('15 - 7 = ?', '5', '6', '7', '8', 'D', '15 trừ đi 7 bằng 8', 2, 'easy', 'phép trừ'),
('3 × 4 = ?', '7', '10', '12', '15', 'C', '3 nhân với 4 bằng 12', 2, 'medium', 'phép nhân'),
('10 ÷ 2 = ?', '4', '5', '6', '8', 'B', '10 chia cho 2 bằng 5', 2, 'medium', 'phép chia'),
('9 + 9 = ?', '16', '17', '18', '19', 'C', '9 cộng với 9 bằng 18', 2, 'easy', 'phép cộng');

-- Tạo bài kiểm tra mẫu cho lớp 1
INSERT INTO tests (title, description, grade_level, time_limit) VALUES
('Bài kiểm tra Cộng Trừ - Lớp 1', 'Bài kiểm tra về phép cộng và phép trừ cơ bản cho học sinh lớp 1', 1, 10);

-- Thêm câu hỏi vào bài kiểm tra lớp 1
INSERT INTO test_questions (test_id, question_id, question_order) VALUES
(1, 1, 1),
(1, 2, 2),
(1, 3, 3),
(1, 4, 4),
(1, 5, 5);

-- Tạo bài kiểm tra mẫu cho lớp 2
INSERT INTO tests (title, description, grade_level, time_limit) VALUES
('Bài kiểm tra Cộng Trừ Nhân Chia - Lớp 2', 'Bài kiểm tra tổng hợp 4 phép tính cơ bản cho học sinh lớp 2', 2, 15);

-- Thêm câu hỏi vào bài kiểm tra lớp 2
INSERT INTO test_questions (test_id, question_id, question_order) VALUES
(2, 6, 1),
(2, 7, 2),
(2, 8, 3),
(2, 9, 4),
(2, 10, 5);