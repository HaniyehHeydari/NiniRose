<?php
include('../../../config/db.php');

// فقط سوپر ادمین و ادمین فروشگاه اجازه دسترسی دارند
if (!isset($_SESSION['user']['role']) || !in_array($_SESSION['user']['role'], ['super_admin', 'store_admin'])) {
    die("دسترسی غیرمجاز");
}

$user_role = $_SESSION['user']['role'] ?? null;
$store_id = $_SESSION['user']['store_id'] ?? null;

// فقط برای سوپر ادمین: دریافت لیست فروشگاه‌های تایید شده و فعال
if ($user_role === 'super_admin') {
    // تغییر کوئری برای فقط فروشگاه‌های فعال و تایید شده
    $stores = $conn->query("SELECT id, name FROM stores WHERE status = '1' ORDER BY name");
}

// پردازش فرم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $error = null;

    if (empty($name)) {
        $error = "نام دسته‌بندی نمی‌تواند خالی باشد.";
    } else {
        if ($user_role === 'store_admin') {
            if (!$store_id) {
                die("شناسه فروشگاه تنظیم نشده است.");
            }
        } else {
            // سوپر ادمین باید فروشگاه را انتخاب کند
            $store_id = $_POST['store_id'] ?? null;
            if (!$store_id) {
                $error = "لطفاً یک فروشگاه انتخاب کنید.";
            }
        }

        if (!$error) {
            $stmt = $conn->prepare("INSERT INTO categories (name, store_id, created_at) VALUES (?, ?, NOW())");
            $stmt->bind_param("si", $name, $store_id);

            if ($stmt->execute()) {
                header("Location: manage-categories.php?message=created");
                exit;
            } else {
                $error = "خطا در ثبت دسته‌بندی: " . $stmt->error;
            }

            $stmt->close();
        }
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
</head>
<body dir="rtl">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6 mt-4">
            <div class="card p-5 shadow">
                <h5 class="text-danger mb-4 text-center">افزودن دسته‌بندی جدید</h5>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-4">
                        <label for="name" class="form-label">نام دسته‌بندی:</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-tag"></i></span>
                            <input type="text" class="form-control shadow-none border" id="name" name="name" required>
                        </div>
                    </div>

                    <?php if ($user_role === 'super_admin'): ?>
                        <div class="mb-4">
                            <label for="store_id" class="form-label">انتخاب فروشگاه:</label>
                            <select name="store_id" id="store_id" class="form-select shadow-none border" required>
                                <option value="">انتخاب فروشگاه</option>
                                <?php while ($store = $stores->fetch_assoc()): ?>
                                    <option value="<?= $store['id'] ?>"><?= htmlspecialchars($store['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="store_id" value="<?= htmlspecialchars($store_id) ?>">
                    <?php endif; ?>

                    <div class="d-flex justify-content-center gap-4">
                        <button type="submit" class="btn btn-danger" style="width: 45%;">افزودن</button>
                        <a href="manage-categories.php" class="btn btn-secondary" style="width: 45%;">بازگشت</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
