<?php
include('../../../config/db.php');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'super_admin') {
    die("دسترسی غیرمجاز");
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "لطفا تصویر اسلاید را انتخاب کنید.";
    } else {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $errors[] = "فقط فایل‌های تصویری با فرمت jpg، png یا gif مجاز است.";
        }
    }

    if (empty($errors)) {
        $upload_dir = '../../../Public/uploads/sliders/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $filename = uniqid() . '-' . basename($_FILES['image']['name']);
        $target_path = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            $image_path_db = 'Public/uploads/sliders/' . $filename;

            $stmt = $conn->prepare("INSERT INTO sliders (image) VALUES (?)");
            $stmt->bind_param("s", $image_path_db);
            if ($stmt->execute()) {
                $_SESSION['success'] = "اسلایدر جدید با موفقیت اضافه شد.";
            } else {
                $_SESSION['errors'] = ["خطا در ذخیره اطلاعات اسلایدر."];
                unlink($target_path);
            }
            $stmt->close();
        } else {
            $_SESSION['errors'] = ["خطا در آپلود تصویر."];
        }

        header("Location: manage-slider.php");
        exit;
    } else {
        $_SESSION['errors'] = $errors;
        header("Location: manage-slider.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <title>افزودن اسلاید جدید</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" />
</head>

<body dir="rtl">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6 mt-4">
                <div class="card p-5 shadow">
                    <h5 class="text-danger mb-4 text-center">افزودن اسلاید جدید</h5>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger text-center">
                            <?php foreach ($errors as $error): ?>
                                <div><?= htmlspecialchars($error) ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label for="image" class="form-label">تصویر اسلاید</label>
                            <div class="input-group">
                                <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-image"></i></span>
                                <input type="file" name="image" id="image" accept="image/*" class="form-control shadow-none" style="border-color: #9FACB9;" required />
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-check me-2"></i>ایجاد اسلایدر
                            </button>

                            <a href="manage-slider.php" class="btn btn-secondary">
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