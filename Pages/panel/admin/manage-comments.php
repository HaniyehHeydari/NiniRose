<?php
session_start();
include('../../../config/db.php');
require '../../../vendor/autoload.php';
use Morilog\Jalali\Jalalian;

// بررسی نقش کاربر
if (!isset($_SESSION['user'])) {
    die("دسترسی غیرمجاز");
}

$user_id = $_SESSION['user']['id'];
$user_role = $_SESSION['user']['role'];
$store_id = $_SESSION['user']['store_id'] ?? null;

// ساخت کوئری بر اساس نقش
if ($user_role === 'super_admin') {
    $stmt = $conn->prepare("SELECT c.*, p.name AS product_name, u.username AS username
                            FROM comments c
                            JOIN products p ON c.product_id = p.id
                            JOIN users u ON c.user_id = u.id
                            WHERE c.parent_id IS NULL
                            ORDER BY c.created_at DESC");
} else {
    $stmt = $conn->prepare("SELECT c.*, p.name AS product_name, u.username AS username
                            FROM comments c
                            JOIN products p ON c.product_id = p.id
                            JOIN users u ON c.user_id = u.id
                            WHERE p.store_id = ? AND c.parent_id IS NULL
                            ORDER BY c.created_at DESC");
    $stmt->bind_param("i", $store_id);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>مدیریت نظرات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
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
    </style>
</head>

<body>
    <?php include('dashbord.php'); ?>

    <div class="main-content">
        <h4 class="mb-4">مدیریت نظرات</h4>

        <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover text-center align-middle">
                    <thead>
                        <tr>
                            <th>محصول</th>
                            <th>کاربر</th>
                            <th>متن نظر</th>
                            <th>تاریخ</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1;
                        while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['product_name']) ?></td>
                                <td><?= htmlspecialchars($row['username']) ?></td>
                                <td><?= htmlspecialchars($row['content']) ?></td>
                                <td><?= Jalalian::fromDateTime($row['created_at'])->format('Y/m/d'); ?></td>
                                <td>
                                    <a href="comment-details.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning text-white">
                                        <i class="bi bi-chat-dots"></i> جزئیات
                                    </a>
                                    <form method="POST" action="delete-comment.php" class="d-inline delete-form">
                                        <input type="hidden" name="comment_id" value="<?= $row['id'] ?>">
                                        <button type="button" class="btn btn-sm btn-danger delete-btn">
                                            <i class="bi bi-trash-fill"></i> حذف
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">هیچ نظری ثبت نشده است.</div>
        <?php endif; ?>
    </div>

    <script>
        // تایید حذف با SweetAlert
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                Swal.fire({
                    title: 'آیا از حذف این نظر مطمئن هستید؟',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'بله، حذف شود',
                    cancelButtonText: 'انصراف',
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#d33'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.closest('form').submit();
                    }
                });
            });
        });

        // پیام موفقیت یا خطا
        <?php if (isset($_SESSION['success_message'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'موفقیت',
                text: '<?= $_SESSION['success_message']; ?>',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false
            });
            <?php unset($_SESSION['success_message']); ?>
        <?php elseif (isset($_SESSION['error_message'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'خطا',
                text: '<?= $_SESSION['error_message']; ?>',
                confirmButtonText: 'باشه'
            });
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
    </script>
</body>

</html>