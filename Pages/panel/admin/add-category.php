<?php
include('../../../config/db.php');

// فقط سوپر ادمین و ادمین فروشگاه اجازه دسترسی دارند
if (!isset($_SESSION['user']['role']) || !in_array($_SESSION['user']['role'], ['super_admin', 'store_admin'])) {
    die("دسترسی غیرمجاز");
}

$user_role = $_SESSION['user']['role'] ?? null;
$store_id = $_SESSION['user']['store_id'] ?? null;
$errors = [];

// فقط برای سوپر ادمین: دریافت لیست فروشگاه‌های تایید شده و فعال
if ($user_role === 'super_admin') {
    $stores = $conn->query("SELECT id, name FROM stores WHERE status = '1' ORDER BY name");
}

// پردازش فرم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    
    // اعتبارسنجی نام
    if (empty($name)) {
        $errors['name'] = "لطفا نام دسته‌بندی را وارد کنید";
    } elseif (!preg_match("/^[\x{0600}-\x{06FF}\s]{3,}$/u", $name)) {
        $errors['name'] = "لطفا یک نام معتبر وارد کنید";
    }

    // تعیین store_id بر اساس نقش کاربر
    if ($user_role === 'store_admin') {
        // برای ادمین فروشگاه، از store_id کاربر استفاده می‌کنیم
        if (!$store_id) {
            die("شناسه فروشگاه تنظیم نشده است.");
        }
    } else {
        // برای سوپر ادمین، فروشگاه اختیاری است
        $store_id = !empty($_POST['store_id']) ? intval($_POST['store_id']) : null;
    }

    // بررسی تکراری نبودن نام دسته‌بندی
    if (empty($errors)) {
        $check_sql = "SELECT id FROM categories WHERE name = ?" . ($store_id ? " AND store_id = ?" : "");
        $stmt_check = $conn->prepare($check_sql);
        
        if ($store_id) {
            $stmt_check->bind_param("si", $name, $store_id);
        } else {
            $stmt_check->bind_param("s", $name);
        }
        
        $stmt_check->execute();
        $stmt_check->store_result();
        
        if ($stmt_check->num_rows > 0) {
            $errors['name'] = $store_id 
                ? "این نام دسته‌بندی قبلاً برای این فروشگاه ثبت شده است" 
                : "این نام دسته‌بندی قبلاً ثبت شده است";
        }
        $stmt_check->close();
    }

    // اگر خطایی وجود نداشت، ثبت انجام شود
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO categories (name, store_id, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("si", $name, $store_id);

        try {
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "دسته‌بندی با موفقیت ایجاد شد.";
                header("Location: manage-categories.php");
                exit;
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) { // کد خطای duplicate entry
                $errors['name'] = " نام دسته‌بندی تکرای است";
            } else {
                $errors['general'] = "خطا در ثبت دسته‌بندی: " . $e->getMessage();
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>افزودن دسته‌بندی</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .error-message {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }
        .is-invalid {
            border-color: #dc3545 !important;
        }
        .input-group-text {
            border-color: #9FACB9;
        }
    </style>
</head>
<body dir="rtl">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6 mt-4">
            <div class="card p-5 shadow">
                <h5 class="text-danger mb-4 text-center">افزودن دسته‌بندی جدید</h5>

                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-danger text-center"><?= htmlspecialchars($errors['general']) ?></div>
                <?php endif; ?>

                <form method="POST" id="categoryForm">
                    <div class="mb-4">
                        <label for="name" class="form-label">نام دسته‌بندی:</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-tag"></i></span>
                            <input type="text" class="form-control shadow-none" 
                                style="border-color: #9FACB9;" id="name" name="name" 
                                value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                        </div>
                        <?php if (isset($errors['name'])): ?>
                            <div class="error-message"><?= $errors['name'] ?></div>
                        <?php endif; ?>
                    </div>

                    <?php if ($user_role === 'super_admin'): ?>
                        <div class="mb-4">
                            <label for="store_id" class="form-label">انتخاب فروشگاه (اختیاری):</label>
                            <select name="store_id" id="store_id" class="form-select shadow-none" 
                                style="border-color: #9FACB9;">
                                <option value="">انتخاب فروشگاه</option>
                                <?php while ($store = $stores->fetch_assoc()): ?>
                                    <option value="<?= $store['id'] ?>" <?= ($store['id'] == ($_POST['store_id'] ?? '')) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($store['name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="store_id" value="<?= htmlspecialchars($store_id) ?>">
                    <?php endif; ?>

                    <div class="d-grid gap-2">
    <button type="submit" class="btn btn-danger">
        <i class="fas fa-plus-circle me-2"></i>ایجاد دسته‌بندی
    </button>

    <a href="manage-categories.php" class="btn btn-secondary">
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