<?php
session_start();
include('../../../config/db.php');

if (!isset($_SESSION['user']['id']) || !isset($_SESSION['checkout_data'])) {
    header("Location: checkout.php");
    exit();
}

$user_id = $_SESSION['user']['id'];

// دریافت سبد خرید کاربر از دیتابیس
$sql = "
    SELECT 
        c.product_id, 
        c.quantity, 
        p.price 
    FROM carts c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart = $result->fetch_all(MYSQLI_ASSOC);

if (empty($cart)) {
    $_SESSION['errors'] = ['سبد خرید خالی است!'];
    header("Location: checkout.php");
    exit();
}

// پردازش هر محصول موجود در سبد به‌صورت جداگانه
foreach ($cart as $item) {
    $product_id  = $item['product_id'];
    $quantity    = $item['quantity'];
    $price       = $item['price'];
    $total_price = $price * $quantity;

    // 1) درج در جدول orders (با ستون quantity)
    $stmt_order = $conn->prepare("
        INSERT INTO orders 
            (user_id, product_id, quantity, total_price, status, created_at) 
        VALUES 
            (?, ?, ?, ?, 0, NOW())
    ");
    $stmt_order->bind_param("iiid", $user_id, $product_id, $quantity, $total_price);
    $stmt_order->execute();
    $order_id = $stmt_order->insert_id;
    $stmt_order->close();

    // 2) درج در جدول order_items (بدون ستون quantity)
    $stmt_item = $conn->prepare("
        INSERT INTO order_items 
            (order_id, product_id) 
        VALUES 
            (?, ?)
    ");
    $stmt_item->bind_param("ii", $order_id, $product_id);
    $stmt_item->execute();
    $stmt_item->close();

    // 3) کاهش موجودی محصول
    $conn->query("UPDATE products SET stock = stock - $quantity WHERE id = $product_id");
}

// پاک کردن سبد خرید از دیتابیس
$conn->query("DELETE FROM carts WHERE user_id = $user_id");
unset($_SESSION['cart']);

$_SESSION['payment_success'] = true;
header("Location: payment.php");
exit();
