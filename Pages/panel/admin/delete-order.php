<?php
session_start();
include('../../../config/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);

    $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "سفارش با موفقیت حذف شد.";
    } else {
        $_SESSION['success_message'] = "حذف سفارش با خطا مواجه شد.";
    }

    $stmt->close();
}

header("Location: manage-orders.php");
exit();
