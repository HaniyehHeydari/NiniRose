<?php
session_start();
include('../../../config/db.php');

// بررسی دسترسی سوپر ادمین
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'super_admin') {
    die("دسترسی غیرمجاز");
}

$errors = [];
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);

// گرفتن آیدی فروشگاه
$store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : (isset($_GET['store_id']) ? intval($_GET['store_id']) : null);

if (!$store_id) {
    header("Location: manage-stores.php?error=no_store_id");
    exit;
}

// دریافت اطلاعات فروشگاه فعلی
$sql = "SELECT * FROM stores WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $store_id);
$stmt->execute();
$result = $stmt->get_result();
$store = $result->fetch_assoc();
$stmt->close();

if (!$store) {
    die("فروشگاهی با این شناسه یافت نشد.");
}

// پردازش فرم ویرایش
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_store'])) {
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    // اعتبارسنجی نام مغازه
    if (empty($name)) {
        $errors['name'] = 'لطفا نام مغازه را وارد کنید';
    } elseif (!preg_match('/^[\x{0600}-\x{06FF}\s]{3,}$/u', $name)) {
        $errors['name'] = 'نام مغازه باید بیش از ۳ کاراکتر و فقط شامل حروف فارسی باشد';
    } else {
        // بررسی تکراری نبودن نام مغازه (به جز برای فروشگاه فعلی)
        $stmt_check = $conn->prepare("SELECT id FROM stores WHERE name = ? AND id != ?");
        $stmt_check->bind_param("si", $name, $store_id);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $errors['name'] = 'این نام مغازه قبلاً ثبت شده است';
        }
        $stmt_check->close();
    }

    // اعتبارسنجی آدرس
    if (empty($address)) {
        $errors['address'] = 'لطفا آدرس را وارد کنید';
    } elseif (mb_strlen($address) < 10 ) {
        $errors['address'] = 'آدرس باید بین ۱۰ تا ۳۰۰ کاراکتر باشد';
    }

    // اعتبارسنجی شماره تماس
    if (empty($phone)) {
        $errors['phone'] = 'لطفا شماره تماس را وارد کنید';
    } elseif (!preg_match('/^09\d{9}$/', $phone)) {
        $errors['phone'] = 'شماره تماس باید با 09 شروع شود و 11 رقم باشد';
    }

    // اگر خطایی وجود نداشت
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE stores SET name = ?, address = ?, phone = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $address, $phone, $store_id);

        if ($stmt->execute()) {
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'اطلاعات فروشگاه با موفقیت بروزرسانی شد.'
            ];
            header("Location: manage-stores.php");
            exit;
        } else {
            $errors['database'] = 'خطا در بروزرسانی اطلاعات: ' . $stmt->error;
        }
        $stmt->close();
    }

    // برای نمایش مجدد مقادیر در فرم در صورت خطا
    $store = [
        'id' => $store_id,
        'name' => $name,
        'address' => $address,
        'phone' => $phone
    ];
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>ویرایش فروشگاه</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        input#store_name,
        input#store_address,
        input#store_phone {
            text-align: right !important;
        }

        .error-message {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }
    </style>
</head>

<body dir="rtl">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6 mt-4">
                <div class="card p-5 shadow">
                    <h5 class="text-danger mb-4 text-center">ویرایش فروشگاه</h5>
                    <form method="POST" action="">
                        <input type="hidden" name="store_id" value="<?= $store['id'] ?>">
                        <input type="hidden" name="update_store" value="1">

                        <div class="mb-4" id="names">
                            <label for="name" class="form-label">نام فروشگاه</label>
                            <div class="input-group">
                                <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-user"></i></span>
                                <input type="text" id="name" class="form-control shadow-none" style="border-color: #9FACB9;" id="name" name="name"
                                    title="فقط حروف مجاز است"
                                    value="<?= htmlspecialchars($store['name']) ?>">
                            </div>
                            <?php if (isset($errors['name'])): ?>
                                <span class="error-message"><?= $errors['name'] ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <label for="address" class="form-label">آدرس</label>
                            <div class="input-group">
                                <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-map-marker-alt"></i></span>
                                <textarea class="form-control shadow-none" style="border-color: #9FACB9;" rows="1"
                                    id="address" name="address"><?= htmlspecialchars($store['address'] ?? '') ?></textarea>
                            </div>
                            <?php if (isset($errors['address'])): ?>
                                <span class="error-message"><?= $errors['address'] ?></span>
                            <?php endif; ?>
                        </div>

                        <div id="mobile" class="mb-4">
                            <label for="phone" class="form-label">شماره تماس:</label>
                            <div class="input-group">
                                <span class="input-group-text" id="phone" style="border-color: #9FACB9;"><i class="fas fa-phone-alt"></i></span>
                                <input type="text" class="form-control shadow-none" style="border-color: #9FACB9;" id="phone" name="phone"
                                    title="لطفا شماره تلفن معتبر وارد کنید"
                                    value="<?= htmlspecialchars($store['phone']) ?>">
                            </div>
                            <?php if (isset($errors['phone'])): ?>
                                <span class="error-message"><?= $errors['phone'] ?></span>
                            <?php endif; ?>
                        </div>

                         <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-check me-2"></i>ذخیره اطلاعات
                            </button>

                            <a href="manage-stores.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>بازگشت
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>