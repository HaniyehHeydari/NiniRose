<?php
include('../../../config/db.php');

// بررسی نقش کاربر
$user_role = $_SESSION['user']['role'] ?? null;
$store_id = $_SESSION['user']['store_id'] ?? null;

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

    $errors = [];

    if ($name === '') $errors[] = "نام محصول را وارد کنید.";
    if (!is_numeric($price) || $price < 0) $errors[] = "قیمت معتبر نیست.";
    if (!is_numeric($stock) || $stock < 0) $errors[] = "موجودی معتبر نیست.";
    if (!$category_id) $errors[] = "دسته‌بندی انتخاب نشده است.";
    if (!$store_id) $errors[] = "فروشگاه انتخاب نشده است.";

    $image_path_db = null;
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
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, stock, image, category_id, store_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdisii", $name, $description, $price, $stock, $image_path_db, $category_id, $store_id);

        if ($stmt->execute()) {
            $product_id = $stmt->insert_id;

            $detail_stmt = $conn->prepare("INSERT INTO detail (product_id, size, color, description) VALUES (?, ?, ?, ?)");
            $detail_stmt->bind_param("isss", $product_id, $size_val, $color_val, $desc_val);

            $has_combination = false; // بررسی حداقل یک مقدار

            // اگر سایزها وجود دارند
            if (!empty($sizes)) {
                foreach ($sizes as $size) {
                    $size_val = trim($size);
                    if ($size_val === '') $size_val = null;

                    // اگر رنگ هم وجود دارد، ترکیب سایز و رنگ ذخیره شود
                    if (!empty($colors)) {
                        foreach ($colors as $color) {
                            $color_val = trim($color);
                            if ($color_val === '') $color_val = null;

                            // اگر توضیح سایز خالی بود، null شود
                            $desc_val = ($size_description !== '') ? $size_description : null;

                            // اگر حداقل یکی از فیلدها پر بود، ذخیره شود
                            if ($size_val || $color_val || $desc_val) {
                                $detail_stmt->execute();
                                $has_combination = true;
                            }
                        }
                    } else {
                        // فقط سایز بدون رنگ
                        $color_val = null;
                        $desc_val = ($size_description !== '') ? $size_description : null;

                        if ($size_val || $desc_val) {
                            $detail_stmt->execute();
                            $has_combination = true;
                        }
                    }
                }
            } elseif (!empty($colors)) {
                // فقط رنگ بدون سایز
                foreach ($colors as $color) {
                    $color_val = trim($color);
                    if ($color_val === '') $color_val = null;

                    $size_val = null;
                    $desc_val = ($size_description !== '') ? $size_description : null;

                    if ($color_val || $desc_val) {
                        $detail_stmt->execute();
                        $has_combination = true;
                    }
                }
            } elseif ($size_description !== '') {
                // فقط توضیح بدون سایز و رنگ
                $size_val = null;
                $color_val = null;
                $desc_val = $size_description;

                $detail_stmt->execute();
                $has_combination = true;
            }

            $detail_stmt->close();


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

// دریافت دسته‌بندی‌ها: فقط دسته‌های فروشگاه خودش برای store_admin
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
</head>

<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card p-4 shadow">
                    <h4 class="text-danger mb-4 text-center">افزودن محصول جدید</h4>

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
                        <div class="mb-3">
                            <label class="form-label">نام محصول</label>
                            <div class="input-group">
                                <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-box"></i></span>
                                <input type="text" name="name" class="form-control shadow-none" style="border-color: #9FACB9;" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" style="border-color: #9FACB9;">توضیحات</label>
                            <textarea name="description" class="form-control shadow-none" style="border-color: #9FACB9;" rows="3"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">قیمت (تومان)</label>
                                <div class="input-group">
                                    <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-money-bill-wave"></i></span>
                                    <input type="number" name="price" step="0.01" min="0" class="form-control shadow-none" style="border-color: #9FACB9;" required value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">موجودی</label>
                                <div class="input-group">
                                    <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-layer-group"></i></span>
                                    <input type="number" name="stock" min="0" class="form-control shadow-none" style="border-color: #9FACB9;" value="<?= htmlspecialchars($_POST['stock'] ?? '0') ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">دسته‌بندی</label>
                            <select name="category_id" class="form-select shadow-none" style="border-color: #9FACB9;">
                                <option value="">انتخاب دسته‌بندی</option>
                                <?php while ($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?= $cat['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <?php if ($user_role === 'super_admin'): ?>
                            <div class="mb-3">
                                <label class="form-label">فروشگاه</label>
                                <select name="store_id" class="form-select shadow-none" style="border-color: #9FACB9;">
                                    <option value="">انتخاب فروشگاه</option>
                                    <?php while ($store = $stores->fetch_assoc()): ?>
                                        <option value="<?= $store['id'] ?>" <?= (isset($_POST['store_id']) && $_POST['store_id'] == $store['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($store['name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="store_id" value="<?= $store_id ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">سایزهای محصول</label>
                            <div id="sizeContainer">
                                <div class="input-group mb-2">
                                    <input type="text" name="sizes[]" class="form-control shadow-none" style="border-color: #9FACB9;" placeholder="مثلاً: S یا 3-6 ماه" />
                                    <button type="button" class="btn btn-outline-secondary remove-size" style="border-color: #9FACB9;">-</button>
                                </div>
                            </div>
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
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="addColorInput()">افزودن رنگ جدید</button>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" style="border-color: #9FACB9;">توضیحات سایز بندی</label>
                            <textarea name="size_description" class="form-control shadow-none" style="border-color: #9FACB9;" rows="3"><?= htmlspecialchars($_POST['size_description'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" style="border-color: #9FACB9;">تصویر محصول</label>
                            <input type="file" name="image" accept="image/*" class="form-control shadow-none" style="border-color: #9FACB9;" />
                        </div>

                        <div class="d-flex justify-content-center gap-3">
                            <button type="submit" class="btn btn-danger" style="width: 45%;">افزودن محصول</button>
                            <a href="manage-products.php" class="btn btn-secondary" style="width: 45%;">بازگشت</a>
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
        <input type="text" name="sizes[]" class="form-control shadow-none border" placeholder="مثلاً: M یا 9-12 ماه" />
        <button type="button" class="btn btn-outline-secondary remove-size">-</button>
    `;

            container.appendChild(newInputGroup);
        });

        // حذف سایز
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-size')) {
                e.target.parentElement.remove();
            }
        });
    </script>
    <script>
        function addColorInput() {
            const wrapper = document.getElementById('colors-wrapper');
            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'colors[]';
            input.placeholder = 'مثلاً: آبی';
            input.className = 'form-control shadow-none border mb-2';
            wrapper.appendChild(input);
        }
    </script>
</body>

</html>