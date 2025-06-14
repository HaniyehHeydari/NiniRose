<?php
include('../../config/db.php');
include_once dirname(__DIR__) . '/../Config/config.php';
require '../../vendor/autoload.php';

use Morilog\Jalali\Jalalian;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('شناسه مقاله نامعتبر است.');
}

$article_id = intval($_GET['id']);

// ابتدا سعی می‌کنیم با store_id ارتباط برقرار کنیم
$stmt = $conn->prepare("SELECT a.*, s.name AS store_name FROM articles a LEFT JOIN stores s ON a.store_id = s.id WHERE a.id = ?");

// اگر خطا داد، با author_id امتحان می‌کنیم
if (!$stmt) {
    $stmt = $conn->prepare("SELECT a.*, s.name AS store_name FROM articles a LEFT JOIN stores s ON a.author_id = s.id WHERE a.id = ?");
}

$stmt->bind_param("i", $article_id);
$stmt->execute();
$result = $stmt->get_result();
$article = $result->fetch_assoc();

if (!$article) {
    die('مقاله‌ای با این مشخصات یافت نشد.');
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <title><?= htmlspecialchars($article['title']) ?> | مقاله</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
</head>

<body>
    <?php include('../../Templates/Header.php') ?>

    <div class="container my-5 px-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-sm">
                    <?php if (!empty($article['image'])): ?>
                        <img src="../../<?= htmlspecialchars($article['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($article['title']) ?>" style="max-height: 400px; object-fit: cover;">
                    <?php endif; ?>

                    <div class="card-body">
                        <h3 class="card-title mb-3"><?= htmlspecialchars($article['title']) ?></h3>

                        <div class="card-text" style="line-height: 2;">
                            <?= nl2br($article['content']) ?>
                        </div>
                        <hr>
                        <p class="text-muted mb-1">
                            <?php if ($article['store_name']): ?>
                                فروشگاه: <?= htmlspecialchars($article['store_name']) ?>
                            <?php endif; ?>
                        </p>
                        <p class="text-muted mb-1">
                            تاریخ انتشار: <?= Jalalian::fromDateTime($article['created_at'])->format('Y/m/d H:i'); ?>
                        </p>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include('../../Templates/Footer.php') ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>