<?php
include('../../../config/db.php');

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: manage-products.php");
    exit;
}

// دریافت اطلاعات محصول
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    $_SESSION['errors'] = ["محصول یافت نشد."];
    header("Location: manage-products.php");
    exit;
}

// دریافت سایزهای منحصر به فرد
$unique_sizes = [];
$stmt = $conn->prepare("SELECT DISTINCT size FROM detail WHERE product_id = ? AND size IS NOT NULL AND size != ''");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $unique_sizes[] = $row['size'];
}
$stmt->close();

// دریافت رنگ‌های منحصر به فرد
$unique_colors = [];
$stmt = $conn->prepare("SELECT DISTINCT color FROM detail WHERE product_id = ? AND color IS NOT NULL AND color != ''");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $unique_colors[] = $row['color'];
}
$stmt->close();

// دریافت توضیحات سایز (فرض می‌کنیم یک توضیح کلی برای سایزها داریم)
$size_description = '';
$stmt = $conn->prepare("SELECT description FROM detail WHERE product_id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $size_description = $row['description'];
}
$stmt->close();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $product_description = trim($_POST['product_description'] ?? '');
    $price = $_POST['price'] ?? '';
    $stock = $_POST['stock'] ?? 0;
    $category_id = $_POST['category_id'] ?? null;
    $store_id = $_POST['store_id'] ?? null;
    $size_description = trim($_POST['size_description'] ?? '');

    if ($name === '') $errors[] = "نام محصول را وارد کنید.";
    if (!is_numeric($price) || $price < 0) $errors[] = "قیمت معتبر نیست.";
    if (!is_numeric($stock) || $stock < 0) $errors[] = "موجودی معتبر نیست.";

    $image_path_db = $product['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES['image']['type'], $allowed_types)) {
                $errors[] = "فقط فایل‌های تصویری jpg، png یا gif مجاز است.";
            } else {
                $upload_dir = '../../../Public/uploads/products/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                $filename = uniqid() . '-' . basename($_FILES['image']['name']);
                $target_path = $upload_dir . $filename;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                    if ($image_path_db && file_exists('../../../' . $image_path_db)) {
                        unlink('../../../' . $image_path_db);
                    }
                    $image_path_db = '/Public/uploads/products/' . $filename;
                } else {
                    $errors[] = "خطا در آپلود تصویر.";
                }
            }
        } else {
            $errors[] = "خطا در آپلود تصویر.";
        }
    }

    if (empty($errors)) {
        // به‌روزرسانی اطلاعات اصلی محصول
        $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, stock=?, image=?, category_id=?, store_id=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("ssdisiii", $name, $product_description, $price, $stock, $image_path_db, $category_id, $store_id, $id);

        if ($stmt->execute()) {
            // حذف تمام رکوردهای قبلی جزئیات
            $conn->query("DELETE FROM detail WHERE product_id = $id");
            
            // دریافت سایزهای جدید از فرم
            $new_sizes = isset($_POST['sizes']) ? array_filter(array_map('trim', $_POST['sizes'])) : [];
            
            // دریافت رنگ‌های جدید از فرم
            $new_colors = isset($_POST['colors']) ? array_filter(array_map('trim', $_POST['colors'])) : [];
            
            // ذخیره سایزهای جدید
            foreach ($new_sizes as $size) {
                $stmt = $conn->prepare("INSERT INTO detail (product_id, size, description) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $id, $size, $size_description);
                $stmt->execute();
                $stmt->close();
            }
            
            // ذخیره رنگ‌های جدید
            foreach ($new_colors as $color) {
                $stmt = $conn->prepare("INSERT INTO detail (product_id, color, description) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $id, $color, $size_description);
                $stmt->execute();
                $stmt->close();
            }

            $_SESSION['success'] = "اطلاعات محصول با موفقیت به‌روزرسانی شد.";
            header("Location: manage-products.php");
            exit;
        } else {
            $errors[] = "خطا در به‌روزرسانی محصول.";
        }
        $stmt->close();
    }
}

$categories = $conn->query("SELECT id, name FROM categories ORDER BY name");
$stores = $conn->query("SELECT id, name FROM stores ORDER BY name");
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <title>ویرایش محصول</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" />
</head>

<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card p-4 shadow">
                    <h4 class="text-danger mb-4 text-center">ویرایش محصول</h4>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger text-center">
                            <ul class="mb-0">
                                <?php foreach ($errors as $e): ?>
                                    <li><?= htmlspecialchars($e) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" novalidate>
                        <div class="mb-3 text-center">
                            <label class="form-label d-block">تصویر فعلی</label>
                            <?php if ($product['image']): ?>
                                <?php $filename = basename($product['image']); ?>
                                <img src="../../../Public/uploads/products/<?= htmlspecialchars($filename) ?>" alt="تصویر محصول" class="img-fluid mb-2 d-block mx-auto" style="max-height: 150px;" />
                            <?php else: ?>
                                <span class="text-muted">تصویری وجود ندارد.</span>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">نام محصول</label>
                            <div class="input-group">
                                <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-box"></i></span>
                                <input type="text" id="name" name="name" class="form-control shadow-none" style="border-color: #9FACB9;" required value="<?= htmlspecialchars($_POST['name'] ?? $product['name']) ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="product_description" class="form-label" style="border-color: #9FACB9;">توضیحات محصول</label>
                            <textarea id="product_description" name="product_description" class="form-control shadow-none" style="border-color: #9FACB9;" rows="3"><?= htmlspecialchars($_POST['product_description'] ?? $product['description']) ?></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="price" class="form-label">قیمت (تومان)</label>
                                <div class="input-group">
                                    <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-money-bill-wave"></i></span>
                                    <input type="number" id="price" name="price" step="0.01" min="0" class="form-control shadow-none" style="border-color: #9FACB9;" required value="<?= htmlspecialchars($_POST['price'] ?? $product['price']) ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="stock" class="form-label">موجودی</label>
                                <div class="input-group">
                                    <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-layer-group"></i></span>
                                    <input type="number" id="stock" name="stock" min="0" class="form-control shadow-none" style="border-color: #9FACB9;" value="<?= htmlspecialchars($_POST['stock'] ?? $product['stock']) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="category_id" class="form-label">دسته‌بندی</label>
                                <select id="category_id" name="category_id" class="form-select shadow-none" style="border-color: #9FACB9;">
                                    <option value="">انتخاب دسته‌بندی</option>
                                    <?php while ($cat = $categories->fetch_assoc()): ?>
                                        <option value="<?= $cat['id'] ?>" <?= ((isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) || (!isset($_POST['category_id']) && $product['category_id'] == $cat['id'])) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="store_id" class="form-label">فروشگاه</label>
                                <select id="store_id" name="store_id" class="form-select shadow-none" style="border-color: #9FACB9;">
                                    <option value="">انتخاب فروشگاه</option>
                                    <?php while ($store = $stores->fetch_assoc()): ?>
                                        <option value="<?= $store['id'] ?>" <?= ((isset($_POST['store_id']) && $_POST['store_id'] == $store['id']) || (!isset($_POST['store_id']) && $product['store_id'] == $store['id'])) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($store['name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="image" class="form-label">آپلود تصویر جدید (در صورت نیاز)</label>
                            <input type="file" id="image" name="image" accept="image/*" class="form-control shadow-none" style="border-color: #9FACB9;" />
                        </div>

                        <!-- بخش سایزها -->
                        <div class="mb-3">
                            <label class="form-label">سایزهای موجود</label>
                            <div id="size-container">
                                <?php foreach ($unique_sizes as $index => $size): ?>
                                    <div class="input-group mb-2">
                                        <span class="input-group-text" style="border-color: #9FACB9;">
                                            <i class="fas fa-ruler"></i>
                                        </span>
                                        <input type="text" name="sizes[]" class="form-control shadow-none" style="border-color: #9FACB9;" value="<?= htmlspecialchars($size) ?>">
                                        <button type="button" class="btn btn-outline-danger remove-size" style="border-color: #9FACB9;" data-index="<?= $index ?>">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="add-size" class="btn btn-sm btn-outline-success mt-2">
                                <i class="fas fa-plus"></i> افزودن سایز جدید
                            </button>
                        </div>

                        <!-- بخش رنگ‌ها -->
                        <div class="mb-3">
                            <label class="form-label">رنگ‌های موجود</label>
                            <div id="color-container">
                                <?php foreach ($unique_colors as $index => $color): ?>
                                    <div class="input-group mb-2">
                                        <span class="input-group-text" style="border-color: #9FACB9;">
                                            <i class="fas fa-palette"></i>
                                        </span>
                                        <input type="text" name="colors[]" class="form-control shadow-none" style="border-color: #9FACB9;" value="<?= htmlspecialchars($color) ?>">
                                        <button type="button" class="btn btn-outline-danger remove-color" style="border-color: #9FACB9;" data-index="<?= $index ?>">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="add-color" class="btn btn-sm btn-outline-success mt-2">
                                <i class="fas fa-plus"></i> افزودن رنگ جدید
                            </button>
                        </div>

                        <!-- توضیحات سایز -->
                        <div class="mb-3">
                            <label for="size_description" class="form-label" style="border-color: #9FACB9;">توضیحات سایز بندی</label>
                            <textarea id="size_description" name="size_description" class="form-control shadow-none" style="border-color: #9FACB9;" rows="3"><?= htmlspecialchars($_POST['size_description'] ?? $size_description) ?></textarea>
                        </div>

                        <div class="d-flex justify-content-center gap-3">
                            <button type="submit" class="btn btn-danger" style="width: 45%;">ذخیره</button>
                            <a href="manage-products.php" class="btn btn-secondary" style="width: 45%;">بازگشت</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // افزودن سایز جدید
        document.getElementById('add-size').addEventListener('click', function() {
            const container = document.getElementById('size-container');
            const div = document.createElement('div');
            div.className = 'input-group mb-2';
            div.innerHTML = `
                <span class="input-group-text">
                    <i class="fas fa-ruler"></i>
                </span>
                <input type="text" name="sizes[]" class="form-control shadow-none border" value="">
                <button type="button" class="btn btn-outline-danger remove-size">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(div);
            
            // اضافه کردن رویداد برای دکمه حذف
            div.querySelector('.remove-size').addEventListener('click', function() {
                div.remove();
            });
        });

        // افزودن رنگ جدید
        document.getElementById('add-color').addEventListener('click', function() {
            const container = document.getElementById('color-container');
            const div = document.createElement('div');
            div.className = 'input-group mb-2';
            div.innerHTML = `
                <span class="input-group-text">
                    <i class="fas fa-palette"></i>
                </span>
                <input type="text" name="colors[]" class="form-control shadow-none border" value="">
                <button type="button" class="btn btn-outline-danger remove-color">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(div);
            
            // اضافه کردن رویداد برای دکمه حذف
            div.querySelector('.remove-color').addEventListener('click', function() {
                div.remove();
            });
        });

        // حذف سایزهای موجود
        document.querySelectorAll('.remove-size').forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.input-group').remove();
            });
        });

        // حذف رنگ‌های موجود
        document.querySelectorAll('.remove-color').forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.input-group').remove();
            });
        });
    </script>
</body>

</html>