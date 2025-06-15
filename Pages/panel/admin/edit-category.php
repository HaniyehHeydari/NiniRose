<?php
session_start();
include('../../../config/db.php');

// بررسی دسترسی کاربر
if (!isset($_SESSION['user']['role']) || !in_array($_SESSION['user']['role'], ['super_admin', 'store_admin'])) {
    die("دسترسی غیرمجاز");
}

$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$errors = [];

if (!$category_id) {
    header("Location: manage-categories.php?error=no_id");
    exit;
}

// دریافت دسته‌بندی فعلی
$sql = "SELECT * FROM categories WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("دسته‌بندی یافت نشد.");
}
$category = $result->fetch_assoc();
$stmt->close();

// بررسی دسترسی ادمین فروشگاه
if ($_SESSION['user']['role'] === 'store_admin') {
    $sessionStoreId = $_SESSION['store_id'] ?? ($_SESSION['user']['store_id'] ?? null);
    if (!$sessionStoreId || $sessionStoreId != $category['store_id']) {
        die("شما مجاز به ویرایش این دسته‌بندی نیستید.");
    }
}

// دریافت لیست فروشگاه‌ها برای سوپر ادمین
if ($_SESSION['user']['role'] === 'super_admin') {
    $stores = $conn->query("SELECT id, name FROM stores ORDER BY name");
}

// پردازش فرم
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $name = trim($_POST['name']);
    
    // تعیین store_id (اختیاری برای سوپر ادمین)
    $store_id = $_SESSION['user']['role'] === 'super_admin' ? 
                (!empty($_POST['store_id']) ? intval($_POST['store_id']) : null) : 
                $_SESSION['user']['store_id'];

    // اعتبارسنجی نام
    if (empty($name)) {
        $errors['name'] = "لطفا نام دسته‌بندی را وارد کنید";
    } elseif (!preg_match("/^[\x{0600}-\x{06FF}\s]{3,}$/u", $name)) {
        $errors['name'] = "لطفا یک نام معتبر وارد کنید";
    } else {
        // بررسی تکراری نبودن نام در همان فروشگاه (به جز دسته‌بندی فعلی)
        $check_sql = "SELECT id FROM categories WHERE name = ? 
                     AND (store_id = ? OR (? IS NULL AND store_id IS NULL))
                     AND id != ?";
        
        $stmt_check = $conn->prepare($check_sql);
        $stmt_check->bind_param("sisi", $name, $store_id, $store_id, $category_id);
        $stmt_check->execute();
        $stmt_check->store_result();
        
        if ($stmt_check->num_rows > 0) {
            $errors['name'] = "این نام دسته‌بندی قبلاً ثبت شده است";
        }
        $stmt_check->close();
    }

    // اگر خطایی وجود نداشت، بروزرسانی انجام شود
    if (empty($errors)) {
        $update_sql = "UPDATE categories SET name = ?, store_id = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sii", $name, $store_id, $category_id);

        try {
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "دسته‌بندی با موفقیت به‌روزرسانی شد.";
                header("Location: manage-categories.php?message=updated");
                exit;
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $errors['name'] = "این نام دسته‌بندی قبلاً ثبت شده است";
            } else {
                $errors['general'] = "خطا در به‌روزرسانی: " . $e->getMessage();
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
    <title>ویرایش دسته‌بندی</title>
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
                    <h5 class="text-danger mb-4 text-center">ویرایش دسته‌بندی</h5>

                    <?php if (isset($errors['general'])): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
                    <?php endif; ?>

                    <form method="POST" id="categoryForm">
                        <input type="hidden" name="update_category" value="1">
                        <div class="mb-4">
                            <label for="name" class="form-label">نام دسته‌بندی</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                <input type="text" id="name" class="form-control shadow-none" 
                                    style="border-color: #9FACB9;" name="name" 
                                    value="<?= htmlspecialchars($_POST['name'] ?? $category['name'] ?? '') ?>">
                            </div>
                            <?php if (isset($errors['name'])): ?>
                                <div class="error-message"><?= $errors['name'] ?></div>
                            <?php endif; ?>
                        </div>

                        <?php if ($_SESSION['user']['role'] === 'super_admin'): ?>
                            <div class="mb-4">
                                <label for="store_id" class="form-label">نام فروشگاه</label>
                                <select name="store_id" id="store_id" class="form-select shadow-none" style="border-color: #9FACB9;">
                                    <option value="">انتخاب فروشگاه</option>
                                    <?php while ($store = $stores->fetch_assoc()): ?>
                                        <option value="<?= $store['id'] ?>" <?= ($store['id'] == ($_POST['store_id'] ?? $category['store_id'] ?? '')) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($store['name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="store_id" value="<?= $_SESSION['user']['store_id'] ?>">
                        <?php endif; ?>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-check me-2"></i>ذخیره تغییرات
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