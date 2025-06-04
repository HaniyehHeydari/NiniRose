<?php
session_start();
if (!isset($_SESSION['user']['id']) || !isset($_SESSION['checkout_data'])) {
    header("Location: checkout.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>صفحه پرداخت</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <?php include('../../../Templates/Header.php') ?>

    <div class="container py-5">
        <div class="card mx-auto shadow-lg rounded-4 p-4" style="max-width: 500px;">
            <div class="card-body">
                <h4 class="card-title text-center mb-4">درگاه پرداخت</h4>

                <form method="POST" action="process-payment.php">
                    <div class="mb-3">
                        <label class="form-label">شماره کارت</label>
                        <input type="text" name="card_number" class="form-control rounded-4 shadow-none border" placeholder="**** **** **** ****" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تاریخ انقضا</label>
                        <div class="d-flex">
                            <input type="text" name="exp_month" class="form-control me-2 rounded-4 shadow-none border" placeholder="ماه" required>
                            <input type="text" name="exp_year" class="form-control rounded-4 shadow-none border" placeholder="سال" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">کد CVV2</label>
                        <input type="text" name="cvv" class="form-control rounded-4 shadow-none border" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">رمز دوم</label>
                        <input type="text" name="card_number" class="form-control rounded-4 shadow-none border" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100 rounded-4 py-2">پرداخت</button>
                </form>
            </div>
        </div>
    </div>

    <?php include('../../../Templates/Footer.php') ?>
    <?php if (isset($_SESSION['payment_success'])): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'پرداخت با موفقیت انجام شد',
                text: 'سفارش شما ثبت شد!',
                confirmButtonText: 'باشه'
            }).then(() => {
                window.location.href = "../../view/product-detail.php";
            });
        </script>
    <?php unset($_SESSION['payment_success']);
    endif; ?>

</body>

</html>