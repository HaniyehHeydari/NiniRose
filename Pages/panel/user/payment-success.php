<?php
session_start();

if (!isset($_SESSION['payment_success'])) {
    header("Location: checkout.php");
    exit();
}

unset($_SESSION['payment_success']);
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>پرداخت موفق</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
</head>

<body>
    <?php include('../../../Templates/Header.php') ?>

    <div class="container py-5">
        <div class="alert alert-success text-center">
            <h4>پرداخت با موفقیت انجام شد!</h4>
            <p>سفارش شما ثبت شد و در حال پردازش است.</p>
            <p>کد رهگیری: <?php echo rand(1000000, 9999999); ?></p>
            <a href="../../user/orders.php" class="btn btn-primary">مشاهده سفارشات</a>
        </div>
    </div>

    <?php include('../../../Templates/Footer.php') ?>
</body>

</html>