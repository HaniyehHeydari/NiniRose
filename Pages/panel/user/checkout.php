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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f5f7fa;
            font-family: 'Vazir', sans-serif;
        }

        .payment-container {
            max-width: 900px;
            margin: auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-top: 2rem;
            margin-bottom: 2rem;
        }

        .form-control {
            height: 40px;
            font-size: 1rem;
            border-radius: 1rem;
        }

        .form-control::placeholder {
            color: #999;
            font-size: 0.95rem;
        }

        .btn-success {
            background-color: #00c896;
            border: none;
            transition: all 0.3s;
        }

        .btn-success:hover {
            background-color: #00b185;
            transform: translateY(-2px);
        }

        /* برای هماهنگی با فرم */
    .select2-container .select2-selection--single {
    height: 45px !important;
    border-radius: 1rem !important;
    border: 1px solid #ced4da !important;
    font-size: 1rem !important;
    text-align: right !important;
    padding-right: 1rem !important;
    display: flex;
    align-items: center;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #495057;
    line-height: 45px !important;
    font-size: 1rem;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 100% !important;
    top: 50% !important;
    transform: translateY(-50%);
}

        .select2-results__options {
            max-height: 200px !important;
            overflow-y: auto !important;
        }

    </style>
</head>

<body>
    <?php include('../../../Templates/Header.php') ?>

    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="payment-container w-100" style="max-width: 500px;">
            <div class="d-flex justify-content-center">
                <h5 class="m-0">تکمیل اطلاعات سفارش</h5>
            </div>

            <?php if (isset($_SESSION['errors'])): ?>
                <div class="alert alert-danger mt-3 rounded-4">
                    <?php foreach ($_SESSION['errors'] as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                    <?php unset($_SESSION['errors']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="process-checkout.php" class="mt-4">
                <div class="mb-3">
                    <label class="form-label">نام و نام خانوادگی</label>
                    <input type="text" name="fullname" class="form-control rounded-4 shadow-none border" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">شماره تماس</label>
                    <input type="text" name="phone" class="form-control rounded-4 shadow-none border" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">استان</label>
                        <select name="province" id="province" class="form-control rounded-4 shadow-none border"></select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">شهر</label>
                        <select name="city" id="city" class="form-control rounded-4 shadow-none border"></select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">آدرس کامل</label>
                    <textarea name="address" class="form-control rounded-4 shadow-none border" rows="4" required></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">کد پستی</label>
                    <input type="text" name="postal_code" class="form-control rounded-4 shadow-none border" required>
                </div>

                <button type="submit" class="btn btn-success rounded-4 w-100 py-2 mt-3">انتقال به صفحه پرداخت</button>
            </form>
        </div>
    </div>

    <?php include('../../../Templates/Footer.php') ?>
    <!-- در head بگذارید: -->
    <!-- jQuery + Select2 JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        const provinceCityMap = {
            "آذربایجان شرقی": ["تبریز", "مراغه", "مرند", "اهر", "شبستر"],
            "آذربایجان غربی": ["ارومیه", "خوی", "بوکان", "مهاباد", "سلماس"],
            "اردبیل": ["اردبیل", "مشگین‌شهر", "پارس‌آباد", "خلخال"],
            "اصفهان": ["اصفهان", "کاشان", "خمینی‌شهر", "نجف‌آباد", "شاهین‌شهر"],
            "البرز": ["کرج", "نظرآباد", "طالقان", "اشتهارد"],
            "ایلام": ["ایلام", "دهلران", "مهران", "آبدانان"],
            "بوشهر": ["بوشهر", "برازجان", "جم", "گناوه"],
            "تهران": ["تهران", "ری", "اسلامشهر", "شهریار", "پردیس"],
            "چهارمحال و بختیاری": ["شهرکرد", "فارسان", "بروجن", "لردگان"],
            "خراسان جنوبی": ["بیرجند", "قائن", "فردوس", "نهبندان"],
            "خراسان رضوی": ["مشهد", "نیشابور", "تربت حیدریه", "سبزوار", "کاشمر"],
            "خراسان شمالی": ["بجنورد", "اسفراین", "شیروان", "جاجرم"],
            "خوزستان": ["اهواز", "آبادان", "خرمشهر", "دزفول", "بهبهان"],
            "زنجان": ["زنجان", "ابهر", "خرمدره", "طارم"],
            "سمنان": ["سمنان", "شاهرود", "دامغان", "گرمسار"],
            "سیستان و بلوچستان": ["زاهدان", "زابل", "چابهار", "ایرانشهر"],
            "فارس": ["شیراز", "مرودشت", "کازرون", "فسا", "لار"],
            "قزوین": ["قزوین", "البرز", "آبیک", "تاکستان"],
            "قم": ["قم"],
            "کردستان": ["سنندج", "سقز", "بانه", "بیجار"],
            "کرمان": ["کرمان", "رفسنجان", "سیرجان", "جیرفت"],
            "کرمانشاه": ["کرمانشاه", "اسلام‌آباد غرب", "هرسین", "سرپل ذهاب"],
            "کهگیلویه و بویراحمد": ["یاسوج", "دهدشت", "گچساران"],
            "گلستان": ["گرگان", "گنبد کاووس", "علی‌آباد", "آق‌قلا"],
            "گیلان": ["رشت", "لاهیجان", "انزلی", "تالش"],
            "لرستان": ["خرم‌آباد", "بروجرد", "دورود", "الیگودرز"],
            "مازندران": ["ساری", "بابل", "آمل", "تنکابن", "قائم‌شهر"],
            "مرکزی": ["اراک", "ساوه", "خمین", "محلات"],
            "هرمزگان": ["بندرعباس", "قشم", "بندر لنگه", "میناب"],
            "همدان": ["همدان", "ملایر", "نهاوند", "تویسرکان"],
            "یزد": ["یزد", "میبد", "اردکان", "تفت"]
        };

        $(document).ready(function() {
            // Populate provinces dynamically
            const $province = $('#province');
            const $city = $('#city');

            $province.append('<option value="">انتخاب استان</option>');
            Object.keys(provinceCityMap).forEach(province => {
                $province.append(`<option value="${province}">${province}</option>`);
            });

            // Init select2 with scrollable dropdown
            $province.select2({
                placeholder: 'انتخاب استان',
                width: '100%',
                dropdownAutoWidth: true,
                dropdownCssClass: "select2-scroll"
            });

            $city.select2({
                placeholder: 'انتخاب شهر',
                width: '100%',
                dropdownAutoWidth: true,
                dropdownCssClass: "select2-scroll"
            });

            // Update cities on province change
            $province.on('change', function() {
                const selectedProvince = $(this).val();
                const cities = provinceCityMap[selectedProvince] || [];

                $city.empty().append('<option value="">انتخاب شهر</option>');
                cities.forEach(city => {
                    $city.append(`<option value="${city}">${city}</option>`);
                });

                $city.val(null).trigger('change');
            });
        });
    </script>


</body>

</html>