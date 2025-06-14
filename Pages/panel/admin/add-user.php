<?php
session_start();
include('../../../config/db.php');

// فقط سوپر ادمین دسترسی دارد
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'super_admin') {
    die("دسترسی غیرمجاز");
}
$alert = null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $address  = trim($_POST['address']);
    $password = trim($_POST['password']);
    $role     = 'user'; // نقش پیش‌فرض

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
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
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

    // اعتبارسنجی رمز عبور
    if (empty($password)) {
        $errors['password'] = 'لطفا رمز عبور را وارد کنید';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'رمز عبور باید حداقل ۶ کاراکتر باشد';
    }

    // اعتبارسنجی آدرس
    if (!empty($address) && mb_strlen($address) > 300) {
        $errors['address'] = 'آدرس نمی‌تواند بیش از 300 کاراکتر باشد';
    }

    // اگر خطایی وجود نداشت، کاربر جدید را ایجاد می‌کنیم
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $insert_sql = "INSERT INTO users (username, email, phone, address, password, role) VALUES (?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ssssss", $username, $email, $phone, $address, $hashed_password, $role);

        if ($insert_stmt->execute()) {
            $alert = ['type' => 'success', 'message' => 'کاربر جدید با موفقیت ایجاد شد.'];
            // خالی کردن فیلدها پس از ثبت موفق
            $_POST = array();
        } else {
            $alert = ['type' => 'error', 'message' => 'خطا در ایجاد کاربر جدید.'];
        }
        $insert_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>ایجاد کاربر جدید</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
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
            <div class="col-12 col-md-8 col-lg-6 mt-4">
                <div class="card p-5 shadow">
                    <h3 class="text-danger text-center mb-4">افزودن کاربر جدید</h3>
                    <form action="" method="POST">

                        <!-- نام کاربری -->
                        <div class="mb-4">
                            <label for="username" class="form-label">نام کاربری:</label>
                            <div class="input-group">
                                <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control shadow-none" style="border-color: #9FACB9;" id="username" name="username"
                                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                            </div>
                            <?php if (isset($errors['username'])): ?>
                                <span class="error-message"><?= $errors['username'] ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- ایمیل -->
                        <div class="mb-4">
                            <label for="email" class="form-label">ایمیل:</label>
                            <div class="input-group">
                                <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control shadow-none" style="border-color: #9FACB9;" id="email" name="email"
                                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            </div>
                            <?php if (isset($errors['email'])): ?>
                                <span class="error-message"><?= $errors['email'] ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- شماره تماس -->
                        <div class="mb-4">
                            <label for="phone" class="form-label">شماره تماس:</label>
                            <div class="input-group">
                                <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-phone-alt"></i></span>
                                <input type="text" class="form-control shadow-none" style="border-color: #9FACB9;" id="phone" name="phone"
                                    value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                            </div>
                            <?php if (isset($errors['phone'])): ?>
                                <span class="error-message"><?= $errors['phone'] ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- رمز عبور -->
                        <div class="mb-4">
                            <label for="password" class="form-label">رمز عبور:</label>
                            <div class="input-group">
                                <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control shadow-none" style="border-color: #9FACB9;" id="password" name="password">
                            </div>
                            <?php if (isset($errors['password'])): ?>
                                <span class="error-message"><?= $errors['password'] ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- آدرس -->
                        <div class="mb-5">
                            <label for="address" class="form-label" style="border-color: #9FACB9;">آدرس:</label>
                            <div class="input-group">
                                <span class="input-group-text shadow-none" style="border-color: #9FACB9;"><i class="fas fa-map-marker-alt"></i></span>
                                <textarea rows="1" class="form-control shadow-none" style="border-color: #9FACB9;" id="address" name="address"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                            </div>
                            <?php if (isset($errors['address'])): ?>
                                <span class="error-message"><?= $errors['address'] ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- دکمه‌ها -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-user-plus me-2"></i>ایجاد کاربر
                            </button>
                            <a href="manage-users.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>بازگشت
                            </a>
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
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
                didOpen: () => {
                    Swal.showLoading();
                }
            }).then(() => {
                <?php if ($alert['type'] === 'success'): ?>
                    window.location.href = 'manage-users.php'; // بازگشت به همان صفحه برای ایجاد کاربر جدید
                <?php endif; ?>
            });
        </script>
    <?php endif; ?>

</body>

</html>