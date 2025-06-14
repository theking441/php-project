<?php
// Trang chủ
require_once 'includes/config.php';

// Kiểm tra đăng nhập
$is_logged_in = isset($_SESSION['user_id']);

// Bao gồm header
include 'includes/header.php';
?>

<!-- Hero Section -->
<div class="hero-section">
    <!-- Decorative shapes -->
    <div class="shape-decoration shape-circle circle-1"></div>
    <div class="shape-decoration shape-circle circle-2"></div>
    <div class="shape-decoration shape-square square-1"></div>
    <div class="shape-decoration shape-triangle triangle-1"></div>
    
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 order-lg-1">
                <h1 class="hero-title">
                    Học toán thật <span class="text-primary">vui vẻ</span> 
                    và <span class="text-secondary">dễ dàng!</span>
                </h1>
                <p class="hero-subtitle">
                    Nền tảng học toán trực tuyến dành cho học sinh tiểu học từ lớp 1 
                    đến lớp 5. Với những bài kiểm tra thú vị và nhân vật hoạt hình 
                    đáng yêu!
                </p>
                
                <div class="hero-buttons">
                    <?php if ($is_logged_in): ?>
                        <a href="tests.php" class="btn btn-primary btn-lg"><i class="fas fa-pencil-alt"></i> Bắt đầu kiểm tra</a>
                        <a href="dashboard.php" class="btn btn-secondary btn-lg"><i class="fas fa-chart-line"></i> Bảng điều khiển</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary btn-lg"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a>
                        <a href="register.php" class="btn btn-secondary btn-lg"><i class="fas fa-user-plus"></i> Đăng ký</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6 order-lg-2">
                <img src="images/hero_character.svg" alt="Math Character" class="hero-image" onerror="this.src='https://via.placeholder.com/500x400?text=Math+Friends'">
            </div>
        </div>
    </div>
</div>

<!-- Stats Section -->
<div class="container stats-container">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="stat-item">
                <div class="stat-value">500+</div>
                <div class="stat-label">Học sinh</div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="stat-item">
                <div class="stat-value">1000+</div>
                <div class="stat-label">Câu hỏi</div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="stat-item">
                <div class="stat-value">5</div>
                <div class="stat-label">Cấp độ</div>
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="container mt-5">
    <h2 class="text-center mb-4">Tại sao chọn Math Friends?</h2>
    
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="feature-icon mb-3">
                        <img src="images/feature_test.svg" alt="Bài kiểm tra" height="80" class="character bouncing" onerror="this.src='https://via.placeholder.com/80x80?text=Test'">
                    </div>
                    <h3>Bài kiểm tra vui nhộn</h3>
                    <p>Các bài kiểm tra toán được thiết kế dễ hiểu, phù hợp với từng lớp và đầy màu sắc, giúp việc học trở nên thú vị.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="feature-icon mb-3">
                        <img src="images/feature_badge.svg" alt="Huy hiệu" height="80" class="character floating" onerror="this.src='https://via.placeholder.com/80x80?text=Badge'">
                    </div>
                    <h3>Huy hiệu thành tích</h3>
                    <p>Nhận huy hiệu đặc biệt khi đạt được thành tích trong học tập, tạo động lực để tiến bộ hơn mỗi ngày.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="feature-icon mb-3">
                        <img src="images/feature_chart.svg" alt="Theo dõi" height="80" class="character pulsing" onerror="this.src='https://via.placeholder.com/80x80?text=Progress'">
                    </div>
                    <h3>Theo dõi tiến bộ</h3>
                    <p>Xem biểu đồ tiến bộ học tập, biết được điểm mạnh và điểm yếu để cải thiện kết quả học tập.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Testimonials Section -->
<div class="container mt-5">
    <h2 class="text-center mb-4">Phụ huynh và học sinh nói gì?</h2>
    
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="testimonial-rating text-warning mb-2">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="testimonial-text">"Con tôi rất thích Math Friends! Các bài kiểm tra vui nhộn và những nhân vật hoạt hình đã làm cho việc học toán trở nên thú vị hơn rất nhiều."</p>
                    <div class="testimonial-author">
                        <span class="fw-bold">Chị Hoa</span> - Phụ huynh học sinh lớp 2
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="testimonial-rating text-warning mb-2">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="testimonial-text">"Tôi thích nhận huy hiệu sau mỗi bài kiểm tra! Giờ tôi làm bài tập toán mỗi ngày để kiếm thêm nhiều huy hiệu nữa."</p>
                    <div class="testimonial-author">
                        <span class="fw-bold">Minh</span> - Học sinh lớp 3
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="testimonial-rating text-warning mb-2">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                    <p class="testimonial-text">"Là một giáo viên, tôi thấy Math Friends là công cụ tuyệt vời để khuyến khích học sinh luyện tập thêm ở nhà. Giao diện thân thiện và nội dung phù hợp."</p>
                    <div class="testimonial-author">
                        <span class="fw-bold">Cô Lan</span> - Giáo viên tiểu học
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CTA Section -->
<div class="container mt-5">
    <div class="card bg-primary text-white">
        <div class="card-body p-5 text-center">
            <h2 class="mb-3">Sẵn sàng bắt đầu cuộc phiêu lưu toán học?</h2>
            <p class="mb-4">Đăng ký miễn phí ngay hôm nay và bắt đầu hành trình học toán thú vị!</p>
            
            <?php if ($is_logged_in): ?>
                <a href="tests.php" class="btn btn-light btn-lg"><i class="fas fa-pencil-alt"></i> Bắt đầu làm bài ngay</a>
            <?php else: ?>
                <a href="register.php" class="btn btn-light btn-lg"><i class="fas fa-user-plus"></i> Đăng ký ngay</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Bao gồm footer
include 'includes/footer.php';
?>