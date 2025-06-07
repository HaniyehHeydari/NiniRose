<?php
session_start();
include('../../../config/db.php');

// فقط سوپر ادمین دسترسی داشته باشد
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'super_admin') {
    die("دسترسی غیرمجاز");
}

// گرفتن آیدی فروشگاه از فرم یا آدرس
$store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : (isset($_GET['store_id']) ? intval($_GET['store_id']) : null);

if (!$store_id) {
    header("Location: manage-stores.php?error=no_store_id");
    exit;
}

// اگر فرم ارسال شده، بروزرسانی انجام بده
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_store'])) {
    $store_name = $conn->real_escape_string($_POST['store_name']);
    $store_address = $conn->real_escape_string($_POST['store_address']);
    $store_phone = $conn->real_escape_string($_POST['store_phone']);

    $update_sql = "UPDATE stores SET name='$store_name', address='$store_address', phone='$store_phone' WHERE id=$store_id";

    if ($conn->query($update_sql)) {
        $_SESSION['success_message'] = "اطلاعات فروشگاه با موفقیت بروزرسانی شد.";
        header("Location: manage-stores.php");
        exit;
    } else {
        $_SESSION['error_message'] = "خطا در بروزرسانی: " . $conn->error;
        header("Location: manage-stores.php");
        exit;
    }
}

// دریافت اطلاعات فروشگاه فعلی
$sql = "SELECT * FROM stores WHERE id = $store_id";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    die("فروشگاهی با این شناسه یافت نشد.");
}

$store = $result->fetch_assoc();
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

                        <div class="mb-4">
                            <label for="store_name" class="form-label">نام فروشگاه</label>
                            <div class="input-group">
                                <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-store"></i></span>
                                <input type="text" class="form-control shadow-none" style="border-color: #9FACB9;" id="store_name" name="store_name" value="<?= htmlspecialchars($store['name']) ?>" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="store_address" class="form-label">آدرس</label>
                            <div class="input-group">
                                <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-map-marker-alt"></i></span>
                                <input type="text" class="form-control shadow-none" style="border-color: #9FACB9;" id="store_address" name="store_address" value="<?= htmlspecialchars($store['address']) ?>" required>
                            </div>
                        </div>

                        <div class="mb-5">
                            <label for="store_phone" class="form-label">شماره تماس</label>
                            <div class="input-group">
                                <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-phone-alt"></i></span>
                                <input type="text" class="form-control shadow-none" style="border-color: #9FACB9;" id="store_phone" name="store_phone" value="<?= htmlspecialchars($store['phone']) ?>" required>
                            </div>
                        </div>

                        <div class="d-flex justify-content-center gap-4">
                            <button type="submit" class="btn btn-danger" style="width: 45%;">ذخیره تغییرات</button>
                            <a href="manage-stores.php" class="btn btn-secondary" style="width: 45%;">بازگشت</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
