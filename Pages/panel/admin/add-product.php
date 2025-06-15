<?php
include('../../../config/db.php');

// بررسی نقش کاربر
$user_role = $_SESSION['user']['role'] ?? null;
$store_id = $_SESSION['user']['store_id'] ?? null;

// Initialize error arrays
$errors = [];
$field_errors = [
    'name' => '',
    'description' => '',
    'price' => '',
    'stock' => '',
    'category_id' => '',
    'store_id' => '',
    'sizes' => '',
    'colors' => '',
    'size_description' => '',
    'image' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = $_POST['price'] ?? '';
    $stock = $_POST['stock'] ?? 0;
    $category_id = $_POST['category_id'] ?? null;
    $sizes = $_POST['sizes'] ?? [];
    $colors = $_POST['colors'] ?? [];
    $size_description = trim($_POST['size_description'] ?? '');

    // اگر ادمین فروشگاه هست، store_id از session خوانده شود
    if ($user_role === 'store_admin') {
        $store_id = $_SESSION['user']['store_id'];
    } else {
        $store_id = $_POST['store_id'] ?? null;
    }

    // اعتبارسنجی فیلدها
    if (empty($name)) {
        $field_errors['name'] = "نام محصول الزامی است.";
        $errors[] = $field_errors['name'];
    } elseif (strlen($name) > 255) {
        $field_errors['name'] = "نام محصول نمی‌تواند بیشتر از 255 کاراکتر باشد.";
        $errors[] = $field_errors['name'];
    }

    if (strlen($description) > 1000) {
        $field_errors['description'] = "توضیحات نمی‌تواند بیشتر از 1000 کاراکتر باشد.";
        $errors[] = $field_errors['description'];
    }

    if (!is_numeric($price) || $price < 0) {
        $field_errors['price'] = "قیمت باید عددی مثبت باشد.";
        $errors[] = $field_errors['price'];
    }

    if (!is_numeric($stock) || $stock < 0) {
        $field_errors['stock'] = "موجودی باید عددی مثبت باشد.";
        $errors[] = $field_errors['stock'];
    }

    if (empty($category_id)) {
        $field_errors['category_id'] = "انتخاب دسته‌بندی الزامی است.";
        $errors[] = $field_errors['category_id'];
    }

    if (empty($store_id)) {
        $field_errors['store_id'] = "انتخاب فروشگاه الزامی است.";
        $errors[] = $field_errors['store_id'];
    }

    if (strlen($size_description) > 500) {
        $field_errors['size_description'] = "توضیحات سایز نمی‌تواند بیشتر از 500 کاراکتر باشد.";
        $errors[] = $field_errors['size_description'];
    }

    // اعتبارسنجی تصویر
    $image_path_db = null;
    $image_required = true; // تصویر الزامی است
    
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
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $filename = uniqid() . '-' . basename($_FILES['image']['name']);
                $target_path = $upload_dir . $filename;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                    $image_path_db = '/Public/uploads/products/' . $filename;
                } else {
                    $field_errors['image'] = "خطا در آپلود تصویر.";
                    $errors[] = $field_errors['image'];
                }
            }
        } else {
            $field_errors['image'] = "خطا در آپلود تصویر.";
            $errors[] = $field_errors['image'];
        }
    } elseif ($image_required) {
        $field_errors['image'] = "انتخاب تصویر الزامی است.";
        $errors[] = $field_errors['image'];
    }

    if (empty($errors)) {
        // کد ذخیره در دیتابیس
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, stock, image, category_id, store_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdisii", $name, $description, $price, $stock, $image_path_db, $category_id, $store_id);

        if ($stmt->execute()) {
            // کدهای بعدی برای ذخیره سایزها و رنگ‌ها
            $_SESSION['success'] = "محصول جدید با موفقیت ایجاد شد.";
            header("Location: manage-products.php");
            exit;
        } else {
            $errors[] = "خطا در ذخیره اطلاعات محصول.";
            if ($image_path_db) unlink('../../../' . $image_path_db);
        }
        $stmt->close();
    }
}

// دریافت دسته‌بندی‌ها
if (($_SESSION['user']['role'] === 'store_admin') && $store_id) {
    $stmt = $conn->prepare("SELECT id, name FROM categories WHERE store_id = ? ORDER BY name");
    $stmt->bind_param("i", $store_id);
    $stmt->execute();
    $categories = $stmt->get_result();
    $stmt->close();
} else {
    $categories = $conn->query("SELECT id, name FROM categories ORDER BY name");
}

// فقط برای سوپر ادمین: فقط فروشگاه‌های تایید شده و فعال
if (($_SESSION['user']['role'] === 'super_admin')) {
    $stores = $conn->query("SELECT id, name FROM stores WHERE status = '1' ORDER BY name");
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <title>افزودن محصول جدید</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" />
    <style>
        .error-text {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        .error-border {
            border-color: #dc3545 !important;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card p-4 shadow">
                    <h4 class="text-danger mb-4 text-center">افزودن محصول جدید</h4>

                    <form method="POST" enctype="multipart/form-data" novalidate>
                        <div class="mb-3">
                            <label class="form-label">نام محصول</label>
                            <div class="input-group">
                                <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-box"></i></span>
                                <input type="text" name="name" class="form-control shadow-none" style="border-color: #9FACB9;" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                            </div>
                            <?php if (!empty($field_errors['name'])): ?>
                                <div class="error-text"><?= htmlspecialchars($field_errors['name']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">توضیحات</label>
                            <textarea name="description" class="form-control shadow-none" style="border-color: #9FACB9;" rows="3"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                            <?php if (!empty($field_errors['description'])): ?>
                                <div class="error-text"><?= htmlspecialchars($field_errors['description']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">قیمت (تومان)</label>
                                <div class="input-group">
                                    <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-money-bill-wave"></i></span>
                                    <input type="number" name="price" step="0.01" min="0" class="form-control shadow-none" style="border-color: #9FACB9;" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
                                </div>
                                <?php if (!empty($field_errors['price'])): ?>
                                    <div class="error-text"><?= htmlspecialchars($field_errors['price']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">موجودی</label>
                                <div class="input-group">
                                    <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-layer-group"></i></span>
                                    <input type="number" name="stock" min="0" class="form-control shadow-none" style="border-color: #9FACB9;" value="<?= htmlspecialchars($_POST['stock'] ?? '0') ?>">
                                </div>
                                <?php if (!empty($field_errors['stock'])): ?>
                                    <div class="error-text"><?= htmlspecialchars($field_errors['stock']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row mb-3">
    <div class="col-md-6 mb-3 mb-md-0">
        <label class="form-label">دسته‌بندی</label>
        <select name="category_id" class="form-select shadow-none" style="border-color: #9FACB9;">
            <option value="">انتخاب دسته‌بندی</option>
            <?php while ($cat = $categories->fetch_assoc()): ?>
                <option value="<?= $cat['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
        <?php if (!empty($field_errors['category_id'])): ?>
            <div class="error-text"><?= htmlspecialchars($field_errors['category_id']) ?></div>
        <?php endif; ?>
    </div>

    <?php if ($user_role === 'super_admin'): ?>
        <div class="col-md-6">
            <label class="form-label">فروشگاه</label>
            <select name="store_id" class="form-select shadow-none" style="border-color: #9FACB9;">
                <option value="">انتخاب فروشگاه</option>
                <?php while ($store = $stores->fetch_assoc()): ?>
                    <option value="<?= $store['id'] ?>" <?= (isset($_POST['store_id']) && $_POST['store_id'] == $store['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($store['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <?php if (!empty($field_errors['store_id'])): ?>
                <div class="error-text"><?= htmlspecialchars($field_errors['store_id']) ?></div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <input type="hidden" name="store_id" value="<?= $store_id ?>">
    <?php endif; ?>
</div>
                        <div class="mb-3">
                            <label class="form-label">سایزهای محصول</label>
                            <div id="sizeContainer">
                                <div class="input-group mb-2">
                                    <input type="text" name="sizes[]" class="form-control shadow-none" style="border-color: #9FACB9;" placeholder="مثلاً: S یا 3-6 ماه" />
                                    <button type="button" class="btn btn-outline-secondary remove-size" style="border-color: #9FACB9;">-</button>
                                </div>
                            </div>
                            <?php if (!empty($field_errors['sizes'])): ?>
                                <div class="error-text"><?= htmlspecialchars($field_errors['sizes']) ?></div>
                            <?php endif; ?>
                            <button type="button" class="btn btn-sm btn-outline-success" id="addSizeBtn">افزودن سایز</button>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">رنگ‌های موجود</label>
                            <div id="colors-wrapper">
                                <div class="input-group mb-2">
                                    <input type="text" name="colors[]" class="form-control shadow-none" style="border-color: #9FACB9;" placeholder="مثلاً: قرمز" />
                                    <button type="button" class="btn btn-outline-secondary remove-size" style="border-color: #9FACB9;">-</button>
                                </div>
                            </div>
                            <?php if (!empty($field_errors['colors'])): ?>
                                <div class="error-text"><?= htmlspecialchars($field_errors['colors']) ?></div>
                            <?php endif; ?>
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="addColorInput()">افزودن رنگ جدید</button>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">توضیحات سایز بندی</label>
                            <textarea name="size_description" class="form-control shadow-none <?= !empty($field_errors['size_description']) ? 'error-border' : '' ?>" style="border-color: #9FACB9;" rows="3"><?= htmlspecialchars($_POST['size_description'] ?? '') ?></textarea>
                            <?php if (!empty($field_errors['size_description'])): ?>
                                <div class="error-text"><?= htmlspecialchars($field_errors['size_description']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">تصویر محصول</label>
                            <input type="file" name="image" accept="image/*" class="form-control shadow-none" style="border-color: #9FACB9;" />
                            <?php if (!empty($field_errors['image'])): ?>
                                <div class="error-text"><?= htmlspecialchars($field_errors['image']) ?></div>
                            <?php endif; ?>
                        </div>

                       <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-check me-2"></i>ایجاد محصول
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

    <script>
        document.getElementById('addSizeBtn').addEventListener('click', function() {
            const container = document.getElementById('sizeContainer');

            const newInputGroup = document.createElement('div');
            newInputGroup.className = 'input-group mb-2';

            newInputGroup.innerHTML = `
                <input type="text" name="sizes[]" class="form-control shadow-none" style="border-color: #9FACB9;" placeholder="مثلاً: M یا 9-12 ماه" />
                <button type="button" class="btn btn-outline-secondary remove-size" style="border-color: #9FACB9;">-</button>
            `;

            container.appendChild(newInputGroup);
        });

        // حذف سایز
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-size')) {
                e.target.parentElement.remove();
            }
        });

        function addColorInput() {
            const wrapper = document.getElementById('colors-wrapper');
            
            const newInputGroup = document.createElement('div');
            newInputGroup.className = 'input-group mb-2';
            
            newInputGroup.innerHTML = `
                <input type="text" name="colors[]" class="form-control shadow-none" style="border-color: #9FACB9;" placeholder="مثلاً: آبی" />
                <button type="button" class="btn btn-outline-secondary remove-size" style="border-color: #9FACB9;">-</button>
            `;
            
            wrapper.appendChild(newInputGroup);
        }
    </script>
</body>
</html>