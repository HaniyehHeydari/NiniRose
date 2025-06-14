<?php
session_start();
include('../../../config/db.php'); // مسیر فایل کانفیگ دیتابیس

// بررسی ورود کاربر
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user']['id'];
$alert = null;

// ۱. دریافت اطلاعات فعلی کاربر (شامل address)
$sql = "SELECT username, email, phone, address FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    $alert = ['type' => 'error', 'message' => 'کاربر پیدا نشد!'];
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $address  = trim($_POST['address']);

    // ۲. بروزرسانی اطلاعات (حالا شامل address)
    $update_sql = "UPDATE users SET username = ?, email = ?, phone = ?, address = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssssi", $username, $email, $phone, $address, $user_id);

    if ($update_stmt->execute()) {
        $alert = ['type' => 'success', 'message' => 'اطلاعات با موفقیت به‌روزرسانی شد.'];
        // برای نمایش مجدد مقادیر در فرم:
        $user = ['username' => $username, 'email' => $email, 'phone' => $phone, 'address' => $address];
    } else {
        $alert = ['type' => 'error', 'message' => 'خطا در به‌روزرسانی اطلاعات.'];
    }
    $update_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>ویرایش اطلاعات کاربری</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        input#email, input#phone, textarea#address {
            text-align: right !important;
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6 mt-4">
            <div class="card p-5 shadow">
                <form action="" method="POST">
                    <!-- نام کاربری -->
                    <div class="mb-4">
                        <label for="username" class="form-label">نام کاربری:</label>
                        <div class="input-group">
                            <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control shadow-none" style="border-color: #9FACB9;" id="username" name="username"
                                   value="<?= htmlspecialchars($user['username'] ?? '') ?>" >
                        </div>
                    </div>

                    <!-- ایمیل -->
                    <div class="mb-4">
                        <label for="email" class="form-label">ایمیل:</label>
                        <div class="input-group">
                            <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control shadow-none" style="border-color: #9FACB9;" id="email" name="email" 
                                   value="<?= htmlspecialchars($user['email'] ?? '') ?>" >
                        </div>
                    </div>

                    <!-- شماره تماس -->
                    <div class="mb-4">
                        <label for="phone" class="form-label">شماره تماس:</label>
                        <div class="input-group">
                            <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-phone-alt"></i></span>
                            <input type="tel" class="form-control shadow-none" style="border-color: #9FACB9;" id="phone" name="phone"
                                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>" >
                        </div>
                    </div>

                    <!-- آدرس -->
                    <div class="mb-5">
                        <label for="address" class="form-label">آدرس:</label>
                        <div class="input-group">
                            <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-map-marker-alt"></i></span>
                            <textarea rows="1" class="form-control shadow-none" style="border-color: #9FACB9;" id="address" name="address"
                                      placeholder="آدرس خود را وارد کنید"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- دکمه‌ها -->
                    <div class="d-flex justify-content-center gap-5">
                        <button type="submit" class="btn btn-danger" style="width: 45%;">ذخیره اطلاعات</button>
                        <a href="../../view/MainPage.php" class="btn btn-secondary" style="width: 45%;">بازگشت</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($alert): ?>
<script>
    Swal.fire({
        icon: '<?= $alert['type'] ?>',
        title: '<?= $alert['type'] === 'success' ? 'موفقیت' : 'خطا' ?>',
        text: '<?= $alert['message'] ?>',
        showConfirmButton: false, // این خط دکمه تایید را مخفی می‌کند
        timer: 2000,
        timerProgressBar: true,
        didOpen: () => {
            Swal.showLoading();
        }
    }).then(() => {
        <?php if ($alert['type'] === 'success'): ?>
            window.location.href = '../../view/MainPage.php';
        <?php endif; ?>
    });
</script>
<?php endif; ?>

</body>
</html>
