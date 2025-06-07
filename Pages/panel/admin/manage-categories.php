<?php
include('../../../config/db.php');
require '../../../vendor/autoload.php';
use Morilog\Jalali\Jalalian;

if (!isset($_SESSION['user']['role']) || !in_array($_SESSION['user']['role'], ['super_admin', 'store_admin'])) {
    die("دسترسی غیرمجاز");
}

// گرفتن دسته‌بندی‌ها بر اساس نقش کاربر
if ($_SESSION['user']['role'] === 'store_admin') {
    $store_id = $_SESSION['store_id'] ?? ($_SESSION['user']['store_id'] ?? null);
    if (!$store_id) {
        die("شناسه فروشگاه برای ادمین فروشگاه تنظیم نشده است.");
    }
    $store_id = (int) $store_id;
    $sql = "SELECT * FROM categories WHERE store_id = $store_id ORDER BY created_at DESC";
} elseif ($_SESSION['user']['role'] === 'super_admin') {
    $sql = "
        SELECT c.*, s.name AS store_name
        FROM categories c
        LEFT JOIN stores s ON c.store_id = s.id
        ORDER BY c.created_at DESC
    ";
} else {
    die("نقش کاربر نامعتبر است.");
}

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>مدیریت دسته‌بندی‌ها</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
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

        .alert {
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <?php include('dashbord.php'); ?>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>مدیریت دسته‌بندی‌ها</h4>
            <a href="add-category.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> افزودن دسته‌بندی
            </a>
        </div>

        <!-- جدول نمایش دسته‌بندی‌ها -->
        <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover text-center align-middle">
                    <thead>
                        <tr>
                            <th>نام دسته‌بندی</th>
                            <?php if ($_SESSION['user']['role'] === 'super_admin'): ?>
                                <th>فروشگاه</th>
                            <?php endif; ?>
                            <th>تاریخ ایجاد</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <?php if ($_SESSION['user']['role'] === 'super_admin'): ?>
                                    <td><?= htmlspecialchars($row['store_name'] ?? 'نامشخص') ?></td>
                                <?php endif; ?>
                                <td><?= Jalalian::fromDateTime($row['created_at'])->format('Y/m/d'); ?></td>
                                <td>
                                    <a href="edit-category.php?category_id=<?= $row['id'] ?>" class="btn btn-edit btn-sm me-2">
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
            <div class="alert alert-info">هیچ دسته‌بندی‌ای ثبت نشده است.</div>
        <?php endif; ?>
    </div>

    <!-- Confirm Delete Script -->
    <script>
        function confirmDelete(categoryId) {
            Swal.fire({
                title: 'آیا از حذف این دسته بندی مطمئن هستید؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'بله، حذف شود',
                cancelButtonText: 'انصراف',
                confirmButtonColor: '#28a745', // سبز
                cancelButtonColor: '#d33', // قرمز/خنثی
                customClass: {
                    confirmButton: 'order-1', // دکمه تایید در راست
                    cancelButton: 'order-2' // دکمه انصراف در چپ
                },
                buttonsStyling: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `delete-category.php?category_id=${categoryId}`;
                }
            });
        }
    </script>

    <!-- پیام موفقیت با SweetAlert -->
    <?php if (isset($_GET['message'])): ?>
    <script>
        <?php if ($_GET['message'] === 'created'): ?>
            Swal.fire({
                icon: 'success',
                title: 'موفقیت',
                text: 'دسته‌بندی جدید با موفقیت ایجاد شد.',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false
            });
        <?php elseif ($_GET['message'] === 'updated'): ?>
            Swal.fire({
                icon: 'success',
                title: 'موفقیت',
                text: 'اطلاعات دسته‌بندی با موفقیت ویرایش شد.',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false
            });
        <?php elseif ($_GET['message'] === 'deleted'): ?>
            Swal.fire({
                icon: 'success',
                title: 'موفقیت',
                text: 'دسته‌بندی با موفقیت حذف شد.',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false
            });
        <?php endif; ?>

            // حذف پارامتر پیام از URL پس از نمایش
            window.addEventListener('load', () => {
                const url = new URL(window.location);
                url.searchParams.delete('message');
                window.history.replaceState({}, document.title, url);
            });
        </script>
    <?php endif; ?>

</body>

</html>