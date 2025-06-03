<?php
session_start();
include('../../../config/db.php');

if (!isset($_SESSION['user']['id']) || !isset($_SESSION['checkout_data'])) {
    header("Location: checkout.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // اعتبارسنجی اطلاعات کارت
    if (empty($_POST['card_number']) || !preg_match('/^[0-9]{16}$/', str_replace('-', '', $_POST['card_number']))) {
        $errors[] = "شماره کارت معتبر وارد کنید";
    }

    if (empty($_POST['expiry_date']) || !preg_match('/^(0[1-9]|1[0-2])\/?([0-9]{2})$/', $_POST['expiry_date'])) {
        $errors[] = "تاریخ انقضا معتبر وارد کنید (فرمت: MM/YY)";
    }

    if (empty($_POST['cvv2']) || !preg_match('/^[0-9]{3,4}$/', $_POST['cvv2'])) {
        $errors[] = "CVV2 معتبر وارد کنید";
    }

    if (empty($_POST['dynamic_password']) || strlen($_POST['dynamic_password']) < 4) {
        $errors[] = "رمز دوم پویا معتبر وارد کنید";
    }

    if (!empty($errors)) {
        $_SESSION['payment_errors'] = $errors;
        header("Location: payment.php");
        exit();
    }

    // در اینجا معمولاً به درگاه پرداخت متصل می‌شوید
    // برای مثال ساده، پرداخت موفق در نظر گرفته می‌شود
    
    // ذخیره آدرس در دیتابیس
    $user_id = $_SESSION['user']['id'];
    $address = $_SESSION['checkout_data']['address'];
    
    $sql = "UPDATE users SET address = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $address, $user_id);
    $stmt->execute();
    $stmt->close();

    // ذخیره اطلاعات سفارش در دیتابیس
    $order_data = [
        'user_id' => $user_id,
        'fullname' => $_SESSION['checkout_data']['fullname'],
        'phone' => $_SESSION['checkout_data']['phone'],
        'address' => $address,
        'postal_code' => $_SESSION['checkout_data']['postal_code'],
        'amount' => 100000, // مبلغ پرداخت شده
        'payment_status' => 'completed'
    ];
    
    // کد ذخیره سفارش در دیتابیس...

    // پاک کردن اطلاعات سشن
    unset($_SESSION['checkout_data']);

    // هدایت به صفحه موفقیت آمیز
    $_SESSION['payment_success'] = true;
    header("Location: payment-success.php");
    exit();
}