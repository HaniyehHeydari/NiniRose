<?php
include('../../config/db.php');
include_once dirname(__DIR__) . '/../Config/config.php';

$slider_result = $conn->query("SELECT * FROM sliders ORDER BY created_at DESC");
$product_result = $conn->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 10");
$article_result = $conn->query("SELECT * FROM articles ORDER BY created_at DESC LIMIT 4"); // 👈 مقالات
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>MainPage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <style>
        #productScroll {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        #productScroll::-webkit-scrollbar {
            display: none;
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

        .product-scroll-wrapper {
            position: relative;
        }

        .scroll-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 2;
            width: 40px;
            height: 40px;
            padding: 0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.8;
            background-color: #dc3545;
            color: white;
            border: none;
        }

        .scroll-btn:hover {
            opacity: 1;
        }

        .scroll-btn.prev {
            right: 5px;
        }

        .scroll-btn.next {
            left: 5px;
        }
    </style>
</head>

<body>
    <?php include('../../Templates/Header.php') ?>

    <div class="container-fluid my-5 px-5">
        <!-- اسلایدر -->
        <?php if ($slider_result && $slider_result->num_rows > 0): ?>
            <div id="mainCarousel" class="carousel slide mb-5" data-bs-ride="carousel">
                <div class="carousel-inner rounded-4 shadow">
                    <?php
                    $index = 0;
                    while ($slide = $slider_result->fetch_assoc()):
                        $active = $index === 0 ? 'active' : '';
                    ?>
                        <div class="carousel-item <?= $active ?>">
                            <img src="../../<?= htmlspecialchars($slide['image']) ?>" class="d-block w-100" alt="slider image">
                        </div>
                    <?php $index++;
                    endwhile; ?>
                </div>

                <button class="carousel-control-prev" type="button" data-bs-target="#mainCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">قبلی</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#mainCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">بعدی</span>
                </button>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">اسلایدری برای نمایش وجود ندارد.</div>
        <?php endif; ?>

        <!-- نمایش محصولات -->
        <div class="container-fluid my-5 px-4">
            <h4 class="text-start mb-4">جدیدترین محصولات</h4>

            <div class="product-scroll-wrapper">
                <button class="scroll-btn prev" onclick="scrollProducts(-1)">
                    <i class="bi bi-chevron-right fs-5"></i>
                </button>
                <button class="scroll-btn next" onclick="scrollProducts(1)">
                    <i class="bi bi-chevron-left fs-5"></i>
                </button>

                <div id="productScroll" class="d-flex overflow-auto gap-3">
                    <?php while ($product = $product_result->fetch_assoc()): ?>
                        <div class="card product-card shadow-sm">
                            <img src="../../<?= htmlspecialchars($product['image']) ?>" class="card-img-top product-image" alt="<?= htmlspecialchars($product['name']) ?>">
                            <div class="card-body">
                                <h6 class="card-title text-start hover-pointer"><?= htmlspecialchars($product['name']) ?></h6>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted"><?= number_format($product['price']) ?> تومان</span>
                                    <a href="product-detail.php?id=<?= $product['id'] ?>" class="btn btn-outline-danger btn-sm">مشاهده</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <!-- نمایش مقالات -->
<div class="container-fluid my-5 px-4">
    <h4 class="text-start mb-4">آخرین مقالات</h4>
    <div class="row g-4">
        <?php while ($article = $article_result->fetch_assoc()): ?>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card h-100 shadow-sm">
                    <img src="../../<?= htmlspecialchars($article['image']) ?>" class="card-img-top" style="height: 180px; object-fit: cover;" alt="<?= htmlspecialchars($article['title']) ?>">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title text-start hover-pointer"><?= htmlspecialchars($article['title']) ?></h5>
                        <div class="mt-auto text-center">
                            <a href="article-detail.php?id=<?= $article['id'] ?>" class="btn btn-danger btn-md w-100 mt-2">مطالعه کنید</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>


    </div>

    <?php include('../../Templates/Footer.php') ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function scrollProducts(direction) {
            const scrollContainer = document.getElementById('productScroll');
            const scrollAmount = 250;

            scrollContainer.scrollBy({
                left: direction * scrollAmount,
                behavior: 'smooth'
            });
        }
    </script>
</body>

</html>