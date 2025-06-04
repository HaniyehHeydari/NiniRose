<?php
session_start();
include('../../../config/db.php');

if (!isset($_SESSION['user']['id'])) {
    header("Location: ../../auth/login.php");
    exit();
}

$user_id = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname     = trim($_POST['fullname']);
    $phone        = trim($_POST['phone']);
    $address      = trim($_POST['address']);
    $postal_code  = trim($_POST['postal_code']);

    // اعتبارسنجی اولیه
    $errors = [];
    if ($fullname === '' || $phone === '' || $address === '' || $postal_code === '') {
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

    // ۱. ابتدا مقدار فعلی address را از دیتابیس بخوانیم
    $check_sql = "SELECT address FROM users WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $check_res = $check_stmt->get_result();
    $row = $check_res->fetch_assoc();
    $existing_address = $row['address'] ?? null;
    $check_stmt->close();

    // ۲. اگر آدرس فعلی نال یا خالی بود، آن را به‌روزرسانی کنیم
    if ($existing_address === null || $existing_address === '') {
        $update_sql = "UPDATE users SET address = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $address, $user_id);

        if (!$update_stmt->execute()) {
            $_SESSION['errors'] = ["خطا در به‌روزرسانی آدرس"];
            $update_stmt->close();
            header("Location: checkout.php");
            exit();
        }
        $update_stmt->close();
    } else {
        // اگر آدرس از قبل وجود دارد، از همان مقدار برای ادامهٔ فرایند استفاده کنیم
        $address = $existing_address;
    }

    // ۳. ذخیرهٔ اطلاعات checkout در سشن
    $_SESSION['checkout_data'] = [
        'fullname'    => $fullname,
        'phone'       => $phone,
        'address'     => $address,
        'postal_code' => $postal_code
    ];

    // ۴. انتقال به صفحه پرداخت
    header("Location: payment.php");
    exit();
}
