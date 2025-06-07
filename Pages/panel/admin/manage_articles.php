<?php
session_start();
include('../../../config/db.php');

// فقط ادمین‌ها دسترسی داشته باشند
if (!isset($_SESSION['user']['role']) || !in_array($_SESSION['user']['role'], ['super_admin', 'store_admin'])) {
    die("دسترسی غیرمجاز");
}

$user_role = $_SESSION['user']['role'];
$store_id = $_SESSION['user']['store_id'] ?? null;

$errors = $_SESSION['errors'] ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['success']);

try {
    // دریافت لیست مقالات با توجه به نقش کاربر
    if ($user_role === 'super_admin') {
        $query = "SELECT articles.*, stores.name AS store_name 
                  FROM articles 
                  LEFT JOIN stores ON articles.store_id = stores.id 
                  ORDER BY created_at DESC";
        $stmt = $conn->prepare($query);
    } else {
        $query = "SELECT articles.*, stores.name AS store_name 
                  FROM articles 
                  LEFT JOIN stores ON articles.store_id = stores.id 
                  WHERE articles.store_id = ? 
                  ORDER BY created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $store_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        throw new Exception("خطا در دریافت اطلاعات از پایگاه داده: " . $conn->error);
    }
} catch (Exception $e) {
    $errors[] = $e->getMessage();
    error_log($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>مدیریت مقالات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .main-content {
            margin-right: 250px;
            padding: 2rem;
            margin-top: 50px;
        }

        table.table {
            box-shadow: -2px 0 5px rgba(0, 0, 0, 0.05);
            border-radius: 15px;
            overflow: hidden;
        }

        table.table thead th {
            background-color: #EA6269;
            color: white;
        }

        table.table tbody tr {
            background-color: #ffffff;
        }

        .btn-edit {
            background-color: #007bff !important;
            color: white !important;
        }

        .btn-edit:hover {
            background-color: #0056b3 !important;
        }

        .btn-delete {
            background-color: #dc3545 !important;
            color: white !important;
        }

        .btn-delete:hover {
            background-color: #c82333 !important;
        }

        .btn-add {
            background-color: #28a745;
            color: white;
        }

        .btn-add:hover {
            background-color: #218838;
        }

        img.article-image {
            max-height: 80px;
            object-fit: cover;
        }

        .alert {
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <?php include('dashbord.php'); ?>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>مدیریت مقالات</h4>
            <a href="add_article.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> افزودن مقاله جدید
            </a>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover text-center align-middle">
                    <thead>
                        <tr>
                             <th>تصویر</th>
                            <th>عنوان</th>
                            <?php if ($user_role === 'super_admin'): ?>
                                <th>فروشگاه</th>
                            <?php endif; ?>
                            <th>تاریخ ایجاد</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php if ($row['image'] && file_exists('../../../' . $row['image'])): ?>
                                        <img src="../../../<?= $row['image'] ?>" class="article-image" alt="تصویر مقاله" />
                                    <?php else: ?>
                                        ندارد
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['title']) ?></td>
                                <?php if ($user_role === 'super_admin'): ?>
                                    <td><?= htmlspecialchars($row['store_name'] ?? 'نامشخص') ?></td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars($row['created_at']) ?></td>
                                <td>
                                    <a href="edit_article.php?id=<?= $row['id'] ?>" class="btn btn-edit btn-sm me-2">
                                        <i class="bi bi-pencil-square"></i> ویرایش
                                    </a>
                                    <button class="btn btn-delete btn-sm" onclick="confirmDelete(<?= $row['id'] ?>)">
                                        <i class="bi bi-trash-fill"></i> حذف
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">هیچ مقاله‌ای وجود ندارد.</div>
        <?php endif; ?>
    </div>

    <script>
        function confirmDelete(articleId) {
            Swal.fire({
                title: 'آیا از حذف این مقاله مطمئن هستید؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'بله',
                cancelButtonText: 'خیر',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `delete_article.php?id=${articleId}`;
                }
            });
        }
    </script>

    <?php if ($success): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'موفقیت',
                text: <?= json_encode($success, JSON_UNESCAPED_UNICODE) ?>,
                timer: 3000,
                showConfirmButton: false
            });
        </script>
    <?php endif; ?>

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