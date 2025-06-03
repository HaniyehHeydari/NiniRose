<?php

include('../config/db.php');

// دریافت عبارت جستجو
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($searchQuery)) {
    header("Location: " . BASE_URL);
    exit();
}

// جستجو در دیتابیس
$results = [];
$queryParam = "%" . $searchQuery . "%";

$sql = "SELECT * FROM products WHERE name LIKE ? OR description LIKE ? ORDER BY name ASC";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("ss", $queryParam, $queryParam);
    $stmt->execute();
    $result = $stmt->get_result();
    $results = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    die("خطا در اجرای کوئری: " . $conn->error);
}
?>


<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نتایج جستجو برای <?php echo htmlspecialchars($searchQuery); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .search-header {
            margin: 30px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 10px;
        }

        .product-card {
            transition: transform 0.3s ease;
            width: 230px !important;
            flex-shrink: 0;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-image {
            height: 180px !important;
            object-fit: cover;
        }

        .hover-pointer {
            cursor: pointer;
            transition: color 0.3s;
        }

        .hover-pointer:hover {
            color: #dc3545;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; // هدر سایت 
    ?>

    <div class="container mt-5">
        <div class="search-header">
            <h4>نتایج جستجو برای: "<?php echo htmlspecialchars($searchQuery); ?>"</h4>
            <p class="text-muted"><?php echo count($results); ?> محصول یافت شد</p>
        </div>

        <?php if (count($results) > 0): ?>
            <div class="row">
                <?php foreach ($results as $product): ?>
                    <div class="col-md-3 col-sm-6 d-flex justify-content-center mb-4">
                        <div class="card product-card shadow-sm">
                            <img src="../<?= htmlspecialchars($product['image']) ?>" class="card-img-top product-image" alt="<?= htmlspecialchars($product['name']) ?>">
                            <div class="card-body">
                                <h6 class="card-title text-start hover-pointer"><?= htmlspecialchars($product['name']) ?></h6>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted"><?= number_format($product['price']) ?> تومان</span>
                                    <a href="../Pages/view/product-detail.php?id=<?= $product['id'] ?>" class="btn btn-outline-danger btn-sm">مشاهده</a>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                 هیچ محصولی با عبارت"<?php echo htmlspecialchars($searchQuery); ?>" یافت نشد.
            </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; // فوتر سایت 
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>