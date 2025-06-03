<?php
include('../../../config/db.php');


if (!isset($_SESSION['user']['role']) || !in_array($_SESSION['user']['role'], ['super_admin', 'store_admin'])) {
    die("دسترسی غیرمجاز");
}

$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

if (!$category_id) {
    header("Location: manage-categories.php?error=no_id");
    exit;
}

$sql = "DELETE FROM categories WHERE id = $category_id";
if ($conn->query($sql)) {
    header("Location: manage-categories.php?message=deleted");
    exit;
} else {
    die("خطا در حذف دسته‌بندی: " . $conn->error);
}
