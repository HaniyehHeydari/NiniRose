<?php
session_start();
include('../../../config/db.php');

// بررسی مجوز دسترسی
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'super_admin'])) {
    die("دسترسی غیرمجاز");
}

// بررسی وجود شناسه سفارش
if (!isset($_GET['id'])) {
    die("شناسه سفارش نامعتبر است.");
}

$order_id = intval($_GET['id']);

// دریافت اطلاعات سفارش
$sql = "SELECT orders.*, products.name AS productname FROM orders
        INNER JOIN products ON orders.product_id = products.id
        WHERE orders.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    die("سفارشی با این شناسه یافت نشد.");
}

// بروزرسانی سفارش
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = intval($_POST['status']);
    $new_price = floatval($_POST['total_price']);

    $update_sql = "UPDATE orders SET status = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $new_status, $order_id);


    if ($update_stmt->execute()) {
        $_SESSION['success_message'] = "سفارش با موفقیت ویرایش شد.";
        header("Location: manage-orders.php");
        exit();
    } else {
        $_SESSION['error_message'] = "خطا در بروزرسانی سفارش.";
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>ویرایش سفارش</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-light">

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6 mt-4">
                <div class="card p-5 shadow">
                    <h5 class="text-danger mb-4 text-center">ویرایش سفارش</h5>

                    <div class="card-body">
                        <form method="post">
                            <div class="mb-3">
                                <label class="form-label">وضعیت سفارش</label>
                                <select name="status" class="form-select shadow-none" style="border-color: #9FACB9;" required>
                                    <option value="0" <?= $order['status'] == 0 ? 'selected' : '' ?>>در حال پردازش</option>
                                    <option value="1" <?= $order['status'] == 1 ? 'selected' : '' ?>>در حال ارسال</option>
                                    <option value="2" <?= $order['status'] == 2 ? 'selected' : '' ?>>عدم ارسال</option>
                                </select>
                            </div>

                            <div class="d-flex justify-content-center gap-4">
                                <button type="submit" class="btn btn-danger" style="width: 45%;">ذخیره تغییرات</button>
                                <a href="manage-orders.php" class="btn btn-secondary" style="width: 45%;">بازگشت</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php if (isset($_SESSION['error_message'])): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'خطا',
                text: '<?= $_SESSION['error_message'] ?>',
                confirmButtonText: 'باشه'
            });
        </script>
    <?php unset($_SESSION['error_message']);
    endif; ?>

</body>

</html>