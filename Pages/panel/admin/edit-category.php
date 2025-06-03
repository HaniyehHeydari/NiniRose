<?php
include('../../../config/db.php');

if (!isset($_SESSION['user']['role']) || !in_array($_SESSION['user']['role'], ['super_admin', 'store_admin'])) {
    die("دسترسی غیرمجاز");
}

$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

if (!$category_id) {
    header("Location: manage-categories.php?error=no_id");
    exit;
}

// دریافت دسته‌بندی فعلی
$sql = "SELECT * FROM categories WHERE id = $category_id";
$result = $conn->query($sql);
if ($result->num_rows === 0) {
    die("دسته‌بندی یافت نشد.");
}
$category = $result->fetch_assoc();

// فقط اجازه به ادمین فروشگاه برای ویرایش دسته‌بندی خودش
if ($_SESSION['user']['role'] === 'store_admin') {
    $sessionStoreId = $_SESSION['store_id'] ?? ($_SESSION['user']['store_id'] ?? null);
    if (!$sessionStoreId || $sessionStoreId != $category['store_id']) {
        die("شما مجاز به ویرایش این دسته‌بندی نیستید.");
    }
}


// فقط سوپر ادمین نیاز به لیست فروشگاه‌ها دارد
if ($_SESSION['user']['role'] === 'super_admin') {
    $stores = $conn->query("SELECT id, name FROM stores ORDER BY name");
}

// بروزرسانی اطلاعات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $name = trim($_POST['name']);

    // تعیین store_id بر اساس نقش
    if ($_SESSION['user']['role'] === 'super_admin') {
        $store_id = intval($_POST['store_id']);
    } else {
        $store_id = $_SESSION['user']['store_id'];
    }

    if (empty($name) || !$store_id) {
        die("نام یا فروشگاه نامعتبر است.");
    }

    $stmt = $conn->prepare("UPDATE categories SET name = ?, store_id = ? WHERE id = ?");
    $stmt->bind_param("sii", $name, $store_id, $category_id);

    if ($stmt->execute()) {
        header("Location: manage-categories.php?message=updated");
        exit;
    } else {
        die("خطا در بروزرسانی: " . $stmt->error);
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

    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body dir="rtl">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6 mt-4">
                <div class="card p-5 shadow">
                    <h5 class="text-danger mb-4 text-center">ویرایش دسته‌بندی</h5>

                    <form method="POST">
                        <input type="hidden" name="update_category" value="1">

                        <div class="mb-4">
                            <label for="name" class="form-label">نام دسته‌بندی:</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                <input type="text" name="name" id="name" class="form-control shadow-none border" required value="<?= htmlspecialchars($category['name']) ?>">
                            </div>
                        </div>

                        <?php if ($_SESSION['user']['role'] === 'super_admin'): ?>
                            <div class="mb-4">
                                <label for="store_id" class="form-label">انتخاب فروشگاه:</label>
                                <select name="store_id" id="store_id" class="form-select shadow-none border" required>
                                    <option value="">انتخاب فروشگاه</option>
                                    <?php while ($store = $stores->fetch_assoc()): ?>
                                        <option value="<?= $store['id'] ?>" <?= $store['id'] == $category['store_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($store['name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="store_id" value="<?= $_SESSION['user']['store_id'] ?>">
                        <?php endif; ?>

                        <div class="d-flex justify-content-center gap-4">
                            <button type="submit" class="btn btn-danger" style="width: 45%;">ذخیره تغییرات</button>
                            <a href="manage-categories.php" class="btn btn-secondary" style="width: 45%;">بازگشت</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

</body>

</html>