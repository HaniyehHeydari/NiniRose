<?php
session_start();
include('../../../config/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_id = $_SESSION['user']['id'] ?? null;

    if (!$admin_id) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'message' => 'برای ارسال درخواست، ابتدا وارد حساب کاربری شوید.'
        ];
        header('Location: ../../auth/login.php'); // مسیر صحیح فایل لاگین
        exit;
    }

    $name = $_POST['name'] ?? '';
    $address = $_POST['address'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $status = 0;

    $sql = "INSERT INTO stores (admin_id, name, address, phone, status) 
            VALUES ('$admin_id', '$name', '$address', '$phone', $status)";

    if ($conn->query($sql) === TRUE) {
        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => 'درخواست شما با موفقیت ثبت شد و در انتظار تایید است.'
        ];
    } else {
        $_SESSION['alert'] = [
            'type' => 'error',
            'message' => 'خطا در ثبت اطلاعات: ' . $conn->error
        ];
    }

    $conn->close();
    header('Location: ../../view/MainPagephp');
    exit;
}
?>
