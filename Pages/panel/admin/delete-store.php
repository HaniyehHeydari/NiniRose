<?php
session_start();
include('../../../config/db.php');

// فقط سوپر ادمین اجازه حذف دارد
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'super_admin') {
    die("دسترسی غیرمجاز");
}

if (!isset($_POST['store_id']) || empty($_POST['store_id'])) {
    $_SESSION['error_message'] = "شناسه فروشگاه ارسال نشده است.";
    header("Location: manage-stores.php");
    exit;
}

$store_id = intval($_POST['store_id']);

// حذف محصولات مربوط به فروشگاه
$delete_products = $conn->prepare("DELETE FROM products WHERE store_id = ?");
$delete_products->bind_param("i", $store_id);
$delete_products->execute();
$delete_products->close();

// حذف دسته‌بندی‌های مربوط به فروشگاه
$delete_categories = $conn->prepare("DELETE FROM categories WHERE store_id = ?");
$delete_categories->bind_param("i", $store_id);
$delete_categories->execute();
$delete_categories->close();

// تغییر نقش کاربران آن فروشگاه و حذف store_id
$update_users = $conn->prepare("UPDATE users SET role = 'user', store_id = NULL WHERE role = 'store_admin' AND store_id = ?");
$update_users->bind_param("i", $store_id);
$update_users->execute();
$update_users->close();

// در نهایت حذف فروشگاه
$delete_store = $conn->prepare("DELETE FROM stores WHERE id = ?");
$delete_store->bind_param("i", $store_id);

if ($delete_store->execute()) {
    $_SESSION['success_message'] = "فروشگاه با موفقیت حذف شد.";
} else {
    $_SESSION['error_message'] = "خطا در حذف فروشگاه: " . $conn->error;
}

$delete_store->close();
$conn->close();

header("Location: manage-stores.php");
exit;
?>
