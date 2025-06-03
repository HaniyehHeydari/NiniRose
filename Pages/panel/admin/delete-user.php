<?php
session_start();
include('../../../config/db.php');

// بررسی نقش برای امنیت (فقط super_admin اجازه حذف دارد)
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'super_admin') {
    die("دسترسی غیرمجاز");
}

// بررسی ارسال user_id
if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
    $_SESSION['error_message'] = "شناسه کاربری ارسال نشده است.";
    header("Location: manage-users.php");
    exit;
}

$user_id = intval($_POST['user_id']);

// حذف کاربر با استفاده از Prepared Statement
$sql = "DELETE FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "کاربر با موفقیت حذف شد.";
    $stmt->close();
    $conn->close();
    header("Location: manage-users.php");
    exit;
} else {
    $_SESSION['error_message'] = "خطا در حذف کاربر: " . $conn->error;
    $stmt->close();
    $conn->close();
    header("Location: manage-users.php");
    exit;
}
