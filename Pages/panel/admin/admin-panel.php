<?php
session_start();
include('../../../config/db.php');

if (!isset($_SESSION['user'])) {
  header('Location: /login.php');
  exit;
}

$user = $_SESSION['user'];
$is_super_admin = $user['role'] === 'super_admin';

if ($is_super_admin) {
  // آمار کلی برای سوپر ادمین
  $res = $conn->query("SELECT COUNT(*) AS count FROM stores");
  $stores_count = $res->fetch_assoc()['count'] ?? 0;

  $res = $conn->query("SELECT COUNT(*) AS count FROM users");
  $users_count = $res->fetch_assoc()['count'] ?? 0;

  $res = $conn->query("SELECT COUNT(*) AS count FROM products");
  $products_count = $res->fetch_assoc()['count'] ?? 0;

  $res = $conn->query("SELECT COUNT(*) AS count FROM orders");
  $orders_count = $res->fetch_assoc()['count'] ?? 0;

  $today = date('Y-m-d');
  $res = $conn->query("SELECT IFNULL(SUM(total_price),0) AS total_today FROM orders WHERE DATE(created_at) = '$today'");
  $sales_today = $res->fetch_assoc()['total_today'] ?? 0;

  $week_ago = date('Y-m-d', strtotime('-7 days'));
  $res = $conn->query("SELECT IFNULL(SUM(total_price),0) AS total_week FROM orders WHERE DATE(created_at) BETWEEN '$week_ago' AND '$today'");
  $sales_week = $res->fetch_assoc()['total_week'] ?? 0;

  $current_month_start = date('Y-m-01');
  $res = $conn->query("SELECT IFNULL(SUM(total_price),0) AS total_month FROM orders WHERE DATE(created_at) >= '$current_month_start'");
  $sales_month = $res->fetch_assoc()['total_month'] ?? 0;
} else {
  // آمار فقط برای فروشگاه خودش
  $store_id = intval($user['store_id']);

  $res = $conn->query("SELECT COUNT(*) AS count FROM products WHERE store_id = $store_id");
  $products_count = $res->fetch_assoc()['count'] ?? 0;

  $res = $conn->query("
    SELECT COUNT(DISTINCT o.id) AS count 
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE p.store_id = $store_id
  ");
  $orders_count = $res->fetch_assoc()['count'] ?? 0;

  $today = date('Y-m-d');
  $res = $conn->query("
    SELECT IFNULL(SUM(o.total_price),0) AS total_today
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE DATE(o.created_at) = '$today' AND p.store_id = $store_id
  ");
  $sales_today = $res->fetch_assoc()['total_today'] ?? 0;

  $week_ago = date('Y-m-d', strtotime('-7 days'));
  $res = $conn->query("
    SELECT IFNULL(SUM(o.total_price),0) AS total_week
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE DATE(o.created_at) BETWEEN '$week_ago' AND '$today' AND p.store_id = $store_id
  ");
  $sales_week = $res->fetch_assoc()['total_week'] ?? 0;

  $current_month_start = date('Y-m-01');
  $res = $conn->query("
    SELECT IFNULL(SUM(o.total_price),0) AS total_month
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE DATE(o.created_at) >= '$current_month_start' AND p.store_id = $store_id
  ");
  $sales_month = $res->fetch_assoc()['total_month'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
  <meta charset="UTF-8">
  <title>داشبورد مدیریت</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .main-content {
      margin-right: 250px;
      padding: 2rem;
    }

    .card {
      box-shadow: 0 0.15rem 1.75rem rgb(33 40 50 / 15%);
    }

    .sidebar {
      width: 250px;
      height: 100vh;
      position: fixed;
      right: 0;
      top: 0;
      background: #212529;
      color: white;
    }

    .sidebar .nav-link {
      color: rgba(255, 255, 255, 0.8);
    }

    .sidebar .nav-link:hover {
      color: white;
      background: rgba(255, 255, 255, 0.1);
    }
  </style>
</head>

<body>

  <?php include('dashbord.php'); ?>

  <div class="main-content mt-5">
    <h4 class="mb-4">داشبورد مدیریت</h4>

    <section class="d-flex justify-content-center gap-3 m-4 text-center">
      <?php if ($is_super_admin): ?>
        <div class="col-md-3">
          <div class="card p-3 bg-success text-white rounded">
            <h5>تعداد فروشگاه‌ها</h5>
            <p class="fs-3"><?= $stores_count ?></p>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card p-3 bg-warning text-white rounded">
            <h5>تعداد کاربران</h5>
            <p class="fs-3"><?= $users_count ?></p>
          </div>
        </div>
      <?php endif; ?>

      <div class="col-md-3">
        <div class="card p-3 bg-danger text-white rounded">
          <h5>تعداد سفارشات</h5>
          <p class="fs-3"><?= $orders_count ?></p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card p-3 bg-info text-white rounded">
          <h5>تعداد محصولات</h5>
          <p class="fs-3"><?= $products_count ?></p>
        </div>
      </div>
    </section>

    <section class="mb-5 mt-3">
      <h5 class="mb-3">آمار فروش</h5>
      <div class="card p-3">
        <div class="row">
          <div class="col-md-4 text-center border-end">
            <h6>فروش امروز</h6>
            <p class="fs-4 text-success"><?= number_format($sales_today) ?> تومان</p>
          </div>
          <div class="col-md-4 text-center border-end">
            <h6>فروش هفته گذشته</h6>
            <p class="fs-4 text-primary"><?= number_format($sales_week) ?> تومان</p>
          </div>
          <div class="col-md-4 text-center">
            <h6>فروش ماه جاری</h6>
            <p class="fs-4 text-danger"><?= number_format($sales_month) ?> تومان</p>
          </div>
        </div>
      </div>
    </section>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>