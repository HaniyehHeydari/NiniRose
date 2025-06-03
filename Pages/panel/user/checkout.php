<?php
session_start();
include('../../../config/db.php');

if (!isset($_SESSION['user']['id'])) {
    header("Location: ../../auth/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>تکمیل اطلاعات سفارش</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
</head>

<body>
    <?php include('../../../Templates/Header.php') ?>

    <div class="container py-5">
        <h4 class="mb-4">تکمیل اطلاعات سفارش</h4>
        
        <?php if (isset($_SESSION['errors'])): ?>
            <div class="alert alert-danger">
                <?php foreach ($_SESSION['errors'] as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
                <?php unset($_SESSION['errors']); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="process-checkout.php">
            <div class="mb-3">
                <label class="form-label">نام و نام خانوادگی</label>
                <input type="text" name="fullname" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">شماره تماس</label>
                <input type="text" name="phone" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">کد پستی</label>
                <input type="text" name="postal_code" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">آدرس کامل</label>
                <textarea name="address" class="form-control" rows="4" required></textarea>
            </div>

            <button type="submit" class="btn btn-success w-100">انتقال به صفحه پرداخت</button>
        </form>
    </div>

    <?php include('../../../Templates/Footer.php') ?>
</body>

</html>