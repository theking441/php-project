<?php
// Trang Bảng điều khiển
require_once 'includes/config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Vui lòng đăng nhập để truy cập bảng điều khiển';
    $_SESSION['message_type'] = 'warning';
    header('Location: login.php');
    exit;
}

// Lấy thông tin người dùng
$user_id = $_SESSION['user_id'];
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($user_result);

// Lấy số bài kiểm tra đã hoàn thành
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as completed_tests FROM test_results WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$count_result = mysqli_stmt_get_result($stmt);
$test_count = mysqli_fetch_assoc($count_result);
$completed_tests = $test_count['completed_tests'];

// Lấy điểm trung bình
$avg_score = 0;
if ($completed_tests > 0) {
    $stmt = mysqli_prepare($conn, "SELECT AVG(score) as avg_score FROM test_results WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $avg_result = mysqli_stmt_get_result($stmt);
    $score_data = mysqli_fetch_assoc($avg_result);
    $avg_score = round($score_data['avg_score'], 1);
}

// Lấy số huy hiệu đã đạt được
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as badge_count FROM user_badges WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$badge_result = mysqli_stmt_get_result($stmt);
$badge_data = mysqli_fetch_assoc($badge_result);
$badge_count = $badge_data['badge_count'];

// Lấy các bài kiểm tra gần đây
$stmt = mysqli_prepare($conn, "
    SELECT r.*, t.title, t.grade_level 
    FROM test_results r
    JOIN tests t ON r.test_id = t.id
    WHERE r.user_id = ?
    ORDER BY r.completed_at DESC
    LIMIT 5
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$recent_tests_result = mysqli_stmt_get_result($stmt);

// Lấy danh sách huy hiệu của người dùng
$stmt = mysqli_prepare($conn, "
    SELECT b.* 
    FROM badges b
    JOIN user_badges ub ON b.id = ub.badge_id
    WHERE ub.user_id = ?
    ORDER BY ub.earned_at DESC
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$badges_result = mysqli_stmt_get_result($stmt);

// Lấy bài kiểm tra được đề xuất dựa trên lớp của người dùng
$stmt = mysqli_prepare($conn, "
    SELECT t.* 
    FROM tests t
    WHERE t.grade_level = ?
    AND t.id NOT IN (
        SELECT test_id FROM test_results WHERE user_id = ?
    )
    LIMIT 3
");
mysqli_stmt_bind_param($stmt, "ii", $user['grade'], $user_id);
mysqli_stmt_execute($stmt);
$recommended_tests_result = mysqli_stmt_get_result($stmt);

// Bao gồm header
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h1 class="mb-3">Xin chào, <?php echo htmlspecialchars($user['first_name'] ?: $user['username']); ?>!</h1>
        <p class="lead">Chào mừng trở lại với Math Friends. Hãy xem tiến độ học tập của bạn nhé!</p>
    </div>
</div>

<!-- Thống kê -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-pencil-alt"></i>
            </div>
            <div class="stat-number"><?php echo $completed_tests; ?></div>
            <div class="stat-label">Bài kiểm tra đã hoàn thành</div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-number"><?php echo $avg_score; ?></div>
            <div class="stat-label">Điểm trung bình</div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-award"></i>
            </div>
            <div class="stat-number"><?php echo $badge_count; ?></div>
            <div class="stat-label">Huy hiệu đã đạt được</div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Bài kiểm tra gần đây -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h3>Bài kiểm tra gần đây</h3>
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($recent_tests_result) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Bài kiểm tra</th>
                                    <th>Điểm</th>
                                    <th>Ngày</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($test = mysqli_fetch_assoc($recent_tests_result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($test['title']); ?></td>
                                        <td>
                                            <span class="badge <?php echo ($test['score'] >= 8) ? 'bg-success' : (($test['score'] >= 6) ? 'bg-warning' : 'bg-danger'); ?>">
                                                <?php echo $test['score']; ?>/10
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($test['completed_at'])); ?></td>
                                        <td>
                                            <a href="results.php?id=<?php echo $test['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="history.php" class="btn btn-outline-primary">Xem tất cả</a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <img src="images/empty_tests.svg" alt="Không có bài kiểm tra" height="120" class="mb-3">
                        <p>Bạn chưa hoàn thành bài kiểm tra nào.</p>
                        <a href="tests.php" class="btn btn-primary">Làm bài kiểm tra ngay</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bài kiểm tra đề xuất -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h3>Bài kiểm tra đề xuất</h3>
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($recommended_tests_result) > 0): ?>
                    <div class="row">
                        <?php while ($test = mysqli_fetch_assoc($recommended_tests_result)): ?>
                            <div class="col-md-12 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($test['title']); ?></h5>
                                        <p class="card-text small">
                                            <span class="badge bg-info">Lớp <?php echo $test['grade_level']; ?></span>
                                            <span class="badge bg-secondary"><?php echo $test['time_limit']; ?> phút</span>
                                        </p>
                                        <p class="card-text"><?php echo htmlspecialchars(substr($test['description'], 0, 100) . (strlen($test['description']) > 100 ? '...' : '')); ?></p>
                                        <a href="test.php?id=<?php echo $test['id']; ?>" class="btn btn-primary">Làm bài kiểm tra</a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="tests.php" class="btn btn-outline-primary">Xem tất cả</a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <img src="images/completed_tests.svg" alt="Đã hoàn thành" height="120" class="mb-3">
                        <p>Bạn đã hoàn thành tất cả bài kiểm tra cho lớp của mình!</p>
                        <a href="tests.php" class="btn btn-primary">Xem tất cả bài kiểm tra</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Huy hiệu -->
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h3>Huy hiệu của tôi</h3>
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($badges_result) > 0): ?>
                    <div class="row">
                        <?php while ($badge = mysqli_fetch_assoc($badges_result)): ?>
                            <div class="col-md-2 col-sm-4 col-6">
                                <div class="badge-card">
                                    <img src="images/<?php echo $badge['image_path']; ?>" alt="<?php echo htmlspecialchars($badge['name']); ?>" class="badge-img">
                                    <div class="badge-name"><?php echo htmlspecialchars($badge['name']); ?></div>
                                    <div class="badge-desc"><?php echo htmlspecialchars($badge['description']); ?></div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <img src="images/empty_badges.svg" alt="Không có huy hiệu" height="120" class="mb-3">
                        <p>Bạn chưa đạt được huy hiệu nào. Hãy hoàn thành các bài kiểm tra để nhận huy hiệu!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Bao gồm footer
include 'includes/footer.php';
?>