<?php
session_start();
include('../../../config/db.php');
include_once dirname(__DIR__) . '/../../Config/config.php';

if (!isset($_SESSION['user']['id'])) {
    header("Location: ../../auth/login.php");
    exit();
}

$user_id = $_SESSION['user']['id'];

$sql = "SELECT carts.id AS cart_id, carts.quantity, carts.size, carts.color,
               products.name AS product_name, products.price AS product_price, products.image AS product_image,
               (products.price * carts.quantity) AS total_price
        FROM carts
        INNER JOIN products ON carts.product_id = products.id
        WHERE carts.user_id = ?";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("خطا در آماده‌سازی کوئری: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$totalAmount = 0;
$items = [];
while ($row = $result->fetch_assoc()) {
    $totalAmount += $row['total_price'];
    $items[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>سبد خرید</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./cart.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php include('../../../Templates/Header.php') ?>

    <div class="container py-5" dir="rtl">
        <h4 class="mb-4">سبد خرید شما</h4>
        <div class="row">

            <div class="col-lg-8">
                <?php if (!empty($items)): ?>
                    <div class="table-responsive border rounded shadow-sm">
                        <table class="table align-middle text-center">
                            <thead class="table-light">
                                <tr>
                                    <th> </th>
                                    <th>محصول</th>
                                    <th>قیمت</th>
                                    <th>تعداد</th>
                                    <th>جمع کل</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td>
                                            <img src="../../../<?= htmlspecialchars($item['product_image']) ?>" width="120" height="120" class="rounded">
                                        </td>
                                        <td class="text-start">
                                            <div class="fw-bold"><?= htmlspecialchars($item['product_name']) ?></div>
                                            <?php if (!empty($item['color']) || !empty($item['size'])): ?>
                                                <div class="text-muted small mt-1">
                                                    <?php if (!empty($item['color'])): ?>
                                                        رنگ: <?= htmlspecialchars($item['color']) ?><br>
                                                    <?php endif; ?>
                                                    <?php if (!empty($item['size'])): ?>
                                                        سایز: <?= htmlspecialchars($item['size']) ?>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= number_format($item['product_price']) ?> تومان</td>
                                        <td><?= $item['quantity'] ?></td>
                                        <td class="text-success fw-bold"><?= number_format($item['total_price']) ?> تومان</td>
                                        <td>
                                            <form method="POST" action="delete-cart.php" class="delete-cart-form d-inline">
                                                <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                                <button type="button" class="btn btn-outline-danger btn-sm rounded-circle p-0 delete-btn" style="width: 30px; height: 30px;">×</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column align-items-center justify-content-center text-center border rounded p-5 bg-light shadow-sm">
                        <img src="https://cdn-icons-png.flaticon.com/512/2038/2038854.png" alt="سبد خرید خالی" width="120" class="mb-4">
                        <h5 class="mb-3 text-secondary">سبد خرید شما خالی است</h5>
                    </div>
                <?php endif; ?>

            </div>

            <div class="col-lg-4">
                <div class="border rounded p-4 shadow-sm bg-light">
                    <h5 class="mb-3">فاکتور خرید</h5>

                    <?php if (!empty($items)): ?>
                        <ul class="list-unstyled">
                            <li class="mb-2">مجموع قیمت کالا: <strong><?= number_format($totalAmount) ?> تومان</strong></li>
                            <li class="mb-2">هزینه ارسال: <strong>49000 تومان</strong></li>
                            <li class="mt-3 text-success fw-bold">پرداخت نهایی: <?= number_format($totalAmount + 49000) ?> تومان</li>
                        </ul>

                        <form action="checkout.php" method="POST">
                            <button type="submit" class="btn btn-success w-100">ادامه فرایند خرید</button>
                        </form>
                    <?php else: ?>
                        <p class="text-muted">هیچ سفارشی برای پرداخت وجود ندارد.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include('../../../Templates/Footer.php') ?>

    <script>
        // اسکریپت برای حذف محصول با تایید SweetAlert
        document.querySelectorAll('.delete-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const form = this.closest('form');
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
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(form.action, {
                                method: 'POST',
                                body: new FormData(form)
                            })
                            .then(response => response.text())
                            .then(data => {
                                if (data.trim() === 'success') {
                                    Swal.fire({
                                        title: 'موفقیت',
                                        text: 'محصول با موفقیت حذف شد.',
                                        icon: 'success',
                                        timer: 3000,
                                        timerProgressBar: true,
                                        showConfirmButton: false
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire('خطا', data, 'error');
                                }
                            })
                            .catch(error => {
                                Swal.fire('خطا', 'مشکلی پیش آمده است.', 'error');
                            });
                    }
                });
            });
        });

        // پیام موفقیت از سشن
        <?php if (!empty($_SESSION['success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'موفقیت',
                text: '<?= htmlspecialchars($_SESSION['success']) ?>',
                timer: 2000,
                timerProgressBar: true,
                showConfirmButton: false
            });
        <?php unset($_SESSION['success']);
        endif; ?>

        // پیام خطا از سشن
        <?php if (!empty($_SESSION['errors'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'خطا',
                html: '<?= implode("<br>", array_map("htmlspecialchars", $_SESSION["errors"])) ?>',
                confirmButtonText: 'باشه'
            });
        <?php unset($_SESSION['errors']);
        endif; ?>
    </script>

</body>

</html>

<?php $conn->close(); ?>