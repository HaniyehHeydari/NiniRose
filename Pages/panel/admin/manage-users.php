<?php
session_start();
include('../../../config/db.php');
require '../../../vendor/autoload.php';
use Morilog\Jalali\Jalalian;

// فقط سوپر ادمین دسترسی دارد
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'super_admin') {
    die("دسترسی غیرمجاز");
}

// دریافت همه کاربران به جز سوپر ادمین (حاوی ستون address)
$sql = "SELECT id, username, email, phone, role, created_at, address FROM users WHERE role != 'super_admin' ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>مدیریت کاربران</title>
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
       <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>مدیریت کاربران</h4>
            <a href="add-user.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> افزودن کاربر جدید
            </a>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover text-center align-middle">
                    <thead>
                        <tr>
                            <th>نام کاربری</th>
                            <th>ایمیل</th>
                            <th>شماره تماس</th>
                            <th>آدرس</th>
                            <th>نقش</th>
                            <th>تاریخ ثبت‌نام</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['username']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['phone']) ?></td>
                                <td><?= htmlspecialchars($row['address']) ?></td>
                                <td><?= htmlspecialchars($row['role']) ?></td>
                                <td><?= Jalalian::fromDateTime($row['created_at'])->format('Y/m/d'); ?></td>
                                <td>
                                    <form method="GET" action="edit-user.php" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="btn btn-primary btn-sm me-2">
                                            <i class="bi bi-pencil-square"></i> ویرایش
                                        </button>
                                    </form>

                                    <form method="POST" action="./delete-user.php" class="d-inline delete-form">
                                        <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                        <button type="button" class="btn btn-danger btn-sm delete-btn">
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
            <div class="alert alert-info">هیچ کاربری ثبت نشده است.</div>
        <?php endif; ?>

    </div>

    <script>
        // تایید حذف با SweetAlert
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                Swal.fire({
                    title: 'آیا از حذف این کاربر مطمئن هستید؟',
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
                        this.closest('form').submit();
                    }
                });
            });
        });

        // نمایش پیام موفقیت یا خطا
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
