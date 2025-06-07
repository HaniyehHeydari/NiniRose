<?php
session_start();
include('../../config/db.php');
include_once dirname(__DIR__) . '/../Config/config.php';
require '../../vendor/autoload.php';

use Morilog\Jalali\Jalalian;

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: MainPage.php");
    exit;
}

// ۱. دریافت اطلاعات محصول از جمله ستون stock
$stmt = $conn->prepare("SELECT p.*, c.name AS category_name, s.name AS store_name 
                        FROM products p
                        LEFT JOIN categories c ON p.category_id = c.id
                        LEFT JOIN stores s ON p.store_id = s.id
                        WHERE p.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header("Location: MainPage.php");
    exit;
}

// ۲. دریافت سایزها (در صورت وجود)
$sizes = [];
$size_stmt = $conn->prepare("SELECT DISTINCT size FROM detail WHERE product_id = ? AND size IS NOT NULL AND size != ''");
$size_stmt->bind_param("i", $id);
$size_stmt->execute();
$res = $size_stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $sizes[] = $row['size'];
}
$size_stmt->close();

// ۳. دریافت رنگ‌ها (در صورت وجود)
$colors = [];
$color_stmt = $conn->prepare("SELECT DISTINCT color FROM detail WHERE product_id = ? AND color IS NOT NULL AND color != ''");
$color_stmt->bind_param("i", $id);
$color_stmt->execute();
$res = $color_stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $colors[] = $row['color'];
}
$color_stmt->close();

// ۴. دریافت توضیحات تکمیلی (در صورت وجود)
$detail_stmt = $conn->prepare("SELECT description FROM detail WHERE product_id = ? AND description IS NOT NULL AND description != '' LIMIT 1");
$detail_stmt->bind_param("i", $id);
$detail_stmt->execute();
$detail_res = $detail_stmt->get_result();
$detail = $detail_res->fetch_assoc();
$detail_stmt->close();

// ۵. گرفتن تعداد موجودی محصول برای غیرفعال‌سازی دکمه
$stock = intval($product['stock'] ?? 0);
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <title>جزییات محصول - <?= htmlspecialchars($product['name']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }

        .product-box {
            background-color: #f8f9fa;
            border-radius: 1rem;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .top-green-bar {
            height: 6px;
            background-color: #198754;
            border-top-right-radius: 1rem;
            border-top-left-radius: 1rem;
        }

        .input-group input::-webkit-outer-spin-button,
        .input-group input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .input-group input[type=number] {
            -moz-appearance: textfield;
        }

        .minus-btn:hover,
        .plus-btn:hover {
            background-color: #198754 !important;
            color: white !important;
            border-color: #198754 !important;
        }

        .reply-form-container {
            margin-top: 0.5rem;
        }

        /* تب‌ها */
        .nav-tabs .nav-link {
            border: none !important;
            color: #000 !important;
            /* رنگ متن مشکی */
            padding: 10px 20px;
            margin-bottom: -1px;
            background-color: transparent !important;
            border-radius: 0;
        }

        .nav-tabs .nav-link:hover {
            border: none !important;
            color: #000 !important;
            /* رنگ متن مشکی هنگام هاور */
            background-color: transparent !important;
            box-shadow: none !important;
            outline: none !important;
        }

        /* تب فعال */
        .nav-tabs .nav-link.active {
            border: none !important;
            color: #dc3545 !important;
            /* رنگ متن قرمز */
            font-weight: bold;
            background-color: transparent !important;
            position: relative;
            box-shadow: none !important;
            outline: none !important;
        }

        /* خط پایین قرمز برای تب فعال */
        .nav-tabs .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 10%;
            right: 10%;
            height: 3px;
            background-color: #dc3545;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <?php include('../../Templates/Header.php') ?>

    <div class="container my-5">
        <div class="product-box">
            <div class="top-green-bar"></div>
            <div class="row g-4 p-4 align-items-start">
                <div class="col-md-5">
                    <img src="../../<?= htmlspecialchars($product['image']) ?>" class="img-fluid rounded shadow-sm" alt="<?= htmlspecialchars($product['name']) ?>">
                </div>
                <div class="col-md-6">
                    <h4><?= htmlspecialchars($product['name']) ?></h4>
                    <?php if (!empty($product['description'])): ?>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                    <?php endif; ?>

                    <!-- ۶. فرم افزودن به سبد خرید با بررسی stock -->
                    <form id="add-to-cart-form">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <input type="hidden" name="quantity" value="1" id="quantity-input">

                        <?php if (!empty($sizes)): ?>
                            <div class="mb-3">
                                <label>سایز:</label>
                                <select class="form-select w-50 shadow-none border" name="size">
                                    <option value="" disabled selected>انتخاب سایز</option>
                                    <?php foreach ($sizes as $size): ?>
                                        <option value="<?= htmlspecialchars($size) ?>"><?= htmlspecialchars($size) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($colors)): ?>
                            <div class="mb-3">
                                <label>رنگ:</label>
                                <select class="form-select w-50 shadow-none border" name="color">
                                    <option value="" disabled selected>انتخاب رنگ</option>
                                    <?php foreach ($colors as $color): ?>
                                        <option value="<?= htmlspecialchars($color) ?>"><?= htmlspecialchars($color) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <!-- ۷. نمایش قیمت و موجودی -->
                        <h5 class="text-success fw-bold"><?= number_format($product['price']) ?> تومان</h5>

                        <?php if ($stock > 0): ?>
                            <div class="mb-2">
                                <span class="small text-secondary">تعداد موجود: <?= $stock ?> عدد</span>
                            </div>
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div>
                                    <div class="input-group" style="width: 120px;">
                                        <button class="btn border btn-outline-secondary minus-btn bg-white" type="button">-</button>
                                        <input type="number" name="quantity" id="quantity" class="form-control border bg-white text-center quantity-input" value="1" min="1" max="<?= $stock ?>">
                                        <button class="btn border btn-outline-secondary plus-btn bg-white" type="button">+</button>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-danger" <?= empty($_SESSION['user']) ? 'disabled' : '' ?>>
                                    افزودن به سبد خرید
                                </button>
                                <?php if (empty($_SESSION['user'])): ?>
                                    <div class="text-danger small">برای افزودن محصول به سبد خرید باید وارد حساب کاربری خود شوید</div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <!-- ۸. اگر ناموجود بود -->
                            <div class="alert alert-danger py-2 px-3 d-inline-block">ناموجود</div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5">
        <ul class="nav nav-tabs" id="productTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="description-tab" data-bs-toggle="tab"
                    data-bs-target="#description" type="button" role="tab"
                    aria-controls="description" aria-selected="true">
                    توضیحات محصول
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="comments-tab" data-bs-toggle="tab"
                    data-bs-target="#comments" type="button" role="tab"
                    aria-controls="comments" aria-selected="false">
                    نظرات کاربران
                </button>
            </li>
        </ul>

        <div class="tab-content" id="productTabsContent">
            <!-- تب توضیحات -->
            <div class="tab-pane fade show active" id="description" role="tabpanel"
                aria-labelledby="description-tab">
                <?php if (!empty($detail['description'])): ?>
                    <div class="product-box mt-3">
                        <div class="top-green-bar"></div>
                        <div class="p-4 bg-light">
                            <p class="text-muted"><?= nl2br(htmlspecialchars($detail['description'])) ?></p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mt-3">توضیحاتی برای این محصول ثبت نشده است.</div>
                <?php endif; ?>
            </div>

            <!-- تب نظرات -->
            <div class="tab-pane fade" id="comments" role="tabpanel"
                aria-labelledby="comments-tab">
                <div class="product-box mt-3">
                    <div class="top-green-bar"></div>
                    <div class="p-4 bg-light">
                        <?php
                        // ۱. فراخوانی کامنت‌های اصلی (parent_id IS NULL)
                        $comment_stmt = $conn->prepare("
                            SELECT c.id, c.content, c.created_at, u.username 
                            FROM comments c 
                            LEFT JOIN users u ON c.user_id = u.id 
                            WHERE c.product_id = ? AND c.parent_id IS NULL
                            ORDER BY c.created_at DESC
                        ");
                        $comment_stmt->bind_param("i", $id);
                        $comment_stmt->execute();
                        $comments_res = $comment_stmt->get_result();

                        if ($comments_res->num_rows > 0):
                            while ($comment = $comments_res->fetch_assoc()):
                                $comment_id = $comment['id'];
                        ?>
                                <!-- هر کامنت اصلی -->
                                <div class="mb-3">
                                    <strong><?= htmlspecialchars($comment['username']) ?></strong>
                                    <div class="text-muted"><?= nl2br(htmlspecialchars($comment['content'])) ?></div>
                                    <div class="text-secondary small"><?= Jalalian::fromDateTime($comment['created_at'])->format('Y/m/d H:i') ?></div>

                                    <!-- ۲. دکمه ریپلای -->
                                    <button class="btn btn-sm text-primary mt-2 reply-btn rounded-pill" data-comment-id="<?= $comment_id ?>">
                                        <i class="bi bi-reply-fill"></i> پاسخ
                                    </button>


                                    <!-- ۳. نمایش ریپلای‌های آن کامنت -->
                                    <?php
                                    $reply_stmt = $conn->prepare("
                                SELECT c2.id, c2.content, c2.created_at, u2.username 
                                FROM comments c2 
                                LEFT JOIN users u2 ON c2.user_id = u2.id 
                                WHERE c2.parent_id = ? 
                                ORDER BY c2.created_at ASC
                            ");
                                    $reply_stmt->bind_param("i", $comment_id);
                                    $reply_stmt->execute();
                                    $replies_res = $reply_stmt->get_result();

                                    if ($replies_res->num_rows > 0):
                                    ?>
                                        <div class="mt-3 ps-4 border-start border-2 border-muted">
                                            <?php while ($reply = $replies_res->fetch_assoc()): ?>
                                                <div class="mb-3">
                                                    <strong><?= htmlspecialchars($reply['username']) ?></strong>
                                                    <div class="text-muted"><?= nl2br(htmlspecialchars($reply['content'])) ?></div>
                                                    <div class="text-secondary small"><?= Jalalian::fromDateTime($reply['created_at'])->format('Y/m/d H:i') ?></div>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                    <?php
                                    endif;
                                    $reply_stmt->close();
                                    ?>

                                    <!-- ۴. فرم ریپلای (ابتدا مخفی) -->
                                    <div class="mt-2 reply-form-container" id="reply-form-<?= $comment_id ?>" style="display: none;">
                                        <form action="save-comment.php" method="POST" class="mt-2">
                                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                            <input type="hidden" name="parent_id" value="<?= $comment_id ?>">
                                            <div class="mb-2">
                                                <textarea name="content" rows="2" class="form-control shadow-none border" placeholder="نظر خود را بنویسید..." required></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-success btn-sm">ارسال پاسخ</button>
                                            <button type="button" class="btn btn-secondary btn-sm cancel-reply" data-comment-id="<?= $comment_id ?>">انصراف</button>
                                        </form>
                                    </div>
                                </div>
                                <hr>
                            <?php
                            endwhile;
                        else:
                            ?>
                            <div class="text-muted">هنوز نظری ثبت نشده است.</div>
                        <?php
                        endif;
                        $comment_stmt->close();
                        ?>
                    </div>
                </div>

                <!-- ۵. فرم ارسال کامنت جدید (بدون parent_id) -->
                <?php if (!empty($_SESSION['user'])): ?>
                    <form id="comment-form" action="save-comment.php" method="POST" class="mt-4">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <div class="mb-3">
                            <label for="content" class="form-label">نظر خود را بنویسید:</label>
                            <textarea name="content" id="content" rows="4" class="form-control shadow-none border" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-success">ارسال نظر</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning mt-4">
                        برای ارسال نظر، ابتدا وارد حساب کاربری خود شوید.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include('../../Templates/Footer.php') ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // کنترل تب‌ها
            const tabElms = document.querySelectorAll('button[data-bs-toggle="tab"]');
            tabElms.forEach(tabEl => {
                tabEl.addEventListener('click', function(e) {
                    e.preventDefault();
                    const tab = new bootstrap.Tab(this);
                    tab.show();
                });
            });

            // کنترل دکمه‌ی + و -
            const form = document.getElementById('add-to-cart-form');
            const minusBtn = document.querySelector('.minus-btn');
            const plusBtn = document.querySelector('.plus-btn');
            const quantityInput = document.getElementById('quantity');

            minusBtn.addEventListener('click', () => {
                const val = parseInt(quantityInput.value);
                if (val > 1) quantityInput.value = val - 1;
            });

            plusBtn.addEventListener('click', () => {
                // جلوگیری از افزایش بیش از موجودی
                const maxStock = <?= $stock ?>;
                const val = parseInt(quantityInput.value);
                if (val < maxStock) {
                    quantityInput.value = val + 1;
                }
            });

            form.addEventListener('submit', function(e) {
                <?php if (empty($_SESSION['user'])): ?>
                    e.preventDefault();
                    Swal.fire({
                        icon: 'info',
                        title: 'ورود مورد نیاز است',
                        text: 'برای افزودن محصول به سبد خرید باید وارد حساب کاربری خود شوید',
                        confirmButtonText: 'ورود',
                        confirmButtonColor: '#3085d6',
                    }).then(() => {
                        window.location.href = '../../Auth/Login.php';
                    });
                    return;
                <?php endif; ?>

                // اگر محصول ناموجود است، از ارسال فرم جلوگیری کن
                const maxStock = <?= $stock ?>;
                if (maxStock <= 0) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'محصول ناموجود است',
                        text: 'این محصول موجودی ندارد',
                        confirmButtonText: 'باشه',
                        confirmButtonColor: '#3085d6',
                    });
                    return;
                }

                // کنترل انتخاب سایز در صورت وجود
                const sizeSelect = form.querySelector('select[name="size"]');
                if (sizeSelect && !sizeSelect.value) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'انتخاب سایز ضروری است',
                        text: 'لطفاً سایز محصول را انتخاب نمایید',
                        confirmButtonText: 'متوجه شدم',
                        confirmButtonColor: '#3085d6',
                    });
                    sizeSelect.focus();
                    return;
                }

                // کنترل انتخاب رنگ در صورت وجود
                const colorSelect = form.querySelector('select[name="color"]');
                if (colorSelect && !colorSelect.value) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'انتخاب رنگ ضروری است',
                        text: 'لطفاً رنگ محصول را انتخاب نمایید',
                        confirmButtonText: 'متوجه شدم',
                        confirmButtonColor: '#3085d6',
                    });
                    colorSelect.focus();
                    return;
                }

                // ارسال AJAX به add-to-cart.php
                e.preventDefault();
                const formData = new FormData(form);
                fetch('../panel/user/add-to-cart.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const cartCount = document.getElementById('cart-count');
                            if (cartCount) cartCount.textContent = data.total;
                            Swal.fire({
                                icon: 'success',
                                title: 'موفقیت',
                                text: 'محصول با موفقیت به سبد خرید اضافه شد',
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true,
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطا در افزودن به سبد خرید',
                                text: data.message || 'خطایی در افزودن محصول به سبد خرید رخ داده است',
                                confirmButtonText: 'متوجه شدم',
                                confirmButtonColor: '#3085d6',
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'خطای سرور',
                            text: 'خطا در ارتباط با سرور، لطفاً مجدداً تلاش نمایید',
                            confirmButtonText: 'متوجه شدم',
                            confirmButtonColor: '#3085d6',
                        });
                        console.error(error);
                    });
            });

            // نمایش/مخفی‌سازی فرم ریپلای
            document.querySelectorAll('.reply-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const commentId = this.getAttribute('data-comment-id');
                    const formDiv = document.getElementById('reply-form-' + commentId);
                    if (formDiv) {
                        formDiv.style.display = 'block';
                    }
                });
            });

            document.querySelectorAll('.cancel-reply').forEach(btn => {
                btn.addEventListener('click', function() {
                    const commentId = this.getAttribute('data-comment-id');
                    const formDiv = document.getElementById('reply-form-' + commentId);
                    if (formDiv) {
                        formDiv.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>

</html>