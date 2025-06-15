<?php
session_start();
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

// دریافت توضیحات سایز
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
$field_errors = [
    'name' => '',
    'product_description' => '',
    'price' => '',
    'stock' => '',
    'category_id' => '',
    'store_id' => '',
    'size_description' => '',
    'image' => '',
    'sizes' => '',
    'colors' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $product_description = trim($_POST['product_description'] ?? '');
    $price = $_POST['price'] ?? '';
    $stock = $_POST['stock'] ?? 0;
    $category_id = $_POST['category_id'] ?? null;
    $store_id = $_POST['store_id'] ?? null;
    $size_description = trim($_POST['size_description'] ?? '');
    $new_sizes = isset($_POST['sizes']) ? array_filter(array_map('trim', $_POST['sizes'])) : [];
    $new_colors = isset($_POST['colors']) ? array_filter(array_map('trim', $_POST['colors'])) : [];

    // اعتبارسنجی فیلدها
    if (empty($name)) {
        $field_errors['name'] = "نام محصول را وارد کنید.";
        $errors[] = $field_errors['name'];
    } elseif (strlen($name) > 255) {
        $field_errors['name'] = "نام محصول نمی‌تواند بیشتر از 255 کاراکتر باشد.";
        $errors[] = $field_errors['name'];
    }

    if (strlen($product_description) > 1000) {
        $field_errors['product_description'] = "توضیحات محصول نمی‌تواند بیشتر از 1000 کاراکتر باشد.";
        $errors[] = $field_errors['product_description'];
    }

    if (!is_numeric($price)) {
        $field_errors['price'] = "قیمت باید عددی باشد.";
        $errors[] = $field_errors['price'];
    } elseif ($price < 0) {
        $field_errors['price'] = "قیمت نمی‌تواند منفی باشد.";
        $errors[] = $field_errors['price'];
    }

    if (!is_numeric($stock)) {
        $field_errors['stock'] = "موجودی باید عددی باشد.";
        $errors[] = $field_errors['stock'];
    } elseif ($stock < 0) {
        $field_errors['stock'] = "موجودی نمی‌تواند منفی باشد.";
        $errors[] = $field_errors['stock'];
    }

    if (empty($category_id)) {
        $field_errors['category_id'] = "لطفا دسته‌بندی را انتخاب کنید.";
        $errors[] = $field_errors['category_id'];
    }

    if (empty($store_id)) {
        $field_errors['store_id'] = "لطفا فروشگاه را انتخاب کنید.";
        $errors[] = $field_errors['store_id'];
    }

    if (strlen($size_description) > 500) {
        $field_errors['size_description'] = "توضیحات سایز نمی‌تواند بیشتر از 500 کاراکتر باشد.";
        $errors[] = $field_errors['size_description'];
    }

    $image_path_db = $product['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            if (!in_array($_FILES['image']['type'], $allowed_types)) {
                $field_errors['image'] = "فقط فایل‌های تصویری jpg، png یا gif مجاز است.";
                $errors[] = $field_errors['image'];
            } elseif ($_FILES['image']['size'] > $max_size) {
                $field_errors['image'] = "حجم فایل نمی‌تواند بیشتر از 2 مگابایت باشد.";
                $errors[] = $field_errors['image'];
            } else {
                $upload_dir = '../../../Public/uploads/products/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $filename = uniqid() . '-' . basename($_FILES['image']['name']);
                $target_path = $upload_dir . $filename;

                if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                    $field_errors['image'] = "خطا در آپلود تصویر.";
                    $errors[] = $field_errors['image'];
                } else {
                    if ($image_path_db && file_exists('../../../' . $image_path_db)) {
                        unlink('../../../' . $image_path_db);
                    }
                    $image_path_db = '/Public/uploads/products/' . $filename;
                }
            }
        } else {
            $field_errors['image'] = "خطا در آپلود تصویر.";
            $errors[] = $field_errors['image'];
        }
    }

    if (empty($errors)) {
        // به‌روزرسانی اطلاعات اصلی محصول
        $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, stock=?, image=?, category_id=?, store_id=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("ssdisiii", $name, $product_description, $price, $stock, $image_path_db, $category_id, $store_id, $id);

        if ($stmt->execute()) {
            // حذف تمام رکوردهای قبلی جزئیات
            $conn->query("DELETE FROM detail WHERE product_id = $id");
            
            // ذخیره سایزهای جدید
            foreach ($new_sizes as $size) {
                if (!empty($size)) {
                    $stmt = $conn->prepare("INSERT INTO detail (product_id, size, description) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $id, $size, $size_description);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            
            // ذخیره رنگ‌های جدید
            foreach ($new_colors as $color) {
                if (!empty($color)) {
                    $stmt = $conn->prepare("INSERT INTO detail (product_id, color, description) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $id, $color, $size_description);
                    $stmt->execute();
                    $stmt->close();
                }
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
    <style>
        .error-border {
            border-color: #dc3545 !important;
        }
        .error-text {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card p-4 shadow">
                    <h4 class="text-danger mb-4 text-center">ویرایش محصول</h4>

                    <form method="POST" enctype="multipart/form-data" novalidate>
                        <div class="mb-3 text-center">
                            <label class="form-label d-block">تصویر فعلی</label>
                            <?php if ($product['image']): ?>
                                <img src="../../../<?= htmlspecialchars($product['image']) ?>" alt="تصویر محصول" class="img-fluid mb-2 d-block mx-auto" style="max-height: 150px;" />
                            <?php else: ?>
                                <span class="text-muted">تصویری وجود ندارد.</span>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">نام محصول</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-box"></i></span>
                                <input type="text" id="name" name="name" class="form-control shadow-none" value="<?= htmlspecialchars($_POST['name'] ?? $product['name']) ?>">
                            </div>
                            <?php if (!empty($field_errors['name'])): ?>
                                <div class="error-text"><?= htmlspecialchars($field_errors['name']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="product_description" class="form-label">توضیحات محصول</label>
                            <textarea id="product_description" name="product_description" class="form-control shadow-none" rows="3"><?= htmlspecialchars($_POST['product_description'] ?? $product['description']) ?></textarea>
                            <?php if (!empty($field_errors['product_description'])): ?>
                                <div class="error-text"><?= htmlspecialchars($field_errors['product_description']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="price" class="form-label">قیمت (تومان)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-money-bill-wave"></i></span>
                                    <input type="number" id="price" name="price" step="0.01" min="0" class="form-control shadow-none" value="<?= htmlspecialchars($_POST['price'] ?? $product['price']) ?>">
                                </div>
                                <?php if (!empty($field_errors['price'])): ?>
                                    <div class="error-text"><?= htmlspecialchars($field_errors['price']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label for="stock" class="form-label">موجودی </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-layer-group"></i></span>
                                    <input type="number" id="stock" name="stock" min="0" class="form-control shadow-none" value="<?= htmlspecialchars($_POST['stock'] ?? $product['stock']) ?>">
                                </div>
                                <?php if (!empty($field_errors['stock'])): ?>
                                    <div class="error-text"><?= htmlspecialchars($field_errors['stock']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="category_id" class="form-label">دسته‌بندی</label>
                                <select id="category_id" name="category_id" class="form-select shadow-none">
                                    <option value="">انتخاب دسته‌بندی</option>
                                    <?php while ($cat = $categories->fetch_assoc()): ?>
                                        <?php
                                        $isSelected = (isset($_POST['category_id']) 
                                            ? ($_POST['category_id'] == $cat['id'])
                                            : ($product['category_id'] == $cat['id']));
                                        ?>
                                        <option value="<?= $cat['id'] ?>" <?= $isSelected ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <?php if (!empty($field_errors['category_id'])): ?>
                                    <div class="error-text"><?= htmlspecialchars($field_errors['category_id']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label for="store_id" class="form-label">فروشگاه</label>
                                <select id="store_id" name="store_id" class="form-select shadow-none">
                                    <option value="">انتخاب فروشگاه</option>
                                    <?php while ($store = $stores->fetch_assoc()): ?>
                                        <?php
                                        $isSelected = (isset($_POST['store_id']) 
                                            ? ($_POST['store_id'] == $store['id'])
                                            : ($product['store_id'] == $store['id']));
                                        ?>
                                        <option value="<?= $store['id'] ?>" <?= $isSelected ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($store['name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <?php if (!empty($field_errors['store_id'])): ?>
                                    <div class="error-text"><?= htmlspecialchars($field_errors['store_id']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="image" class="form-label">آپلود تصویر جدید (در صورت نیاز)</label>
                            <input type="file" id="image" name="image" accept="image/*" class="form-control shadow-none">
                            <?php if (!empty($field_errors['image'])): ?>
                                <div class="error-text"><?= htmlspecialchars($field_errors['image']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- بخش سایزها -->
                        <div class="mb-3">
                            <label class="form-label">سایزهای موجود</label>
                            <div id="size-container">
                                <?php 
                                $sizes_to_display = !empty($_POST['sizes']) ? $_POST['sizes'] : $unique_sizes;
                                foreach ($sizes_to_display as $index => $size): ?>
                                    <div class="input-group mb-2">
                                        <span class="input-group-text">
                                            <i class="fas fa-ruler"></i>
                                        </span>
                                        <input type="text" name="sizes[]" class="form-control shadow-none" value="<?= htmlspecialchars($size) ?>">
                                        <button type="button" class="btn btn-outline-danger remove-size">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (!empty($field_errors['sizes'])): ?>
                                <div class="error-text"><?= htmlspecialchars($field_errors['sizes']) ?></div>
                            <?php endif; ?>
                            <button type="button" id="add-size" class="btn btn-sm btn-outline-success mt-2">
                                <i class="fas fa-plus"></i> افزودن سایز جدید
                            </button>
                        </div>

                        <!-- بخش رنگ‌ها -->
                        <div class="mb-3">
                            <label class="form-label">رنگ‌های موجود</label>
                            <div id="color-container">
                                <?php 
                                $colors_to_display = !empty($_POST['colors']) ? $_POST['colors'] : $unique_colors;
                                foreach ($colors_to_display as $index => $color): ?>
                                    <div class="input-group mb-2">
                                        <span class="input-group-text">
                                            <i class="fas fa-palette"></i>
                                        </span>
                                        <input type="text" name="colors[]" class="form-control shadow-none" value="<?= htmlspecialchars($color) ?>">
                                        <button type="button" class="btn btn-outline-danger remove-color">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (!empty($field_errors['colors'])): ?>
                                <div class="error-text"><?= htmlspecialchars($field_errors['colors']) ?></div>
                            <?php endif; ?>
                            <button type="button" id="add-color" class="btn btn-sm btn-outline-success mt-2">
                                <i class="fas fa-plus"></i> افزودن رنگ جدید
                            </button>
                        </div>

                        <!-- توضیحات سایز -->
                        <div class="mb-3">
                            <label for="size_description" class="form-label">توضیحات سایز بندی</label>
                            <textarea id="size_description" name="size_description" class="form-control shadow-none" rows="3"><?= htmlspecialchars($_POST['size_description'] ?? $size_description) ?></textarea>
                            <?php if (!empty($field_errors['size_description'])): ?>
                                <div class="error-text"><?= htmlspecialchars($field_errors['size_description']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-check me-2"></i>ذخیره تغییرات
                            </button>

                            <a href="manage-products.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>بازگشت
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
                <input type="text" name="sizes[]" class="form-control shadow-none" value="">
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
                <input type="text" name="colors[]" class="form-control shadow-none" value="">
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