<?php
session_start();
include('../../../config/db.php');

if (!isset($_SESSION['user']['id']) || !isset($_SESSION['order_success'])) {
    header("Location: ../../auth/login.php");
    exit();
}

$order_number = $_SESSION['order_success'];
unset($_SESSION['order_success']);
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>سفارش با موفقیت ثبت شد</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f7fa;
            font-family: 'Vazir', sans-serif;
        }
        .success-container {
            max-width: 600px;
            margin: auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-top: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        .success-icon {
            font-size: 5rem;
            color: #28a745;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include('../../../Templates/Header.php') ?>

    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="success-container w-100">
            <div class="success-icon">✓</div>
            <h3 class="mb-3">سفارش شما با موفقیت ثبت شد!</h3>
            <p class="mb-4">شماره سفارش: <strong><?php echo htmlspecialchars($order_number); ?></strong></p>
            <p class="mb-4">جزئیات سفارش به آدرس ایمیل شما ارسال خواهد شد.</p>
            <a href="../../user/orders.php" class="btn btn-success rounded-4 py-2 px-4">مشاهده سفارشات</a>
            <a href="../../shop/" class="btn btn-outline-secondary rounded-4 py-2 px-4 ms-2">بازگشت به فروشگاه</a>
        </div>
    </div>

    <?php include('../../../Templates/Footer.php') ?>
</body>
</html>