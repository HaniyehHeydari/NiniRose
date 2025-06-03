<?php
session_start();
include('../../../config/db.php');

// فقط سوپر ادمین حق دارد
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'super_admin') {
    $_SESSION['error_message'] = "دسترسی غیرمجاز.";
    header("Location: manage-stores.php");
    exit;
}

$store_id = $_POST['store_id'] ?? null;
$admin_id = $_POST['admin_id'] ?? null;

if (!$store_id || !$admin_id) {
    $_SESSION['error_message'] = "اطلاعات ناقص ارسال شده است.";
    header("Location: manage-stores.php");
    exit;
}

// ثبت تاریخ فعلی برای تأیید
$approvedAt = date('Y-m-d H:i:s');

// استفاده از پرس‌وجوی آماده برای بروزرسانی فروشگاه
$updateStore = $conn->prepare("UPDATE stores SET status = 1, approved_at = ? WHERE id = ?");
$updateStore->bind_param("si", $approvedAt, $store_id);

// استفاده از پرس‌وجوی آماده برای بروزرسانی نقش کاربر
$updateUser = $conn->prepare("UPDATE users SET role = 'store_admin', store_id = ? WHERE id = ?");
$updateUser->bind_param("ii", $store_id, $admin_id);

if ($updateStore->execute() && $updateUser->execute()) {
    $_SESSION['success_message'] = "فروشگاه با موفقیت تایید شد.";
} else {
    $_SESSION['error_message'] = "خطا در تایید فروشگاه.";
}

$updateStore->close();
$updateUser->close();
$conn->close();

header("Location: manage-stores.php");
exit;
