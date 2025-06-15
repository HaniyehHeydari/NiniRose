<?php
session_start();
include('../../../config/db.php');

// بررسی نقش کاربر
if (!isset($_SESSION['user']['role']) || !in_array($_SESSION['user']['role'], ['super_admin', 'store_admin'])) {
    die("دسترسی غیرمجاز");
}

$user_role = $_SESSION['user']['role'];
$store_id = $_SESSION['user']['store_id'] ?? null;
$errors = [];
$alert = null;

// دریافت لیست فروشگاه‌ها برای سوپر ادمین
if ($user_role === 'super_admin') {
    $stores_query = $conn->query("SELECT id, name FROM stores WHERE status = '1' ORDER BY name");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $author_id = $_SESSION['user']['id'];

    // تعیین store_id بر اساس نقش کاربر
    if ($user_role === 'super_admin') {
        $store_id = $_POST['store_id'] ?? null;
        if (!$store_id) {
            $errors['store_id'] = "لطفاً یک فروشگاه انتخاب کنید.";
        }
    } elseif ($user_role === 'store_admin') {
        if (!$store_id) {
            die("شناسه فروشگاه تنظیم نشده است.");
        }
    }

    // اعتبارسنجی عنوان
    if (empty($title)) {
        $errors['title'] = "عنوان مقاله الزامی است.";
    } elseif (!preg_match('/^(?![0-9])[آ-یa-zA-Z0-9\s‌]{3,}$/u', $title)) {
        $errors['title'] = "لطفا یک عنوان معتبر وارد کنید";
    }

    // اعتبارسنجی محتوا
    if (empty($content)) {
        $errors['content'] = "محتوای مقاله الزامی است.";
    } elseif (mb_strlen($content) > 5000) {
        $errors['content'] = "محتوای مقاله نمی‌تواند بیش از 5000 کاراکتر باشد.";
    }

    // اعتبارسنجی تصویر (الزامی)
    $image_path = null;
    if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors['image'] = "انتخاب تصویر برای مقاله الزامی است.";
    } elseif ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['image']['type'];

        if (!in_array($file_type, $allowed_types)) {
            $errors['image'] = "فرمت تصویر باید JPG, PNG یا GIF باشد.";
        } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) { // 2MB
            $errors['image'] = "حجم تصویر نباید بیشتر از 2 مگابایت باشد.";
        } else {
            $upload_dir = '../../../Public/uploads/articles/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = uniqid() . '.' . $ext;
            $image_path = '/Public/uploads/articles/' . $image_name;
            move_uploaded_file($_FILES['image']['tmp_name'], '../../../' . $image_path);
        }
    } elseif ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $errors['image'] = "خطا در آپلود تصویر. لطفاً مجدداً تلاش کنید.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO articles (title, content, author_id, store_id, image) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiis", $title, $content, $author_id, $store_id, $image_path);
        if ($stmt->execute()) {
            $alert = ['type' => 'success', 'message' => "مقاله با موفقیت ثبت شد."];
            // خالی کردن فیلدها پس از ثبت موفق
            $_POST = array();
            $_FILES = array();
        } else {
            $alert = ['type' => 'error', 'message' => "خطا در ثبت مقاله: " . $conn->error];
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>افزودن مقاله</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" />
    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .error-message {
            color: red;
            font-size: 12px;
            margin-top: 5px;
        }

        .required-field::after {
            content: " *";
            color: red;
        }
    </style>
</head>

<body>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card p-4 shadow">
                    <h4 class="text-danger mb-4 text-center">افزودن مقاله جدید</h4>

                    <form method="POST" enctype="multipart/form-data" novalidate>
                        <div class="mb-3">
                            <label class="form-label">عنوان مقاله</label>
                            <div class="input-group">
                                <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-heading"></i></span>
                                <input type="text" name="title" class="form-control shadow-none" style="border-color: #9FACB9;" required
                                    value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                            </div>
                            <?php if (isset($errors['title'])): ?>
                                <span class="error-message"><?= $errors['title'] ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">محتوای مقاله</label>
                            <textarea name="content" rows="6" class="form-control shadow-none" style="border-color: #9FACB9;"><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                            <?php if (isset($errors['content'])): ?>
                                <span class="error-message"><?= $errors['content'] ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if ($user_role === 'super_admin'): ?>
                            <div class="mb-3">
                                <label class="form-label">فروشگاه</label>
                                <select name="store_id" class="form-select shadow-none" style="border-color: #9FACB9;" required>
                                    <option value="">انتخاب فروشگاه</option>
                                    <?php while ($store = $stores_query->fetch_assoc()): ?>
                                        <option value="<?= $store['id'] ?>" <?= ($_POST['store_id'] ?? '') == $store['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($store['name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <?php if (isset($errors['store_id'])): ?>
                                    <span class="error-message"><?= $errors['store_id'] ?></span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="store_id" value="<?= htmlspecialchars($store_id) ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">تصویر مقاله</label>
                            <input type="file" name="image" accept="image/*" class="form-control shadow-none" style="border-color: #9FACB9;" required />
                            <?php if (isset($errors['image'])): ?>
                                <span class="error-message"><?= $errors['image'] ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger">
                           <i class="fas fa-file-alt me-2"></i>ایجاد مقاله
                            </button>
                            <a href="manage_articles.php" class="btn btn-secondary">
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
                    window.location.href = 'manage_articles.php';
                <?php endif; ?>
            });
        </script>
    <?php endif; ?>

</body>

</html>