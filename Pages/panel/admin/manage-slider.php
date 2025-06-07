<?php
include('../../../config/db.php');
require '../../../vendor/autoload.php';
use Morilog\Jalali\Jalalian;

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'super_admin') {
    die("دسترسی غیرمجاز");
}

// حذف اسلاید
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    $stmt = $conn->prepare("SELECT image FROM sliders WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $image_path = '../../' . $row['image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM sliders WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "اسلایدر با موفقیت حذف شد.";
    } else {
        $_SESSION['errors'] = ["خطا در حذف اسلایدر."];
    }
    $stmt->close();

    header("Location: manage-slider.php");
    exit;
}

// دریافت پیام‌ها از سشن
$errors = $_SESSION['errors'] ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['success']);

$result = $conn->query("SELECT * FROM sliders ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <title>مدیریت اسلایدر</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
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
    </style>
</head>

<body>
<?php include('dashbord.php'); ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>مدیریت اسلایدر</h4>
        <a href="add-slider.php" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> افزودن اسلایدر
        </a>
    </div>

    <?php if ($result && $result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover text-center align-middle">
                <thead>
                <tr>
                    <th>ردیف</th>
                    <th>تصویر</th>
                    <th>تاریخ ایجاد</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody>
                <?php $i = 1; while ($slide = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td>
                            <img src="../../../<?= htmlspecialchars($slide['image']) ?>" alt="Slide Image" style="max-height: 80px;" />
                        </td>
                        <td><?= Jalalian::fromDateTime($slide['created_at'])->format('Y/m/d'); ?></td>
                        <td>
                            <button class="btn btn-delete btn-sm" onclick="confirmDelete(<?= $slide['id'] ?>)">
                                <i class="bi bi-trash-fill"></i> حذف
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">هیچ اسلایدری وجود ندارد.</div>
    <?php endif; ?>
</div>

<script>
// SweetAlert برای پیام موفقیت
<?php if ($success): ?>
Swal.fire({
    icon: 'success',
    title: 'موفقیت',
    text: '<?= htmlspecialchars($success) ?>',
    timer: 3000,
    timerProgressBar: true,
    showConfirmButton: false
});
<?php endif; ?>


<?php if (!empty($errors)): ?>
Swal.fire({
    icon: 'error',
    title: 'خطا',
    html: '<?= implode("<br>", array_map('htmlspecialchars', $errors)) ?>',
    confirmButtonText: 'باشه'
});
<?php endif; ?>

// SweetAlert برای تایید حذف
function confirmDelete(id) {
    Swal.fire({
       title: 'آیا از حذف این فروشگاه مطمئن هستید؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'بله، حذف شود',
        cancelButtonText: 'انصراف',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#d33',
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '?delete_id=' + id;
        }
    });
}
</script>

</body>
</html>
