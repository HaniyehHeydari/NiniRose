<?php
session_start();
include('../../../config/db.php');

// بررسی لاگین بودن کاربر
if (!isset($_SESSION['user']['id']) || !isset($_SESSION['checkout_data'])) {
    header("Location: checkout.php");
    exit();
}

// تعیین مبلغ پرداختی (مثال: 610,000 ریال)
$total_amount = 610000;

// تابع تبدیل اعداد به فارسی
function persian_numbers($number) {
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    return str_replace($english, $persian, number_format($number));
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>درگاه پرداخت</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-top: 2rem;
            margin-bottom: 2rem;
        }
        .payment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        .form-control::placeholder {
            font-size: 0.9rem;
            color: #aaa;
        }
        .amount-box {
            font-weight: bold;
            font-size: 1.3rem;
            color: #0d6efd;
        }
        .pay-btn {
            background-color: #00c896;
            border: none;
            transition: all 0.3s;
            height: 50px;
            font-size: 1.1rem;
        }
        .pay-btn:hover {
            background-color: #00b185;
            transform: translateY(-2px);
        }
        .bank-cards {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .bank-card {
            width: 60px;
            height: 40px;
            object-fit: contain;
            border: 1px solid #eee;
            border-radius: 5px;
            padding: 5px;
        }
        .order-summary {
            border-right: 3px solid #00c896;
            padding-right: 15px;
        }
    </style>
</head>
<body>

<?php include('../../../Templates/Header.php'); ?>

<div class="container payment-container">
    <div class="payment-header">
        <img src="https://sep.shaparak.ir/logo.png" alt="لوگو سپ" height="50">
        <h5 class="m-0">درگاه پرداخت اینترنتی</h5>
    </div>

    <div class="row">
        <!-- فرم پرداخت -->
        <div class="col-md-6">
            <h6 class="mb-3 text-primary">اطلاعات کارت بانکی</h6>
            
            <div class="bank-cards">
                <img src="https://www.meliiran.ir/images/logo.png" alt="بانک ملی" class="bank-card">
                <img src="https://www.samanbank.ir/images/logo.png" alt="بانک سامان" class="bank-card">
                <img src="https://www.parsian-bank.ir/images/logo.png" alt="بانک پارسیان" class="bank-card">
                <img src="https://www.mellatbank.ir/images/logo.png" alt="بانک ملت" class="bank-card">
            </div>
            
            <form method="POST" action="process-payment.php">
                <div class="mb-3">
                    <label class="form-label">شماره کارت</label>
                    <input type="text" name="card_number" class="form-control" placeholder="۶۰۳۷-۹۹۹۹-۸۸۸۸-۷۷۷۷" required>
                </div>

                <div class="row mb-3">
                    <div class="col">
                        <label class="form-label">تاریخ انقضا</label>
                        <input type="text" name="expiry_date" class="form-control" placeholder="۰۴/۱۴" required>
                    </div>
                    <div class="col">
                        <label class="form-label">CVV2</label>
                        <input type="text" name="cvv2" class="form-control" placeholder="۱۲۳" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">رمز دوم (پویا)</label>
                    <input type="password" name="dynamic_password" class="form-control" placeholder="رمز یکبار مصرف" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">کد امنیتی</label>
                    <div class="d-flex align-items-center">
                        <input type="text" class="form-control me-2" placeholder="کد تصویر" required>
                        <span style="font-family: monospace; font-size: 1.2rem; background: #e0e0e0; padding: 5px 10px; border-radius: 5px;">۵۳۳۸۱</span>
                    </div>
                </div>

                <div class="alert alert-light text-center py-3 mb-4">
                    <p class="m-0 text-muted">مبلغ قابل پرداخت:</p>
                    <p class="amount-box my-1"><?php echo persian_numbers($total_amount); ?> ریال</p>
                    <small class="text-muted">معادل <?php echo persian_numbers($total_amount/10); ?> تومان</small>
                </div>

                <button type="submit" class="btn pay-btn text-white w-100 d-flex align-items-center justify-content-center">
                    <i class="fas fa-lock me-2"></i>
                    <span>پرداخت مبلغ</span>
                    <span class="fw-bold mx-1"><?php echo persian_numbers($total_amount); ?></span>
                    <span>ریال</span>
                </button>
            </form>
        </div>

        <!-- اطلاعات سفارش -->
        <div class="col-md-6 order-summary">
            <h6 class="mb-3 text-primary">خلاصه سفارش</h6>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="card-title border-bottom pb-2">اطلاعات تحویل گیرنده</h6>
                    <p><i class="fas fa-user me-2 text-muted"></i> <?php echo htmlspecialchars($_SESSION['checkout_data']['fullname']); ?></p>
                    <p><i class="fas fa-phone me-2 text-muted"></i> <?php echo htmlspecialchars($_SESSION['checkout_data']['phone']); ?></p>
                    <p><i class="fas fa-map-marker-alt me-2 text-muted"></i> <?php echo nl2br(htmlspecialchars($_SESSION['checkout_data']['address'])); ?></p>
                    <p><i class="fas fa-barcode me-2 text-muted"></i> کد پستی: <?php echo htmlspecialchars($_SESSION['checkout_data']['postal_code']); ?></p>
                    
                    <h6 class="card-title border-bottom pb-2 mt-4">جزئیات سفارش</h6>
                    <div class="d-flex justify-content-between">
                        <span>مبلغ کل:</span>
                        <span><?php echo persian_numbers($total_amount); ?> ریال</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>هزینه ارسال:</span>
                        <span>رایگان</span>
                    </div>
                    <div class="d-flex justify-content-between mt-2 fw-bold">
                        <span>مبلغ قابل پرداخت:</span>
                        <span class="text-success"><?php echo persian_numbers($total_amount); ?> ریال</span>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-warning mt-3">
                <i class="fas fa-exclamation-circle me-2"></i>
                پس از پرداخت، رسید پرداخت به ایمیل و پیامک شما ارسال خواهد شد.
            </div>
        </div>
    </div>
</div>

<?php include('../../../Templates/Footer.php'); ?>

</body>
</html>