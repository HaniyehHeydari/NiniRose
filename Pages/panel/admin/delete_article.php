<?php
session_start();
include('../../../config/db.php');

// بررسی نقش کاربر
if (!isset($_SESSION['user']['role']) || !in_array($_SESSION['user']['role'], ['super_admin', 'store_admin'])) {
    die("دسترسی غیرمجاز");
}

$article_id = $_GET['id'] ?? null;

if ($article_id && is_numeric($article_id)) {
    // دریافت مسیر تصویر مقاله برای حذف فایل از سرور
    $stmt = $conn->prepare("SELECT image FROM articles WHERE id = ?");
    $stmt->bind_param("i", $article_id);
    $stmt->execute();
    $stmt->bind_result($image_path);
    $stmt->fetch();
    $stmt->close();

    // حذف مقاله
    $stmt = $conn->prepare("DELETE FROM articles WHERE id = ?");
    $stmt->bind_param("i", $article_id);
    if ($stmt->execute()) {
        // حذف فایل تصویر از روی سرور اگر وجود داشته باشد
        if ($image_path && file_exists('../../../' . $image_path)) {
            unlink('../../../' . $image_path);
        }
        $_SESSION['success'] = "مقاله با موفقیت حذف شد.";
    } else {
        $_SESSION['error'] = "خطا در حذف مقاله.";
    }
    $stmt->close();
} else {
    $_SESSION['error'] = "شناسه مقاله نامعتبر است.";
}

header("Location: manage_articles.php");
exit;
