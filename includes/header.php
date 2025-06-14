<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nền tảng học toán tiểu học</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/kids_theme.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Bubblegum+Sans&family=Comic+Neue:wght@400;700&display=swap" rel="stylesheet">
    <!-- Favicons -->
    <link rel="icon" href="images/favicon.ico">
    <!-- MathJax for formulas -->
    <script type="text/javascript" async
        src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.7/MathJax.js?config=TeX-MML-AM_CHTML">
    </script>
    <script type="text/x-mathjax-config">
        MathJax.Hub.Config({
            tex2jax: {
                inlineMath: [['$','$'], ['\\(','\\)']],
                displayMath: [['$$','$$'], ['\\[','\\]']],
                processEscapes: true
            },
            CommonHTML: { linebreaks: { automatic: true } },
            "HTML-CSS": { linebreaks: { automatic: true } },
            SVG: { linebreaks: { automatic: true } }
        });
    </script>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="images/logo.svg" alt="Math Friends Logo" height="60" onerror="this.src='https://via.placeholder.com/180x60?text=Math+Friends'">
                <!-- Text logo
                <span class="ms-2 logo-text">Math Friends</span>
                 -->
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home"></i> Trang chủ</a>
                    </li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php"><i class="fas fa-chalkboard"></i> Bảng điều khiển</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="tests.php"><i class="fas fa-pencil-alt"></i> Bài kiểm tra</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="leaderboard.php"><i class="fas fa-trophy"></i> Bảng xếp hạng</a>
                    </li>
                    <li class="nav-item">
    <a class="nav-link" href="history.php"><i class="fas fa-history"></i> Lịch sử</a>
</li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <img src="images/<?php echo isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : 'default_avatar.png'; ?>" class="avatar-img" alt="Avatar" onerror="this.src='https://via.placeholder.com/35x35?text=User'">
                            <?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'Người dùng'; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Hồ sơ</a></li>
                            <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                            <li><a class="dropdown-item" href="admin/index.php"><i class="fas fa-cogs"></i> Quản trị</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php"><i class="fas fa-user-plus"></i> Đăng ký</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="container main-container py-4">
        <!-- Thông báo -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['message']; 
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>