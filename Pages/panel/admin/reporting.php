<?php
session_start();
include('../../../config/db.php');
require '../../../vendor/autoload.php';
use GuzzleHttp\Client;
use Morilog\Jalali\Jalalian;
use Morilog\Jalali\CalendarUtils;

// بررسی نقش کاربر
if (!isset($_SESSION['user']['role']) || !in_array($_SESSION['user']['role'], ['super_admin', 'store_admin'])) {
    die("دسترسی غیرمجاز");
}
function faToEn($string) {
    $persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    $english = ['0','1','2','3','4','5','6','7','8','9'];
    return str_replace($persian, $english, $string);
}


$report_result = null;
$new_products_result = null;
$grand_total = 0;

// دریافت پارامترهای جستجو
$product_name = $_GET['product_name'] ?? null;
$from_date_jalali = $_GET['from_date'] ?? null;
$to_date_jalali = $_GET['to_date'] ?? null;

// تبدیل تاریخ شمسی به میلادی
$from_date_gregorian = null;
$to_date_gregorian = null;

if (!empty($from_date_jalali)) {
    try {
        list($year, $month, $day) = explode('/', faToEn($from_date_jalali));
        $gregorian = CalendarUtils::toGregorian((int)$year, (int)$month, (int)$day);
        $from_date_gregorian = implode('-', $gregorian) . ' 00:00:00';
    } catch (Exception $e) {
        die("فرمت تاریخ شروع نامعتبر است");
    }
}

if (!empty($to_date_jalali)) {
    try {
        list($year, $month, $day) = explode('/', faToEn($to_date_jalali));
        $gregorian = CalendarUtils::toGregorian((int)$year, (int)$month, (int)$day);
        $to_date_gregorian = implode('-', $gregorian) . ' 23:59:59';
    } catch (Exception $e) {
        die("فرمت تاریخ پایان نامعتبر است");
    }
}


// اجرای کوئری‌ها اگر پارامترهای جستجو وجود داشته باشد
if (!empty($from_date_gregorian) || !empty($to_date_gregorian) || !empty($product_name)) {
    // کوئری گزارش فروش
    $where = "1=1";
    $params = "";
    $bindValues = [];

    if (!empty($from_date_gregorian) && !empty($to_date_gregorian)) {
        $where .= " AND orders.created_at BETWEEN ? AND ?";
        $params .= "ss";
        $bindValues[] = $from_date_gregorian;
        $bindValues[] = $to_date_gregorian;
    }

    if (!empty($product_name)) {
        $where .= " AND products.name LIKE ?";
        $params .= "s";
        $bindValues[] = "%$product_name%";
    }

    if ($_SESSION['user']['role'] === 'store_admin') {
        $where .= " AND products.store_id = ?";
        $params .= "i";
        $bindValues[] = $_SESSION['user']['store_id'];
    }

    $sql = "
        SELECT 
            products.name AS product_name,
            stores.name AS store_name,
            SUM(orders.quantity) AS total_quantity,
            SUM(orders.total_price) AS total_sales,
            DATE(orders.created_at) AS order_date
        FROM orders
        JOIN products ON products.id = orders.product_id
        LEFT JOIN stores ON products.store_id = stores.id
        WHERE $where
        GROUP BY products.id, DATE(orders.created_at)
        ORDER BY order_date DESC
    ";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($params, ...$bindValues);
    }
    $stmt->execute();
    $report_result = $stmt->get_result();

    // کوئری محصولات جدید
    $where_new = "1=1";
    $params_new = "";
    $bindValues_new = [];

    if (!empty($from_date_gregorian) && !empty($to_date_gregorian)) {
        $where_new .= " AND products.created_at BETWEEN ? AND ?";
        $params_new .= "ss";
        $bindValues_new[] = $from_date_gregorian;
        $bindValues_new[] = $to_date_gregorian;
    }

    if (!empty($product_name)) {
        $where_new .= " AND products.name LIKE ?";
        $params_new .= "s";
        $bindValues_new[] = "%$product_name%";
    }

    if ($_SESSION['user']['role'] === 'store_admin') {
        $where_new .= " AND products.store_id = ?";
        $params_new .= "i";
        $bindValues_new[] = $_SESSION['user']['store_id'];
    }

    $sql_new = "
        SELECT 
            products.name AS product_name,
            stores.name AS store_name,
            products.stock,
            DATE(products.created_at) AS created_date
        FROM products
        LEFT JOIN stores ON products.store_id = stores.id
        WHERE $where_new
    ";

    $stmt_new = $conn->prepare($sql_new);
    if (!empty($params_new)) {
        $stmt_new->bind_param($params_new, ...$bindValues_new);
    }
    $stmt_new->execute();
    $new_products_result = $stmt_new->get_result();
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>گزارش فروش و محصولات جدید</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
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
        .datepicker-plot-area {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
    </style>
</head>

<body>
    <?php include('dashbord.php'); ?>

    <div class="main-content">
        <form method="GET" class="mb-3" id="report-form">
            <div class="row">
                <div class="col-md-3">
                    <label>از تاریخ:</label>
                    <input type="text" name="from_date" id="from_date" class="form-control shadow-none persian-date" style="border-color: #9FACB9;"
                           value="<?= htmlspecialchars($from_date_jalali ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label>تا تاریخ:</label>
                    <input type="text" name="to_date" id="to_date" class="form-control shadow-none persian-date" style="border-color: #9FACB9;"
                           value="<?= htmlspecialchars($to_date_jalali ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label>نام محصول:</label>
                    <input type="text" name="product_name" id="product_name" class="form-control shadow-none" style="border-color: #9FACB9;"
                        value="<?= htmlspecialchars($product_name ?? '') ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">نمایش گزارش</button>
                </div>
            </div>
        </form>

        <?php if ($report_result): ?>
        <h5 class="mt-4">گزارش فروش</h5>
        <div class="table-responsive mb-5">
            <table class="table table-bordered table-hover text-center align-middle">
                <thead>
                    <tr>
                        <th>تاریخ سفارش</th>
                        <th>نام محصول</th>
                        <?php if ($_SESSION['user']['role'] === 'super_admin'): ?>
                            <th>فروشگاه</th>
                        <?php endif; ?>
                        <th>تعداد فروش</th>
                        <th>مبلغ کل</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($report_result->num_rows > 0): ?>
                        <?php while ($row = $report_result->fetch_assoc()):
                            $grand_total += $row['total_sales'];
                            $jDate = Jalalian::fromDateTime($row['order_date'])->format('Y/m/d');
                        ?>
                            <tr>
                                <td><?= $jDate ?></td>
                                <td><?= htmlspecialchars($row['product_name']) ?></td>
                                <?php if ($_SESSION['user']['role'] === 'super_admin'): ?>
                                    <td><?= htmlspecialchars($row['store_name']) ?></td>
                                <?php endif; ?>
                                <td><?= (int)$row['total_quantity'] ?></td>
                                <td><?= number_format($row['total_sales']) ?> تومان</td>
                            </tr>
                        <?php endwhile; ?>
                        <tr class="table-info">
                            <td colspan="<?= $_SESSION['user']['role'] === 'super_admin' ? 4 : 3 ?>"><strong>جمع کل فروش</strong></td>
                            <td><strong><?= number_format($grand_total) ?> تومان</strong></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">هیچ فروشی در این بازه ثبت نشده است.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if ($new_products_result): ?>
        <h5 class="mt-4">محصولات ثبت‌شده</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-hover text-center align-middle">
                <thead>
                    <tr>
                        <th>تاریخ ثبت</th>
                        <th>نام محصول</th>
                        <?php if ($_SESSION['user']['role'] === 'super_admin'): ?>
                            <th>فروشگاه</th>
                        <?php endif; ?>
                        <th>موجودی</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($new_products_result->num_rows > 0): ?>
                        <?php while ($row = $new_products_result->fetch_assoc()):
                            $jDateNew = Jalalian::fromDateTime($row['created_date'])->format('Y/m/d');
                        ?>
                            <tr>
                                <td><?= $jDateNew ?></td>
                                <td><?= htmlspecialchars($row['product_name']) ?></td>
                                <?php if ($_SESSION['user']['role'] === 'super_admin'): ?>
                                    <td><?= htmlspecialchars($row['store_name']) ?></td>
                                <?php endif; ?>
                                <td><?= (int)$row['stock'] ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">محصول جدیدی در این بازه ثبت نشده است.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.persian-date').persianDatepicker({
                format: 'YYYY/MM/DD',
                observer: true,
                autoClose: true,
                initialValue: false
            });
            
            $('#report-form').on('submit', function(e) {
                if(($('#from_date').val() && !$('#to_date').val()) || 
                   (!$('#from_date').val() && $('#to_date').val())) {
                    alert('لطفا هر دو تاریخ را وارد کنید یا هر دو را خالی بگذارید');
                    return false;
                }
                return true;
            });
        });
    </script>
</body>
</html>