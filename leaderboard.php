<?php
// Trang bảng xếp hạng
require_once 'includes/config.php';

// Lọc theo lớp
$grade_filter = isset($_GET['grade']) ? intval($_GET['grade']) : 0;

// Xây dựng câu lệnh truy vấn với bộ lọc
$where_clause = '';
$params = [];
$types = '';

if ($grade_filter > 0) {
    $where_clause = 'WHERE u.grade = ?';
    $params[] = $grade_filter;
    $types = 'i';
}

// Lấy danh sách người dùng có điểm cao nhất
$query = "
    SELECT u.id, u.username, u.first_name, u.last_name, u.grade, u.profile_image, 
           ROUND(AVG(tr.score), 1) as avg_score,
           COUNT(tr.id) as test_count,
           (SELECT COUNT(*) FROM user_badges WHERE user_id = u.id) as badge_count
    FROM users u
    LEFT JOIN test_results tr ON u.id = tr.user_id
    $where_clause
    GROUP BY u.id
    HAVING test_count > 0
    ORDER BY avg_score DESC, test_count DESC
    LIMIT 50
";

$stmt = mysqli_prepare($conn, $query);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$leaderboard_result = mysqli_stmt_get_result($stmt);

// Bao gồm header
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1>Bảng xếp hạng</h1>
        <p class="lead">Xem ai đang dẫn đầu trong việc học toán!</p>
    </div>
    <div class="col-md-4 text-end">
        <div class="btn-group" role="group">
            <a href="leaderboard.php" class="btn <?php echo ($grade_filter === 0) ? 'btn-primary' : 'btn-outline-primary'; ?>">
                Tất cả
            </a>
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <a href="leaderboard.php?grade=<?php echo $i; ?>" class="btn <?php echo ($grade_filter === $i) ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    Lớp <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="top-students-title">Top học sinh xuất sắc</h3>
    </div>
    <div class="card-body">
        <?php if (mysqli_num_rows($leaderboard_result) > 0): ?>
            <?php 
            $rank = 1;
            while ($user = mysqli_fetch_assoc($leaderboard_result)): 
                $rank_class = '';
                $rank_badge = '';
                
                if ($rank === 1) {
                    $rank_class = 'gold-rank';
                    $rank_badge = '<i class="fas fa-crown text-warning"></i>';
                } elseif ($rank === 2) {
                    $rank_class = 'silver-rank';
                    $rank_badge = '<i class="fas fa-medal" style="color: #c0c0c0;"></i>';
                } elseif ($rank === 3) {
                    $rank_class = 'bronze-rank';
                    $rank_badge = '<i class="fas fa-medal" style="color: #cd7f32;"></i>';
                }
            ?>
                <div class="leaderboard-item <?php echo $rank_class; ?>">
                    <div class="leaderboard-rank">
                        <?php echo $rank_badge ?: $rank; ?>
                    </div>
                    <img src="images/<?php echo $user['profile_image']; ?>" class="leaderboard-avatar" alt="Avatar" onerror="this.src='https://via.placeholder.com/50x50?text=User'">
                    <div class="leaderboard-info">
                        <div class="leaderboard-name">
                            <?php 
                                if (!empty($user['first_name']) && !empty($user['last_name'])) {
                                    echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
                                } else {
                                    echo htmlspecialchars($user['username']);
                                }
                            ?>
                        </div>
                        <div class="leaderboard-grade">
                            Lớp <?php echo $user['grade']; ?> | 
                            <?php echo $user['test_count']; ?> bài kiểm tra | 
                            <?php echo $user['badge_count']; ?> huy hiệu
                        </div>
                    </div>
                    <div class="leaderboard-score">
                        <?php echo $user['avg_score']; ?>
                    </div>
                </div>
            <?php 
                $rank++;
                endwhile; 
            ?>
        <?php else: ?>
            <div class="text-center py-5">
                <img src="images/empty_leaderboard.svg" alt="Không có dữ liệu" height="150" class="mb-3" onerror="this.src='https://via.placeholder.com/150x150?text=No+Data'">
                <p>Chưa có dữ liệu bảng xếp hạng. Hãy hoàn thành một số bài kiểm tra để xuất hiện tại đây!</p>
                <a href="tests.php" class="btn btn-primary">Làm bài kiểm tra ngay</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Thông tin bổ sung -->
<div class="row mt-4">
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h3>Cách tính điểm</h3>
            </div>
            <div class="card-body">
                <p>Điểm trên bảng xếp hạng được tính như sau:</p>
                <ul>
                    <li>Điểm trung bình của tất cả các bài kiểm tra bạn đã hoàn thành</li>
                    <li>Mỗi câu hỏi đúng sẽ được điểm theo thang điểm 10</li>
                    <li>Bảng xếp hạng được cập nhật ngay sau khi bạn hoàn thành bài kiểm tra</li>
                </ul>
                <p>Hoàn thành nhiều bài kiểm tra với điểm số cao để cải thiện thứ hạng của bạn!</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h3>Huy hiệu</h3>
            </div>
            <div class="card-body">
                <p>Mỗi huy hiệu thể hiện một thành tích đặc biệt:</p>
                <ul>
                    <li><strong>Nhà toán học tập sự</strong> - Hoàn thành bài kiểm tra đầu tiên</li>
                    <li><strong>Siêu sao toán học</strong> - Đạt điểm 10/10 trong bài kiểm tra</li>
                    <li><strong>Tia chớp</strong> - Hoàn thành bài kiểm tra trong thời gian ngắn</li>
                    <li><strong>Người kiên trì</strong> - Hoàn thành 5 bài kiểm tra</li>
                    <li><strong>Bậc thầy toán học</strong> - Đạt điểm tối đa trong 3 bài kiểm tra liên tiếp</li>
                </ul>
                <p>Thu thập tất cả huy hiệu để chứng minh bạn là nhà toán học xuất sắc nhất!</p>
            </div>
        </div>
    </div>
</div>

<style>
.gold-rank {
    background-color: rgba(255, 215, 0, 0.1);
    border: 2px solid rgba(255, 215, 0, 0.5);
}

.silver-rank {
    background-color: rgba(192, 192, 192, 0.1);
    border: 2px solid rgba(192, 192, 192, 0.5);
}

.bronze-rank {
    background-color: rgba(205, 127, 50, 0.1);
    border: 2px solid rgba(205, 127, 50, 0.5);
}

.top-students-title {
    color: #ffffff; /* Bootstrap primary blue color */
}

.card-header h3 {
    color: #ffffff; /* Màu chữ đậm cho tiêu đề Cách tính điểm, Huy hiệu */
}
</style>

<?php
// Bao gồm footer
include 'includes/footer.php';
?>