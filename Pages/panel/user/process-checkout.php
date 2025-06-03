<?php
session_start();
include('../../../config/db.php');

if (!isset($_SESSION['user']['id'])) {
    header("Location: ../../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user']['id'];
    $fullname = $_POST['fullname'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $postal_code = $_POST['postal_code'];

    // اعتبارسنجی
    $errors = [];
    if (empty($fullname) || empty($phone) || empty($address) || empty($postal_code)) {
        $errors[] = "همه فیلدها باید پر شوند.";
    }

    if (!preg_match('/^[0-9]{10,11}$/', $phone)) {
        $errors[] = "شماره تماس باید 10 یا 11 رقم باشد.";
    }

    if (!preg_match('/^[0-9]{10}$/', $postal_code)) {
        $errors[] = "کد پستی باید 10 رقم باشد.";
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header("Location: checkout.php");
        exit();
    }

    // به‌روزرسانی آدرس در جدول users
    $sql = "UPDATE users SET address = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $address, $user_id);

    if ($stmt->execute()) {
        // ذخیره اطلاعات در سشن برای استفاده در صفحه پرداخت
        $_SESSION['checkout_data'] = [
            'fullname' => $fullname,
            'phone' => $phone,
            'address' => $address,
            'postal_code' => $postal_code
        ];

        // انتقال به صفحه پرداخت
        header("Location: payment.php");
        exit();
    } else {
        $_SESSION['errors'] = ["خطا در به‌روزرسانی آدرس"];
        header("Location: checkout.php");
        exit();
    }

    $stmt->close();
    $conn->close();
}
