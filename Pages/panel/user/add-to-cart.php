<?php
session_start();
include('../../../config/db.php');

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'لطفاً ابتدا وارد شوید']));
}

$user_id = $_SESSION['user_id'];
$product_id = (int)($_POST['product_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);
$size = $_POST['size'] ?? null;
$color = $_POST['color'] ?? null;

// اعتبارسنجی
if ($product_id <= 0 || $quantity <= 0) {
    die(json_encode(['success' => false, 'message' => 'مقادیر نامعتبر']));
}

try {
    // بررسی وجود محصول
    $check = $conn->prepare("SELECT id FROM products WHERE id = ?");
    $check->bind_param("i", $product_id);
    $check->execute();
    if (!$check->get_result()->num_rows) {
        die(json_encode(['success' => false, 'message' => 'محصول یافت نشد']));
    }

    // ساخت کوئری دینامیک
    $fields = ['user_id', 'product_id', 'quantity'];
    $values = [$user_id, $product_id, $quantity];
    $types = "iii";

    if ($size !== null) {
        $fields[] = 'size';
        $values[] = $size;
        $types .= "s";
    }

    if ($color !== null) {
        $fields[] = 'color';
        $values[] = $color;
        $types .= "s";
    }

    $placeholders = str_repeat('?,', count($fields) - 1) . '?';
    $sql = "INSERT INTO carts (" . implode(',', $fields) . ") VALUES ($placeholders) 
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'محصول به سبد اضافه شد']);
    exit; // این خط را اضافه کنید

} catch (mysqli_sql_exception $e) {
    die(json_encode(['success' => false, 'message' => 'خطای پایگاه داده: ' . $e->getMessage()]));
}
