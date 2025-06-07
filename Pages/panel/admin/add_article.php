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
$success = '';

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
            $errors[] = "لطفاً یک فروشگاه انتخاب کنید.";
        }
    } elseif ($user_role === 'store_admin') {
        if (!$store_id) {
            die("شناسه فروشگاه تنظیم نشده است.");
        }
    }

    if ($title === '' || $content === '') {
        $errors[] = "عنوان و محتوای مقاله الزامی هستند.";
    }

    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../../uploads/articles/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image_name = uniqid() . '.' . $ext;
        $image_path = 'uploads/articles/' . $image_name;
        move_uploaded_file($_FILES['image']['tmp_name'], '../../../' . $image_path);
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO articles (title, content, author_id, store_id, image) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiis", $title, $content, $author_id, $store_id, $image_path);
        if ($stmt->execute()) {
            $_SESSION['success'] = "مقاله با موفقیت ثبت شد.";
            header("Location: manage_articles.php");
            exit;
        } else {
            $errors[] = "خطا در ثبت مقاله: " . $conn->error;
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
</head>
<body>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card p-4 shadow">
                <h4 class="text-danger mb-4 text-center">افزودن مقاله جدید</h4>

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
                        <label class="form-label">عنوان مقاله</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-heading"></i></span>
                            <input type="text" name="title" class="form-control shadow-none border" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">محتوای مقاله</label>
                        <textarea name="content" rows="6" class="form-control shadow-none border"><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                    </div>

                    <?php if ($user_role === 'super_admin'): ?>
                        <div class="mb-3">
                            <label class="form-label">فروشگاه</label>
                            <select name="store_id" class="form-select shadow-none border" required>
                                <option value="">انتخاب فروشگاه</option>
                                <?php while ($store = $stores_query->fetch_assoc()): ?>
                                    <option value="<?= $store['id'] ?>" <?= ($_POST['store_id'] ?? '') == $store['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($store['name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="store_id" value="<?= htmlspecialchars($store_id) ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">تصویر مقاله</label>
                        <input type="file" name="image" accept="image/*" class="form-control shadow-none border" />
                    </div>

                    <div class="d-flex justify-content-center gap-3">
                        <button type="submit" class="btn btn-danger" style="width: 45%;">ثبت مقاله</button>
                        <a href="manage_articles.php" class="btn btn-secondary" style="width: 45%;">بازگشت</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

</body>
</html>