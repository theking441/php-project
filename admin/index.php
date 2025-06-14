<?php
// Trang quản trị
require_once '../includes/config.php';

// Kiểm tra đăng nhập và quyền quản trị
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    $_SESSION['message'] = 'Bạn không có quyền truy cập trang quản trị';
    $_SESSION['message_type'] = 'danger';
    header('Location: ../index.php');
    exit;
}

// Bao gồm header quản trị
include 'includes/admin_header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h1>Bảng điều khiển quản trị</h1>
        <p class="lead">Quản lý người dùng, câu hỏi và bài kiểm tra từ đây.</p>
    </div>
</div>

<div class="row">
    <!-- Tổng quan người dùng -->
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h3>Người dùng</h3>
            </div>
            <div class="card-body">
                <?php
                // Lấy thông tin người dùng
                $user_query = "SELECT COUNT(*) as total, 
                                      SUM(CASE WHEN is_admin = 1 THEN 1 ELSE 0 END) as admin_count 
                               FROM users";
                $user_result = mysqli_query($conn, $user_query);
                $user_data = mysqli_fetch_assoc($user_result);
                
                // Thống kê theo lớp
                $grade_query = "SELECT grade, COUNT(*) as count FROM users WHERE grade BETWEEN 1 AND 5 GROUP BY grade ORDER BY grade";
                $grade_result = mysqli_query($conn, $grade_query);
                ?>
                
                <div class="admin-stat mb-3">
                    <div class="admin-stat-value"><?php echo $user_data['total']; ?></div>
                    <div class="admin-stat-label">Tổng số người dùng</div>
                </div>
                
                <div class="admin-stat mb-3">
                    <div class="admin-stat-value"><?php echo $user_data['admin_count']; ?></div>
                    <div class="admin-stat-label">Quản trị viên</div>
                </div>
                
                <h5 class="mt-4">Học sinh theo lớp:</h5>
                <div class="grade-stats">
                    <?php while ($grade = mysqli_fetch_assoc($grade_result)): ?>
                        <div class="grade-stat-item">
                            <div class="grade-label">Lớp <?php echo $grade['grade']; ?></div>
                            <div class="grade-value"><?php echo $grade['count']; ?></div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <div class="card-footer">
                <a href="manage_users.php" class="btn btn-primary w-100">Quản lý người dùng</a>
            </div>
        </div>
    </div>
    
    <!-- Tổng quan câu hỏi -->
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h3>Câu hỏi</h3>
            </div>
            <div class="card-body">
                <?php
                // Lấy thông tin câu hỏi
                $question_query = "SELECT COUNT(*) as total FROM math_questions";
                $question_result = mysqli_query($conn, $question_query);
                $question_data = mysqli_fetch_assoc($question_result);
                
                // Thống kê theo lớp
                $grade_question_query = "SELECT grade_level, COUNT(*) as count FROM math_questions GROUP BY grade_level ORDER BY grade_level";
                $grade_question_result = mysqli_query($conn, $grade_question_query);
                
                // Thống kê theo chủ đề
                $topic_query = "SELECT topic, COUNT(*) as count FROM math_questions GROUP BY topic ORDER BY count DESC LIMIT 5";
                $topic_result = mysqli_query($conn, $topic_query);
                ?>
                
                <div class="admin-stat mb-3">
                    <div class="admin-stat-value"><?php echo $question_data['total']; ?></div>
                    <div class="admin-stat-label">Tổng số câu hỏi</div>
                </div>
                
                <h5 class="mt-4">Câu hỏi theo lớp:</h5>
                <div class="grade-stats">
                    <?php while ($grade = mysqli_fetch_assoc($grade_question_result)): ?>
                        <div class="grade-stat-item">
                            <div class="grade-label">Lớp <?php echo $grade['grade_level']; ?></div>
                            <div class="grade-value"><?php echo $grade['count']; ?></div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <h5 class="mt-4">Chủ đề phổ biến:</h5>
                <ul class="topic-list">
                    <?php while ($topic = mysqli_fetch_assoc($topic_result)): ?>
                        <li><?php echo htmlspecialchars($topic['topic']); ?> (<?php echo $topic['count']; ?>)</li>
                    <?php endwhile; ?>
                </ul>
            </div>
            <div class="card-footer">
                <a href="manage_questions.php" class="btn btn-primary w-100">Quản lý câu hỏi</a>
            </div>
        </div>
    </div>
    
    <!-- Tổng quan bài kiểm tra -->
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h3>Bài kiểm tra</h3>
            </div>
            <div class="card-body">
                <?php
                // Lấy thông tin bài kiểm tra
                $test_query = "SELECT COUNT(*) as total FROM tests";
                $test_result = mysqli_query($conn, $test_query);
                $test_data = mysqli_fetch_assoc($test_result);
                
                // Lấy kết quả bài kiểm tra
                $result_query = "SELECT COUNT(*) as total, AVG(score) as avg_score FROM test_results";
                $result_result = mysqli_query($conn, $result_query);
                $result_data = mysqli_fetch_assoc($result_result);
                
                // Thống kê theo lớp
                $grade_test_query = "SELECT grade_level, COUNT(*) as count FROM tests GROUP BY grade_level ORDER BY grade_level";
                $grade_test_result = mysqli_query($conn, $grade_test_query);
                ?>
                
                <div class="admin-stat mb-3">
                    <div class="admin-stat-value"><?php echo $test_data['total']; ?></div>
                    <div class="admin-stat-label">Tổng số bài kiểm tra</div>
                </div>
                
                <div class="admin-stat mb-3">
                    <div class="admin-stat-value"><?php echo $result_data['total']; ?></div>
                    <div class="admin-stat-label">Bài kiểm tra đã hoàn thành</div>
                </div>
                
                <div class="admin-stat mb-3">
                    <div class="admin-stat-value"><?php echo number_format($result_data['avg_score'], 1); ?></div>
                    <div class="admin-stat-label">Điểm trung bình</div>
                </div>
                
                <h5 class="mt-4">Bài kiểm tra theo lớp:</h5>
                <div class="grade-stats">
                    <?php while ($grade = mysqli_fetch_assoc($grade_test_result)): ?>
                        <div class="grade-stat-item">
                            <div class="grade-label">Lớp <?php echo $grade['grade_level']; ?></div>
                            <div class="grade-value"><?php echo $grade['count']; ?></div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <div class="card-footer">
                <a href="manage_tests.php" class="btn btn-primary w-100">Quản lý bài kiểm tra</a>
            </div>
        </div>
    </div>
</div>

<!-- Hoạt động gần đây -->
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h3>Hoạt động gần đây</h3>
            </div>
            <div class="card-body">
                <?php
                // Lấy kết quả kiểm tra gần đây
                $recent_activity_query = "
                    SELECT r.*, t.title, u.username, u.profile_image 
                    FROM test_results r
                    JOIN tests t ON r.test_id = t.id
                    JOIN users u ON r.user_id = u.id
                    ORDER BY r.completed_at DESC
                    LIMIT 10
                ";
                $recent_activity_result = mysqli_query($conn, $recent_activity_query);
                ?>
                
                <?php if (mysqli_num_rows($recent_activity_result) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Người dùng</th>
                                    <th>Bài kiểm tra</th>
                                    <th>Điểm</th>
                                    <th>Thời gian</th>
                                    <th>Ngày</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($activity = mysqli_fetch_assoc($recent_activity_result)): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="../images/<?php echo $activity['profile_image']; ?>" class="avatar-img me-2" alt="Avatar">
                                                <?php echo htmlspecialchars($activity['username']); ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($activity['title']); ?></td>
                                        <td>
                                            <span class="badge <?php echo ($activity['score'] >= 8) ? 'bg-success' : (($activity['score'] >= 6) ? 'bg-warning' : 'bg-danger'); ?>">
                                                <?php echo $activity['score']; ?>/10
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                                $minutes = floor($activity['completion_time'] / 60);
                                                $seconds = $activity['completion_time'] % 60;
                                                echo sprintf('%02d:%02d', $minutes, $seconds);
                                            ?>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($activity['completed_at'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">Chưa có hoạt động nào.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Bao gồm footer quản trị
include 'includes/admin_footer.php';
?>