<?php
include('../../../config/db.php');

// بررسی نقش کاربر
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'super_admin') {
    die("دسترسی غیرمجاز");
}

// گرفتن آیدی کاربر از فرم
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : (isset($_GET['user_id']) ? intval($_GET['user_id']) : null);

if (!$user_id) {
    // هدایت به صفحه مدیریت کاربران در صورت عدم ارسال user_id
    header("Location: manage-users.php?error=no_user_id");
    exit;
}

// اگر فرم ارسال شده، بروزرسانی انجام بده
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);


    $update_sql = "UPDATE users SET username='$username', email='$email', phone='$phone' WHERE id=$user_id";

    if ($conn->query($update_sql)) {
        $_SESSION['success_message'] = "اطلاعات کاربر با موفقیت بروزرسانی شد.";
        header("Location: manage-users.php");
        exit;
    } else {
        $_SESSION['error_message'] = "خطا در بروزرسانی: " . $conn->error;
        header("Location: manage-users.php");
        exit;
    }
}

// دریافت اطلاعات کاربر فعلی
$sql = "SELECT * FROM users WHERE id = $user_id";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    die("کاربری با این شناسه یافت نشد.");
}

$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>ویرایش کاربران</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        /* برای راست‌چین کردن inputهای ایمیل و تلفن */
        input#email,
        input#phone {
            text-align: right !important;
        }
    </style>
</head>

<body dir="rtl">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6 mt-4">
                <div class="card p-5 shadow">
                    <h5 class="text-danger mb-4 text-center">ویرایش کاربر</h5>
                    <form method="POST" action="">
                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                        <input type="hidden" name="update_user" value="1">

                        <div class="mb-4">
                            <label for="username" class="form-label">نام کاربری</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control shadow-none border" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="email" class="form-label">ایمیل</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control shadow-none border" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                        </div>

                        <div class="mb-5">
                            <label for="phone" class="form-label">شماره تماس</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone-alt"></i></span>
                                <input type="text" class="form-control shadow-none border" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>
                            </div>
                        </div>

                        <div class="d-flex justify-content-center gap-4">
                            <button type="submit" class="btn btn-danger" style="width: 45%;">ذخیره تغییرات</button>
                            <a href="manage-users.php" class="btn btn-secondary" style="width: 45%;">بازگشت</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
