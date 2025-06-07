<?php
session_start();
include('../../../config/db.php');
require '../../../vendor/autoload.php';
use Morilog\Jalali\Jalalian;

// فقط سوپر ادمین اجازه داره
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'super_admin') {
    die("دسترسی غیرمجاز");
}

// دریافت فروشگاه‌ها به همراه ایمیل مدیر
$sql = "SELECT stores.*, users.email FROM stores 
        JOIN users ON stores.admin_id = users.id 
        ORDER BY stores.created_at DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>مدیریت فروشگاه‌ها</title>
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

        .btn-approve {
            background-color: #28a745 !important;
            color: white !important;
        }

        .btn-reject {
            background-color: #dc3545 !important;
            color: white !important;
            margin-right: 8px;
        }

        .btn-edit {
            background-color: #007bff !important;
            color: white;
        }
    </style>
</head>

<body>
    <?php include('dashbord.php'); ?>

    <div class="main-content">
        <h4 class="mb-4">مدیریت فروشگاه‌ها</h4>

        <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover text-center align-middle">
                    <thead>
                        <tr>
                            <th>نام مغازه</th>
                            <th>آدرس</th>
                            <th>شماره تماس</th>
                            <th>ایمیل درخواست‌دهنده</th>
                            <th>تاریخ ثبت‌نام</th>
                            <th>تاریخ تأیید</th>
                            <th>وضعیت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['address']) ?></td>
                                <td><?= htmlspecialchars($row['phone']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= Jalalian::fromDateTime($row['created_at'])->format('Y/m/d'); ?></td>
                                <td>
                                    <?php if (!empty($row['approved_at'])): ?>
                                      <?= Jalalian::fromDateTime($row['approved_at'])->format('Y/m/d') ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= $row['status'] == 1 ? '<span class="text-success">تأیید شده</span>' : '<span class="text-danger">در انتظار تایید</span>' ?>
                                </td>
                                <td>
                                    <?php if ($row['status'] == 0): ?>
                                        <button class="btn btn-success btn-sm px-3" onclick="confirmApprove(<?= $row['id'] ?>, <?= $row['admin_id'] ?>)">
                                            <i class="bi bi-check-circle-fill"></i> تایید
                                        </button>
                                        <button class="btn btn-reject btn-sm" onclick="confirmDelete(<?= $row['id'] ?>)">
                                            <i class="bi bi-x-circle-fill"></i> رد
                                        </button>
                                    <?php else: ?>
                                        <form method="GET" action="edit-store.php" class="d-inline">
                                            <input type="hidden" name="store_id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="btn btn-edit btn-sm text-white">
                                                <i class="bi bi-pencil-square"></i> ویرایش
                                            </button>
                                        </form>
                                        <button class="btn btn-reject btn-sm" onclick="confirmDelete(<?= $row['id'] ?>)">
                                            <i class="bi bi-trash-fill"></i> حذف
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">هیچ فروشگاهی ثبت نشده است.</div>
        <?php endif; ?>
    </div>

    <!-- فرم مخفی برای حذف -->
    <form id="deleteForm" method="POST" action="delete-store.php" style="display:none;">
        <input type="hidden" name="store_id" id="deleteStoreId" value="">
    </form>

    <!-- فرم مخفی برای تایید -->
    <form id="approveForm" method="POST" action="approve-store.php" style="display:none;">
        <input type="hidden" name="store_id" id="approveStoreId">
        <input type="hidden" name="admin_id" id="approveAdminId">
    </form>

    <script>
        function confirmDelete(storeId) {
            Swal.fire({
                title: 'آیا از حذف این فروشگاه مطمئن هستید؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'بله، حذف شود',
                cancelButtonText: 'انصراف',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                customClass: {
                    confirmButton: 'order-1', // دکمه تایید در راست
                    cancelButton: 'order-2' // دکمه انصراف در چپ
                },
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('deleteStoreId').value = storeId;
                    document.getElementById('deleteForm').submit();
                }
            });
        }

        function confirmApprove(storeId, adminId) {
            Swal.fire({
                title: 'آیا از تایید این فروشگاه مطمئن هستید؟',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'بله، تایید شود',
                cancelButtonText: 'انصراف',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                customClass: {
                    confirmButton: 'order-1', // دکمه تایید در راست
                    cancelButton: 'order-2' // دکمه انصراف در چپ
                },
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('approveStoreId').value = storeId;
                    document.getElementById('approveAdminId').value = adminId;
                    document.getElementById('approveForm').submit();
                }
            });
        }

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
                text: <?= json_encode($_SESSION['error_message']) ?>,
                confirmButtonText: 'باشه'
            });
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
    </script>
</body>

</html>