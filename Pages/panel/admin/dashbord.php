<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION['user']['role'] ?? 'store_admin';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>dashbord</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../../Public/css/dashbord.css" />
</head>

<body>

    <!-- Sidebar -->
    <nav class="sidebar d-flex flex-column">
        <a href="../../view/MainPage.php" class="nav-link active">
            <i class="bi bi-house-door"></i> صفحه اصلی
        </a>

        <?php if ($_SESSION['user']['role'] === 'super_admin'): ?>
            <a href="manage-users.php" class="nav-link">
                <i class="bi bi-people"></i> کاربران
            </a>
            <a href="manage-stores.php" class="nav-link">
                <i class="bi bi-shop"></i> فروشگاه‌ها
            </a>
        <?php endif; ?>

        <a href="manage-categories.php" class="nav-link">
            <i class="bi bi-tags"></i> دسته‌بندی ها
        </a>
        <a href="manage-products.php" class="nav-link">
            <i class="bi bi-box-seam"></i> محصولات
        </a>
        <a href="manage-orders.php" class="nav-link">
            <i class="bi bi-basket"></i> سفارشات
        </a>

        <?php if ($_SESSION['user']['role'] === 'super_admin'): ?>
            <a href="manage-slider.php" class="nav-link">
                <i class="bi bi-sliders"></i> اسلایدر
            </a>
        <?php endif; ?>

        <a href="manage_articles.php" class="nav-link">
            <i class="bi bi-book"></i> مقالات
        </a>

        <a href="manage-comments.php" class="nav-link">
            <i class="bi bi-chat-left-dots"></i> کامنت‌ها
        </a>

        <a href="reporting.php" class="nav-link">
            <i class="bi-bar-chart-line"></i> گزارش گیری
        </a>
    </nav>

</body>

</html>