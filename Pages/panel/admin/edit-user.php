<?php
session_start();
include('../../../config/db.php');

// فقط سوپر ادمین اجازه دسترسی دارد
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'super_admin') {
    die("دسترسی غیرمجاز");
}

// ۱. خواندن آیدی کاربری که باید ویرایش شود
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
$errors = [];

// ۲. دریافت اطلاعات فعلی آن کاربر
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
    // ۳. دریافت مقادیر جدید از فرم
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $address  = trim($_POST['address']);

    // اعتبارسنجی نام کاربری
    if (empty($username)) {
        $errors['username'] = 'لطفا نام کاربری را وارد کنید';
    } elseif (!preg_match('/^(?![0-9])[آ-یa-zA-Z0-9\s‌]{3,}$/u', $username)) {
        $errors['username'] = 'نام کاربری باید حداقل ۳ کاراکتر و فقط شامل حروف و اعداد باشد';
    }

    // اعتبارسنجی ایمیل
    if (empty($email)) {
        $errors['email'] = 'لطفا ایمیل را وارد کنید';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'فرمت ایمیل نامعتبر است';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors['email'] = 'این ایمیل قبلا ثبت شده است';
        }
        $stmt->close();
    }

    // اعتبارسنجی شماره تلفن
    if (empty($phone)) {
        $errors['phone'] = 'لطفا شماره تلفن را وارد کنید';
    } elseif (!preg_match('/^09\d{9}$/', $phone)) {
        $errors['phone'] = 'لطفا یک شماره معتبر وارد کنید';
    }

    // اعتبارسنجی آدرس
    if (!empty($address) && mb_strlen($address) > 300) {
        $errors['address'] = 'آدرس نمی‌تواند بیش از 300 کاراکتر باشد';
    }

    // اگر خطایی وجود نداشت، اطلاعات را بروزرسانی می‌کنیم
    if (empty($errors)) {
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
    }

    // برای نمایش مجدد مقادیر در فرم در صورت خطا
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
        input#email,
        input#phone,
        textarea#address {
            text-align: right !important;
        }

         .error-message {
            color: red;
            font-size: 12px;
            margin-top: 5px;
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
                        <div class="mb-4" id="usernames">
                            <label for="username" class="form-label">نام کاربری:</label>
                            <div class="input-group">
                                <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-user"></i></span>
                                <input type="text" id="username" class="form-control shadow-none" style="border-color: #9FACB9;" id="username" name="username"
                                    title="فقط حروف مجاز است"
                                    value="<?= htmlspecialchars($user['username'] ?? '') ?>">
                            </div>
                            <?php if (isset($errors['username'])): ?>
                                <span class="error-message"><?= $errors['username'] ?></span>
                            <?php endif; ?>
                        </div>
                        <!-- ایمیل -->
                        <div class="mb-4" id="email">
                            <label for="email" class="form-label">ایمیل:</label>
                            <div class="input-group">
                                <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-envelope"></i></span>
                                <input type="text" id="emailuser" class="form-control shadow-none" style="border-color: #9FACB9;" id="email" name="email"
                                    title="لطفا ایمیل معتبر وارد کنید"
                                    value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                            </div>
                            <?php if (isset($errors['email'])): ?>
                                <span class="error-message"><?= $errors['email'] ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- شماره تماس -->
                        <div id="mobile" class="mb-4">
                            <label for="phone" class="form-label">شماره تماس:</label>
                            <div class="input-group">
                                <span class="input-group-text" id="phone" style="border-color: #9FACB9;"><i class="fas fa-phone-alt"></i></span>
                                <input type="text" class="form-control shadow-none" style="border-color: #9FACB9;" id="phone" name="phone"
                                    title="لطفا شماره تلفن معتبر وارد کنید"
                                    value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>
                            <?php if (isset($errors['phone'])): ?>
                                <span class="error-message"><?= $errors['phone'] ?></span>
                            <?php endif; ?>
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
                         <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-check me-2"></i>ذخیره تغییرات
                            </button>

                            <a href="manage-users.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>بازگشت
                            </a>
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