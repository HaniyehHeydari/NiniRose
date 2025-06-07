<?php
session_start();
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}


// بررسی وجود پیام از سشن
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']); // حذف پیام پس از نمایش
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>درخواست همکاری</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-light">

    <div class="container py-5">
        <div class="mx-auto mt-5 bg-white p-5 rounded-3 shadow" style="max-width: 500px;">
            <h5 class="text-center mb-4">فرم درخواست مغازه</h5>
            <form action="submit-request.php" method="POST">
                <div class="mb-3">
                    <label for="store_name" class="form-label">نام مغازه</label>
                    <input type="text" name="name" id="store_name" class="form-control rounded-3 shadow-none" style="border-color: #9FACB9;" required>
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">آدرس مغازه</label>
                    <textarea name="address" id="address" rows="2" class="form-control rounded-3 shadow-none " style="border-color: #9FACB9;" required></textarea>
                </div>

                <div class="mb-4">
                    <label for="phone" class="form-label">شماره تماس</label>
                    <input type="text" name="phone" id="phone" class="form-control rounded-3 shadow-none " style="border-color: #9FACB9;" required>
                </div>

                <div class="d-flex justify-content-center gap-5">
                    <button type="submit" class="btn text-white" style="background-color: #EA6269; width: 40%;">ارسال درخواست</button>
                    <a href="../../view/MainPage.php" class="btn text-white" style="background-color: #6c757d; width: 40%;">بازگشت</a>
                </div>
            </form>
        </div>
    </div>

    <?php if ($alert): ?>
        <script>
            Swal.fire({
                icon: '<?= $alert["type"] ?>',
                title: '<?= $alert["type"] === "success" ? "موفقیت" : "خطا" ?>',
                text: '<?= $alert["message"] ?>',
                timer: 3000, // مدت زمان نمایش به میلی‌ثانیه
                timerProgressBar: true,
                showConfirmButton: false
            });
        </script>
    <?php endif; ?>

</body>

</html>