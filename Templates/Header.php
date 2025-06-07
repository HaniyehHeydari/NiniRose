<?php include_once dirname(__DIR__) . '/Config/config.php'; ?>

<!DOCTYPE html>
<html lang="fa">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Public/css/fonts.css">
    <style>
        body {
            font-family: 'YourFontName', sans-serif !important;
        }

        header {
            border-bottom: 2px solid #fdf1f3;
        }

        .search-input::placeholder {
            color: #6c757d;
        }

        input {
            background-color: #EDF0F2 !important;
        }

        .dropdown-item:hover,
        .dropdown-item:focus,
        .dropdown-item:active {
            background-color: #fdf1f3 !important;
            color: #d63c53 !important;
        }

        .cart-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            width: 45px;
            height: 45px;
            padding: 0;
            border: none;
            outline: none;
            box-shadow: none;
            background: none;
        }

        .cart-btn:focus {
            outline: none !important;
            box-shadow: none !important;
        }

        .cart-btn i {
            color: #57616C;
        }

        .cart-btn .badge {
            position: absolute;
            top: 0;
            start: 100%;
            transform: translate(-50%, 0);
            background-color: red;
            border-radius: 999px;
        }
    </style>
    <title>Header</title>
</head>

<body class="m-0 p-0">

    <header class="bg-light py-2 px-5 gap-5 d-flex align-items-center justify-content-between" style="background-color: white !important; direction: rtl;">
        <!-- لوگو -->
        <div class="d-flex align-items-center me-3">
            <a href="<?php echo BASE_URL; ?>Pages/view/MainPage.php">
                <img src="<?php echo BASE_URL; ?>Public/image/looo.png" alt="Logo" width="150" height="80">
            </a>
        </div>

        <!-- سرچ و کاربر -->
        <div class="d-flex align-items-center gap-5 flex-grow-1 justify-content-between pe-3">

            <!-- باکس جستجو -->
            <form action="<?php echo BASE_URL; ?>Templates/search.php" method="GET" class="d-flex align-items-center border rounded-4 bg-white px-2" style="background-color: #EDF0F2 !important; width: 35%; height: 45px; border-radius: 10px; border: 1px solid #9FACB9;">
                <input type="text" name="q" class="form-control border-0 shadow-none px-2 search-input" placeholder="جستجو..." maxlength="100" required>
                <button type="submit" class="btn p-0 border-0 bg-transparent">
                    <img src="<?php echo BASE_URL; ?>Public/image/search.png" alt="Search" style="width: 20px; height: 20px;">
                </button>
            </form>


            <div class="d-flex align-items-center gap-2">

                <a href="<?php echo BASE_URL; ?>Pages/panel/user/cart.php" class="cart-btn position-relative">
                    <i class="bi bi-cart fs-3" style="color: #57616C;"></i>
                    <?php if (isset($_SESSION['cart_total_items']) && $_SESSION['cart_total_items'] > 0): ?>
                        <span class="cart-count"><?= $_SESSION['cart_total_items'] ?></span>
                    <?php endif; ?>
                </a>

                <!-- دکمه ورود/ثبت‌نام یا نام کاربر -->
                <div class="position-relative d-flex align-items-center text-white rounded-4 px-3"
                    style="height: 45px; border-radius: 10px; background-color: #EA6269; cursor: pointer;"
                    id="userAccount">

                    <img src="<?php echo BASE_URL; ?>Public/image/i-user.png" alt="User" style="width: 20px; height: 20px;">

                    <div class="ms-2" id="dropdownToggle">
                        <?php
                        echo isset($_SESSION['user']) && isset($_SESSION['user']['username'])
                            ? htmlspecialchars($_SESSION['user']['username'])
                            : 'ورود / ثبت‌نام';
                        ?>
                    </div>

                    <?php if (isset($_SESSION['user']) && isset($_SESSION['user']['username'])): ?>
                        <ul class="dropdown-menu mt-2 position-absolute end-0" id="userDropdown"
                            style="top: 45px; min-width: 140px; z-index: 100; display: none;">
                            <li><a class="dropdown-item d-flex align-items-center"
                                    href="<?php echo BASE_URL; ?>Pages/panel/user/profile.php">
                                    <i class="bi bi-person me-2" style="font-size: 1.3rem;"></i> اطلاعات کاربری</a></li>

                            <?php if (isset($_SESSION['user']['role'])): ?>
                                <?php if ($_SESSION['user']['role'] === 'super_admin'): ?>
                                    <li><a class="dropdown-item d-flex align-items-center"
                                            href="<?php echo BASE_URL; ?>Pages/panel/admin/admin-panel.php">
                                            <i class="bi bi-shield-lock me-2" style="font-size: 1.3rem;"></i> پنل ادمین</a></li>
                                <?php elseif ($_SESSION['user']['role'] === 'store_admin'): ?>
                                    <li><a class="dropdown-item d-flex align-items-center"
                                            href="<?php echo BASE_URL; ?>Pages/panel/admin/admin-panel.php">
                                            <i class="bi bi-shop me-2" style="font-size: 1.3rem;"></i> فروشگاه</a></li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <li><a class="dropdown-item d-flex align-items-center"
                                    href="<?php echo BASE_URL; ?>Pages/panel/user/orders.php">
                                    <i class="bi bi-card-checklist me-2" style="font-size: 1.3rem;"></i> سفارشات</a></li>
                            <li><a class="dropdown-item d-flex align-items-center"
                                    href="<?php echo BASE_URL; ?>Pages/panel/user/change-password.php">
                                    <i class="bi bi-lock me-2" style="font-size: 1.3rem;"></i> تغییر رمز عبور</a></li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="#" onclick="confirmLogout(event)">
                                    <i class="bi bi-box-arrow-right me-2" style="font-size: 1.3rem;"></i> خروج
                                </a>
                            </li>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmLogout(event) {
            event.preventDefault();
            Swal.fire({
                title: 'آیا می‌خواهید خارج شوید؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'بله، خروج',
                cancelButtonText: 'انصراف',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "<?php echo BASE_URL; ?>Pages/panel/user/logout-validation.php";
                }
            });
        }
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const userAccount = document.getElementById("userAccount");
            const dropdown = document.getElementById("userDropdown");

            // بررسی آیا کاربر لاگین کرده یا نه
            const isLoggedIn = <?php echo isset($_SESSION['user']) ? 'true' : 'false'; ?>;

            userAccount.addEventListener("click", function(e) {
                if (!isLoggedIn) {
                    // اگر لاگین نیست، به صفحه ورود هدایت شود
                    window.location.href = "<?php echo BASE_URL; ?>Pages/auth/login.php";
                } else if (dropdown) {
                    // اگر لاگین کرده و dropdown وجود دارد، نمایش/مخفی کردن آن
                    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
                }
            });

            // کلیک خارج از منو باعث بسته شدن آن شود
            document.addEventListener("click", function(event) {
                if (dropdown && !userAccount.contains(event.target)) {
                    dropdown.style.display = 'none';
                }
            });
        });
    </script>

</body>

</html>