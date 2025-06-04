<?php
session_start();
include('../../../config/db.php'); // مسیر فایل پیکربندی دیتابیس

// فقط سوپر ادمین اجازه دسترسی دارد
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'super_admin') {
    die("دسترسی غیرمجاز");
}

// ۱. خواندن آیدی کاربری که باید ویرایش شود
// اول از POST (فرم ویرایش) می‌پرسیم، در غیر این صورت از GET (لینک ویرایش)
$user_id = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
} elseif (isset($_GET['user_id'])) {
    $user_id = intval($_GET['user_id']);
}

if (!$user_id) {
    header("Location: manage-users.php?error=no_user_id");
    exit;
}

$alert = null;

// ۲. دریافت اطلاعات فعلی آن کاربر (از جدول users، شامل ستون address)
$sql = "SELECT username, email, phone, address FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    $alert = ['type' => 'error', 'message' => 'کاربری با این شناسه یافت نشد.'];
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    // ۳. اگر فرم ارسال شده بود، مقادیر جدید را بخوان و UPDATE کن
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $address  = trim($_POST['address']);

    $update_sql = "UPDATE users SET username = ?, email = ?, phone = ?, address = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssssi", $username, $email, $phone, $address, $user_id);

    if ($update_stmt->execute()) {
        $_SESSION['success_message'] = "اطلاعات کاربر با موفقیت به‌روزرسانی شد.";
        header("Location: manage-users.php");
        exit;
    } else {
        $alert = ['type' => 'error', 'message' => 'خطا در به‌روزرسانی اطلاعات: ' . $update_stmt->error];
    }
    $update_stmt->close();

    // اگر می‌خواهید فرم را بعد از موفقیت هم دوباره پر کنید:
    $user = ['username' => $username, 'email' => $email, 'phone' => $phone, 'address' => $address];
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>ویرایش کاربر</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* راست‌چین کردن ورودی ایمیل، تلفن و آدرس */
        input#email, input#phone, textarea#address {
            text-align: right !important;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card p-5 shadow">
                    <h5 class="text-danger mb-4 text-center">ویرایش کاربر</h5>
                    <!-- ۴. فرم ویرایش کاربر -->
                    <form method="POST" action="">
                        <!-- حتما user_id را پنهان بفرست -->
                        <input type="hidden" name="user_id" value="<?= $user_id ?>">
                        <input type="hidden" name="update_user" value="1">

                        <!-- نام کاربری -->
                        <div class="mb-4">
                            <label for="username" class="form-label">نام کاربری:</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control shadow-none border" id="username" name="username"
                                    value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
                            </div>
                        </div>

                        <!-- ایمیل -->
                        <div class="mb-4">
                            <label for="email" class="form-label">ایمیل:</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control shadow-none border" id="email" name="email"
                                    value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                            </div>
                        </div>

                        <!-- شماره تماس -->
                        <div class="mb-4">
                            <label for="phone" class="form-label">شماره تماس:</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone-alt"></i></span>
                                <input type="text" class="form-control shadow-none border" id="phone" name="phone"
                                    value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
                            </div>
                        </div>

                        <!-- آدرس -->
                        <div class="mb-5">
                            <label for="address" class="form-label">آدرس:</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                <textarea rows="1" class="form-control shadow-none border" id="address" name="address"
                                    ><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <!-- دکمه‌ها -->
                        <div class="d-flex justify-content-center gap-4">
                            <button type="submit" class="btn btn-danger" style="width: 45%;">ذخیره تغییرات</button>
                            <a href="manage-users.php" class="btn btn-secondary" style="width: 45%;">بازگشت</a>
                        </div>
                    </form>

                    <!-- نمایش پیام موفقیت یا خطا با SweetAlert -->
                    <?php if ($alert): ?>
                        <script>
                            Swal.fire({
                                icon: '<?= $alert['type'] ?>',
                                title: '<?= ($alert['type'] === 'success' ? 'موفقیت' : 'خطا') ?>',
                                text: '<?= $alert['message'] ?>',
                                confirmButtonText: 'باشه'
                            });
                        </script>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</body>
</html>
