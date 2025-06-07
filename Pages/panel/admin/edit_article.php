<?php
session_start();
include('../../../config/db.php');

// بررسی دسترسی ادمین
if (!isset($_SESSION['user']['role']) || !in_array($_SESSION['user']['role'], ['super_admin', 'store_admin'])) {
    die("دسترسی غیرمجاز");
}

$user_role = $_SESSION['user']['role'];
$store_id = $_SESSION['user']['store_id'] ?? null;
$errors = [];
$success = '';

// دریافت اطلاعات مقاله
$article_id = $_GET['id'] ?? null;
if (!$article_id) {
    die("شناسه مقاله مشخص نشده است");
}

// بررسی مالکیت مقاله برای ادمین فروشگاه
if ($user_role === 'store_admin') {
    $check_query = "SELECT id FROM articles WHERE id = ? AND store_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $article_id, $store_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        die("شما اجازه ویرایش این مقاله را ندارید");
    }
    $check_stmt->close();
}

// دریافت اطلاعات فعلی مقاله
$article_query = "SELECT * FROM articles WHERE id = ?";
$article_stmt = $conn->prepare($article_query);
$article_stmt->bind_param("i", $article_id);
$article_stmt->execute();
$article_result = $article_stmt->get_result();

if ($article_result->num_rows === 0) {
    die("مقاله مورد نظر یافت نشد");
}

$article = $article_result->fetch_assoc();
$article_stmt->close();

// دریافت لیست فروشگاه‌ها برای سوپر ادمین
if ($user_role === 'super_admin') {
    $stores_query = $conn->query("SELECT id, name FROM stores WHERE status = '1' ORDER BY name");
}

// پردازش فرم ویرایش
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    
    // تعیین store_id بر اساس نقش کاربر
    if ($user_role === 'super_admin') {
        $store_id = $_POST['store_id'] ?? null;
        if (!$store_id) {
            $errors[] = "لطفاً یک فروشگاه انتخاب کنید.";
        }
    }

    if ($title === '' || $content === '') {
        $errors[] = "عنوان و محتوای مقاله الزامی هستند.";
    }

    $image_path = $article['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // حذف تصویر قبلی اگر وجود دارد
        if ($image_path && file_exists('../../../' . $image_path)) {
            unlink('../../../' . $image_path);
        }
        
        // آپلود تصویر جدید
        $upload_dir = '../../../uploads/articles/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image_name = uniqid() . '.' . $ext;
        $image_path = 'uploads/articles/' . $image_name;
        move_uploaded_file($_FILES['image']['tmp_name'], '../../../' . $image_path);
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE articles SET title = ?, content = ?, store_id = ?, image = ? WHERE id = ?");
        $stmt->bind_param("ssisi", $title, $content, $store_id, $image_path, $article_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "مقاله با موفقیت ویرایش شد.";
            header("Location: manage_articles.php");
            exit;
        } else {
            $errors[] = "خطا در ویرایش مقاله: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>ویرایش مقاله</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />
</head>
<body>

<?php include('dashbord.php'); ?>

<div class="main-content">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card p-4 shadow">
                    <h4 class="text-danger mb-4 text-center">ویرایش مقاله</h4>

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
                                <input type="text" name="title" class="form-control shadow-none border" required 
                                       value="<?= htmlspecialchars($article['title']) ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">محتوای مقاله</label>
                            <textarea name="content" rows="6" class="form-control shadow-none border"><?= htmlspecialchars($article['content']) ?></textarea>
                        </div>

                        <?php if ($user_role === 'super_admin'): ?>
                            <div class="mb-3">
                                <label class="form-label">فروشگاه</label>
                                <select name="store_id" class="form-select shadow-none border" required>
                                    <option value="">انتخاب فروشگاه</option>
                                    <?php while ($store = $stores_query->fetch_assoc()): ?>
                                        <option value="<?= $store['id'] ?>" 
                                            <?= ($article['store_id'] == $store['id']) ? 'selected' : '' ?>>
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
                            <?php if ($article['image'] && file_exists('../../../' . $article['image'])): ?>
                                <div class="mt-2">
                                    <img src="../../../<?= $article['image'] ?>" class="img-thumbnail" style="max-height: 150px;">
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex justify-content-center gap-3">
                            <button type="submit" class="btn btn-danger" style="width: 45%;">ذخیره تغییرات</button>
                            <a href="manage_articles.php" class="btn btn-secondary" style="width: 45%;">بازگشت</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if (!empty($errors)): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'خطا',
            html: <?= json_encode(implode('<br>', array_map('htmlspecialchars', $errors)), JSON_UNESCAPED_UNICODE) ?>,
            confirmButtonText: 'باشه'
        });
    </script>
<?php endif; ?>

</body>
</html>