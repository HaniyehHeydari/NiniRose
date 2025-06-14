<?php
include('../../config/db.php');

// بررسی وجود پارامتر id
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("آیدی دسته‌بندی مشخص نشده است!");
}

$categoryId = $_GET['id'];

// دریافت اطلاعات دسته‌بندی
$category_stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
$category_stmt->bind_param("i", $categoryId);
$category_stmt->execute();
$category_result = $category_stmt->get_result();
$category = $category_result->fetch_assoc();
$category_stmt->close();

if (!$category) {
    die("دسته‌بندی پیدا نشد!");
}

// دریافت محصولات این دسته‌بندی
$products_stmt = $conn->prepare("
    SELECT * FROM products 
    WHERE category_id = ?
    ORDER BY created_at DESC
");
$products_stmt->bind_param("i", $categoryId);
$products_stmt->execute();
$products_result = $products_stmt->get_result();
$products = $products_result->fetch_all(MYSQLI_ASSOC);
$products_stmt->close();
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($category['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        .product-card {
            transition: transform 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }


        .product-image {
            height: 180px;
            width: 100%;
            object-fit: cover;
            /* برش خودکار با حفظ نسبت */
            object-position: center;
            /* تمرکز روی مرکز تصویر */
        }
    </style>
</head>

<body>
    <?php include('../../Templates/Header.php') ?>

    <div class="container py-5">

        <!-- لیست محصولات -->
        <h3 class="mb-4"><?= htmlspecialchars($category['name']) ?></h3>
        <?php if (!empty($products)): ?>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                <?php foreach ($products as $product): ?>
                    <div class="col">
                        <div class="card product-card shadow-sm h-100">
                            <img src="../../<?= htmlspecialchars($product['image']) ?>"
                                class="card-img-top product-image"
                                alt="<?= htmlspecialchars($product['name']) ?>">
                            <div class="card-body d-flex flex-column">
                                <h6 class="card-title text-start hover-pointer"><?= htmlspecialchars($product['name']) ?></h6>
                                <div class="mt-auto d-flex justify-content-between align-items-center">
                                    <span class="text-muted"><?= number_format($product['price']) ?> تومان</span>
                                    <a href="product-detail.php?id=<?= $product['id'] ?>" class="btn btn-outline-success btn-sm">
                                        مشاهده
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">
                هیچ محصولی در این دسته‌بندی یافت نشد.
            </div>
        <?php endif; ?>
    </div>

    <?php include('../../Templates/Footer.php') ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>