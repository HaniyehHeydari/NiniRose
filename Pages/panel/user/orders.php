<?php
session_start();
include('../../../config/db.php');

if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user']['id'];

// ۱. در کوئری، مقدار quantity را هم انتخاب می‌کنیم
$sql = "
    SELECT 
        orders.id         AS order_id,
        products.name     AS productname,
        products.image    AS productimage,
        orders.quantity, 
        orders.total_price,
        orders.created_at AS order_date,
        orders.status
    FROM orders 
    INNER JOIN products ON orders.product_id = products.id 
    WHERE orders.user_id = ?
    ORDER BY orders.created_at DESC
";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("خطا در آماده‌سازی کوئری: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>سفارش‌های من</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .main-content {
            width: 1100px;
            padding: 2rem;
            margin-top: 50px;
            margin-right: 150px;
            margin-bottom: 50px;
        }

        .table thead th {
            background-color: #EA6269;
            color: white;
        }

        .product-img {
            width: 100px;
            height: 120px;
            object-fit: cover;
            border-radius: 12px;
        }
    </style>
</head>

<body>
    <?php include('../../../Templates/Header.php'); ?>

    <div class="main-content">
        <h4 class="mb-4">سفارش‌های من</h4>

        <?php if ($result->num_rows > 0): ?>
            <div class="rounded-4 overflow-hidden shadow">
                <table class="table table-bordered table-hover text-center align-middle mb-0">
                    <thead>
                        <tr>
                            <th>تصویر</th>
                            <th>نام محصول</th>
                            <th>تعداد</th>
                            <th>تاریخ سفارش</th>
                            <th>قیمت کل</th>
                            <th>وضعیت</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <img src="../../../<?= htmlspecialchars($order['productimage']); ?>"
                                         alt="<?= htmlspecialchars($order['productname']); ?>"
                                         class="product-img">
                                </td>
                                <td><?= htmlspecialchars($order['productname']); ?></td>
                                <td><?= htmlspecialchars($order['quantity']); ?></td>
                                <td><?= htmlspecialchars($order['order_date']); ?></td>
                                <td class="text-danger fw-bold">
                                    <?= number_format($order['total_price']); ?> تومان
                                </td>
                                <td>
                                    <?php
                                        switch ($order['status']) {
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
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">شما تاکنون سفارشی ثبت نکرده‌اید.</div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include('../../../Templates/Footer.php'); ?>
</body>

</html>

<?php
$stmt->close();
$conn->close();
?>
