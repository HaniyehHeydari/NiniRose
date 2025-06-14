<?php
session_start();
include('../../../config/db.php');

// بررسی لاگین بودن کاربر
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    $_SESSION['alert'] = [
        'type' => 'error',
        'message' => 'برای ارسال درخواست، ابتدا وارد حساب کاربری شوید.'
    ];
    header('Location: ../../auth/login.php');
    exit;
}

$errors = [];
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);

// پردازش فرم ارسال شده
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_id = $_SESSION['user']['id'];
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $status = 0; // وضعیت پیش‌فرض: در انتظار تایید

    // اعتبارسنجی نام مغازه
    if (empty($name)) {
        $errors['name'] = 'لطفا نام مغازه را وارد کنید';
    } elseif (!preg_match('/^[\x{0600}-\x{06FF}\s]{3,}$/u', $name)) {
        $errors['name'] = 'نام مغازه باید بیش از ۳ کاراکتر و فقط شامل حروف فارسی باشد';
    } else {
        // بررسی تکراری نبودن نام مغازه
        $stmt_check = $conn->prepare("SELECT id FROM stores WHERE name = ?");
        $stmt_check->bind_param("s", $name);
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
        $errors['address'] = 'آدرس باید بیش از ۱۰ کاراکتر باشد';
    }

    // اعتبارسنجی شماره تماس
    if (empty($phone)) {
        $errors['phone'] = 'لطفا شماره تماس را وارد کنید';
    } elseif (!preg_match('/^09\d{9}$/', $phone)) {
        $errors['phone'] = 'شماره تماس باید با 09 شروع شود و 11 رقم باشد';
    }

    try {
        if (empty($errors)) {
            $stmt = $conn->prepare("INSERT INTO stores (admin_id, name, address, phone, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isssi", $admin_id, $name, $address, $phone, $status);
            
            if ($stmt->execute()) {
                $_SESSION['alert'] = [
                    'type' => 'success',
                    'message' => 'درخواست شما با موفقیت ثبت شد و در انتظار تایید است.'
                ];
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            $errors['name'] = 'این نام مغازه قبلاً ثبت شده است';
        } else {
            $errors['database'] = 'خطا در ثبت اطلاعات: ' . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['old_input'] = [
            'name' => $name,
            'address' => $address,
            'phone' => $phone
        ];
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
}

// بازیابی خطاها و مقادیر قبلی از سشن
$errors = $_SESSION['form_errors'] ?? [];
$oldInput = $_SESSION['old_input'] ?? [];
unset($_SESSION['form_errors']);
unset($_SESSION['old_input']);
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>درخواست همکاری</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .form-container {
            max-width: 500px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
        }
        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: block;
        }
        .input-group-text {
            background-color: #f8f9fa;
            border-color: #ced4da;
        }
        .form-control {
            border-color: #ced4da;
        }
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        .swal2-popup {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="form-container animate__animated animate__fadeIn">
            <h5 class="text-center mb-4">فرم درخواست مغازه</h5>
            <form method="POST" action="">
                <!-- نام مغازه -->
                <div class="mb-3">
                    <label for="name" class="form-label">نام مغازه</label>
                    <div class="input-group">
                        <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-store"></i></span>
                        <input type="text" class="form-control shadow-none" style="border-color: #9FACB9;" id="name" name="name" 
                               value="<?= htmlspecialchars($oldInput['name'] ?? '') ?>">
                    </div>
                    <?php if (isset($errors['name'])): ?>
                        <span class="error-message"><?= $errors['name'] ?></span>
                    <?php endif; ?>
                </div>

                <!-- آدرس -->
                <div class="mb-3">
                    <label for="address" class="form-label">آدرس</label>
                    <div class="input-group">
                        <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-map-marker-alt"></i></span>
                        <textarea rows="1" class="form-control shadow-none" style="border-color: #9FACB9;" id="address" name="address"><?= htmlspecialchars($oldInput['address'] ?? '') ?></textarea>
                    </div>
                    <?php if (isset($errors['address'])): ?>
                        <span class="error-message"><?= $errors['address'] ?></span>
                    <?php endif; ?>
                </div>

                <!-- شماره تماس -->
                <div class="mb-4">
                    <label for="phone" class="form-label">شماره تماس</label>
                    <div class="input-group">
                        <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-phone-alt"></i></span>
                        <input type="text" class="form-control shadow-none" style="border-color: #9FACB9;" id="phone" name="phone" 
                               value="<?= htmlspecialchars($oldInput['phone'] ?? '') ?>">
                    </div>
                    <?php if (isset($errors['phone'])): ?>
                        <span class="error-message"><?= $errors['phone'] ?></span>
                    <?php endif; ?>
                </div>

                <!-- دکمه‌ها -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-danger py-2">
                        <i class="fas fa-paper-plane me-2"></i>ارسال درخواست
                    </button>
                    <a href="../../view/MainPage.php" class="btn btn-secondary py-2">
                        <i class="fas fa-arrow-left me-2"></i>بازگشت
                    </a>
                </div>
            </form>
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