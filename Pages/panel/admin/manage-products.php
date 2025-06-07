<?php
include('../../../config/db.php');
require '../../../vendor/autoload.php';
use Morilog\Jalali\Jalalian;

// فقط ادمین‌ها دسترسی داشته باشند
if (!isset($_SESSION['user']['role']) || !in_array($_SESSION['user']['role'], ['super_admin', 'store_admin'])) {
    die("دسترسی غیرمجاز");
}

$user_role = $_SESSION['user']['role'] ?? null;
$store_id = $_SESSION['user']['store_id'] ?? null;


// پیام‌ها از سشن
$errors = $_SESSION['errors'] ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['success']);

// حذف محصول
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result_img = $stmt->get_result();
    if ($row = $result_img->fetch_assoc()) {
        if ($row['image'] && file_exists('../../../' . $row['image'])) {
            unlink('../../../' . $row['image']);
        }
    }
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "deleted";
    } else {
        $_SESSION['errors'] = ["خطا در حذف محصول."];
    }
    $stmt->close();

    header("Location: manage-products.php");
    exit;
}

// دریافت لیست محصولات
if ($user_role === 'store_admin' && $store_id) {
    $stmt = $conn->prepare("
        SELECT p.*, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.store_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->bind_param("i", $store_id);
} else {
    // سوپر ادمین
    $stmt = $conn->prepare("
        SELECT p.*, c.name AS category_name, s.name AS store_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN stores s ON p.store_id = s.id
        ORDER BY p.created_at DESC
    ");
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <title>مدیریت محصولات</title>
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

        .alert {
            margin-top: 20px;
        }

        img.product-image {
            max-height: 80px;
            object-fit: contain;
        }
    </style>
</head>

<body>
    <?php include('dashbord.php'); ?>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>مدیریت محصولات</h4>
            <a href="add-product.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> افزودن محصول جدید
            </a>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover text-center align-middle">
                    <thead>
                        <tr>
                            <th>تصویر</th>
                            <th>نام محصول</th>
                            <th>قیمت</th>
                            <th>موجودی</th>
                            <th>دسته‌بندی</th>
                            <?php if ($user_role === 'super_admin'): ?>
                                <th>فروشگاه</th>
                            <?php endif; ?>
                            <th>توضیحات</th>
                            <th>تاریخ ایجاد</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php if ($row['image'] && file_exists('../../../' . $row['image'])): ?>
                                        <img src="../../../<?= htmlspecialchars($row['image']) ?>" alt="تصویر محصول" class="product-image" />
                                    <?php else: ?>
                                        ندارد
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= number_format($row['price']) ?> تومان</td>
                                <td><?= intval($row['stock']) ?></td>
                                <td><?= htmlspecialchars($row['category_name'] ?? 'بدون دسته') ?></td>
                                <?php if ($user_role === 'super_admin'): ?>
                                    <td><?= htmlspecialchars($row['store_name'] ?? 'نامشخص') ?></td>
                                <?php endif; ?>
                                <td><?= mb_strimwidth(strip_tags($row['description']), 0, 10, '...') ?></td>
                                <td><?= Jalalian::fromDateTime($row['created_at'])->format('Y/m/d'); ?></td>
                                <td>
                                    <a href="edit-product.php?id=<?= $row['id'] ?>" class="btn btn-edit btn-sm me-2">
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
            <div class="alert alert-info">هیچ محصولی وجود ندارد.</div>
        <?php endif; ?>
    </div>

    <!-- Confirm Delete Script -->
    <script>
        function confirmDelete(productId) {
            Swal.fire({
                title: 'آیا از حذف این محصول مطمئن هستید؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'بله، حذف شود',
                cancelButtonText: 'انصراف',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                customClass: {
                    confirmButton: 'order-1',
                    cancelButton: 'order-2'
                },
                buttonsStyling: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `manage-products.php?delete_id=${productId}`;
                }
            });
        }
    </script>

    <!-- SweetAlert Success Messages -->
    <?php if ($success === 'deleted'): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'موفقیت',
                text: 'محصول با موفقیت حذف شد.',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false
            });
        </script>
    <?php elseif ($success): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'موفقیت',
                text: <?= json_encode($success, JSON_UNESCAPED_UNICODE) ?>,
                timer: 3000,
                timerProgressBar: true,
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