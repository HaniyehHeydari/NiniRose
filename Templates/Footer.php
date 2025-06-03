<?php include_once dirname(__DIR__) . '/Config/config.php'; ?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <title>Footer</title>
</head>

<body>
    <footer class="py-5" style="font-family: Tahoma, sans-serif; color: #333;  background-color: #fdf1f3;">
        <div class="container bg-white rounded-4 p-4 shadow-sm" style=" background-color: #fdf1f3;">
            <div class="row justify-content-center text-center" style="column-gap: 80px;">

                <!-- ستون ۱: ارتباط با ما -->
                <div class="col-md-3">
                    <!-- عنوان وسط‌چین -->
                    <h5 class="mb-4 text-center">ارتباط با ما</h5>

                    <!-- محتوا چپ‌چین (با در نظر گرفتن direction: rtl) -->
                    <p class="d-flex align-items-center justify-content-start mb-2">
                        <img src="<?php echo BASE_URL; ?>Public/image/Location.png" width="25" height="25">
                        <span>آدرس: همدان، خیابان تختی، ابتدای کوچه توفیق همدانی</span>
                    </p>

                    <p class="d-flex align-items-center justify-content-start mb-2">
                        <img src="<?php echo BASE_URL; ?>Public/image/call.png" width="25" height="25" class="me-2">
                        <span>تلفن پشتیبانی: 08132512523</span>
                    </p>

                    <p class="d-flex align-items-center justify-content-start mb-2">
                        <img src="<?php echo BASE_URL; ?>Public/image/Email.png" width="25" height="25" class="me-2">
                        <span>ایمیل: info@ninirose.ir</span>
                    </p>
                </div>



                <!-- ستون ۲: دسترسی سریع -->
                <div class="col-md-3">
                    <h5 class="mb-4">دسترسی سریع</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-danger text-decoration-none">ورود / ثبت نام</a></li>
                        <li class="mb-2"><a href="#" class="text-danger text-decoration-none">سبد خرید</a></li>
                        <li class="mb-2"><a href="<?php echo BASE_URL; ?>Pages/panel/user/Store-request.php" class="text-danger text-decoration-none">همکاری با ما</a></li>
                        <li class="mb-2"><a href="#" class="text-danger text-decoration-none">پیگیری سفارش</a></li>
                    </ul>
                </div>

                <!-- ستون ۳: نماد اعتماد -->
                <div class="col-md-3">
                    <h5 class="mb-5">نماد اعتماد الکترونیک</h5>
                    <div class="d-flex justify-content-center gap-3">
                        <img src="<?php echo BASE_URL; ?>Public/image/enamad.png" alt="نماد اعتماد" style="width:100px; height:100px;">
                        <img src="<?php echo BASE_URL; ?>Public/image/samandehi.png" alt="ساماندهی" style="width:100px; height:100px;">
                    </div>
                </div>

            </div>

            <div class="text-center mt-3 text-muted" style="font-size: 0.9rem;">
                کلیه حقوق مادی و معنوی این سایت متعلق به نی نی رز می‌باشد.
            </div>
    </footer>
</body>

</html>