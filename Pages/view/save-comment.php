<?php
session_start();
include('../../config/db.php'); // مسیر صحیح به فایل config/db.php

if (!isset($_SESSION['user']['id'])) {
    header("Location: ../../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['product_id']) || empty(trim($_POST['content']))) {
    header("Location: product-detail.php?id=" . intval($_POST['product_id'] ?? $_GET['product_id']));
    exit();
}

$user_id    = $_SESSION['user']['id'];
$product_id = intval($_POST['product_id']);
$content    = trim($_POST['content']);
$parent_id  = (isset($_POST['parent_id']) && intval($_POST['parent_id']) > 0)
                ? intval($_POST['parent_id'])
                : null;

// درج کامنت یا ریپلای در جدول comments
$sql = "INSERT INTO comments (user_id, product_id, content, parent_id) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iisi", $user_id, $product_id, $content, $parent_id);

if ($stmt->execute()) {
    $_SESSION['success_comment'] = true;
} else {
    $_SESSION['error_comment'] = "خطا در ثبت نظر.";
}

$stmt->close();
header("Location: product-detail.php?id=$product_id");
exit();
