<?php
session_start();
include('../../../config/db.php');

// بررسی لاگین بودن کاربر
if (!isset($_SESSION['user'])) {
    die("لطفاً ابتدا وارد شوید.");
}

$user = $_SESSION['user'];
$user_role = $user['role'];
$user_store_id = $user['store_id'] ?? null;

// کوئری گرفتن سفارشات
if ($user_role === 'super_admin') {
    // همه سفارشات
    $sql = "SELECT orders.*, products.name AS productname, products.image, stores.name AS storename 
            FROM orders
            INNER JOIN products ON orders.product_id = products.id
            LEFT JOIN stores ON products.store_id = stores.id
            ORDER BY orders.created_at DESC";
    $stmt = $conn->prepare($sql);
} elseif ($user_role === 'store_admin' && $user_store_id) {
    // فقط سفارش‌های فروشگاه خودش
    $sql = "SELECT orders.*, products.name AS productname, products.image, stores.name AS storename 
            FROM orders
            INNER JOIN products ON orders.product_id = products.id
            LEFT JOIN stores ON products.store_id = stores.id
            WHERE products.store_id = ?
            ORDER BY orders.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_store_id);
} else {
    die("دسترسی غیرمجاز.");
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>مدیریت سفارشات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
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

        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 10px;
        }

        .btn-edit {
            background-color: #007bff !important;
            color: white !important;
        }

        .btn-edit:hover {
            background-color: #0056b3 !important;
        }
    </style>
</head>

<body>

    <?php include('Dashbord.php'); ?>

    <div class="main-content">
        <h4 class="mb-4">مدیریت سفارشات</h4>

        <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover text-center align-middle">
                    <thead>
                        <tr>
                            <th>تصویر</th>
                            <th>محصول</th>
                            <th>فروشگاه</th>
                            <th>تعداد</th>
                            <th>قیمت کل</th>
                            <th>تاریخ</th>
                            <th>وضعیت</th>
                            <?php if ($user_role === 'super_admin'): ?>
                                <th>عملیات</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><img src="../../../<?= htmlspecialchars($row['image']) ?>" class="product-img"></td>
                                <td><?= htmlspecialchars($row['productname']) ?></td>
                                <td><?= htmlspecialchars($row['storename']) ?></td>
                                <td><?= $row['quantity'] ?></td>
                                <td class="text-danger fw-bold"><?= number_format($row['total_price']) ?> تومان</td>
                                <td><?= $row['created_at'] ?></td>
                                <td>
                                    <?php
                                    switch ($row['status']) {
                                        case 0:
                                            echo '<span class="badge bg-warning text-dark">در حال پردازش</span>';
                                            break;
                                        case 1:
                                            echo '<span class="badge bg-success text-dark">در حال ارسال</span>';
                                            break;
                                        case 2:
                                            echo '<span class="badge bg-danger">عدم ارسال</span>';
                                            break;
                                        default:
                                            echo '<span class="badge bg-secondary">نامشخص</span>';
                                    }
                                    ?>
                                </td>
                                <?php if ($user_role === 'super_admin'): ?>
                                    <td>
                                        <a href="edit-order.php?id=<?= $row['id'] ?>" class="btn btn-edit btn-sm me-2">
                                            <i class="bi bi-pencil-square"></i> ویرایش
                                        </a>
                                        <form action="delete-order.php" method="POST" class="d-inline delete-form">
                                            <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                            <button type="button" class="btn btn-danger btn-sm delete-btn">
                                                <i class="bi bi-trash-fill"></i> حذف
                                            </button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">هیچ سفارشی یافت نشد.</div>
        <?php endif; ?>
    </div>

    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                Swal.fire({
                    title: 'حذف سفارش؟',
                    text: "آیا از حذف این سفارش مطمئن هستید؟",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'بله، حذف شود',
                    cancelButtonText: 'انصراف',
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.closest('form').submit();
                    }
                });
            });
        });

        <?php if (isset($_SESSION['success_message'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'موفقیت',
                text: '<?= $_SESSION['success_message'] ?>',
                timer: 2500,
                showConfirmButton: false
            });
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
    </script>
</body>

</html>

<?php $stmt->close();
$conn->close(); ?>