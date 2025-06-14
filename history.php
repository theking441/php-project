<?php
require_once 'includes/config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Vui lòng đăng nhập để xem lịch sử kiểm tra';
    $_SESSION['message_type'] = 'warning';
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Lấy quyền admin
$stmt = mysqli_prepare($conn, "SELECT is_admin FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
$is_admin = $user['is_admin'] ?? 0;

// Lấy lịch sử kiểm tra
if ($is_admin) {
    $stmt = mysqli_prepare($conn, "
        SELECT tr.id, u.username, t.title, t.grade_level, tr.score, tr.completion_time, tr.completed_at
        FROM test_results tr
        JOIN tests t ON tr.test_id = t.id
        JOIN users u ON tr.user_id = u.id
        ORDER BY tr.completed_at DESC
    ");
} else {
    $stmt = mysqli_prepare($conn, "
        SELECT tr.id, t.title, t.grade_level, tr.score, tr.completion_time, tr.completed_at
        FROM test_results tr
        JOIN tests t ON tr.test_id = t.id
        WHERE tr.user_id = ?
        ORDER BY tr.completed_at DESC
    ");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
}

mysqli_stmt_execute($stmt);
$results = mysqli_stmt_get_result($stmt);

// Header
include 'includes/header.php';
?>

<div class="container mt-5">
    <h1 class="mb-4">Lịch sử kiểm tra</h1>

    <?php if (mysqli_num_rows($results) > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-primary">
                    <tr>
                        <th>Tiêu đề bài kiểm tra</th>
                        <th>Lớp</th>
                        <?php if ($is_admin): ?>
                            <th>Người dùng</th>
                        <?php endif; ?>
                        <th>Điểm</th>
                        <th>Thời gian làm</th>
                        <th>Ngày hoàn thành</th>
                        <th>Chi tiết</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($results)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td>Lớp <?php echo $row['grade_level']; ?></td>
                            <?php if ($is_admin): ?>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <?php endif; ?>
                            <td><?php echo number_format($row['score'], 1); ?>/10</td>
                            <td>
                                <?php
                                    $minutes = floor($row['completion_time'] / 60);
                                    $seconds = $row['completion_time'] % 60;
                                    echo sprintf('%02d:%02d', $minutes, $seconds);
                                ?>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['completed_at'])); ?></td>
                            <td>
                                <a href="results.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">
                                    Xem
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-warning text-center">
            Chưa có kết quả bài kiểm tra nào được ghi nhận.
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
