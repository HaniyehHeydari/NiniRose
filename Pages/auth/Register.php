<?php include('./Register-Validation.php'); ?>

<!DOCTYPE html>
<html lang="fa">


<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../../Public/css/Register.css" />
    <title>Register</title>
</head>


<body dir="rtl">

    <div class="background-blur-layer"></div>

    <div class="main">
        <form id="form-register" name="register" method="post">
            <div class="logo">
                <a href="../../Pages/view/MainPage.php">
                    <img src="../../Public/image/looo.png" alt="Logo" width="160" height="90">
                </a>
            </div>
            <div id="usernames">
                <label for="username">نام کاربری</label><br />
                <input type="text" id="username" name="username" placeholder="HaniyehHeydari"
                    title="فقط حروف مجاز است"
                    value="<?= htmlspecialchars($username ?? '') ?>" />
                <?php if (isset($errors['username'])): ?>
                    <span class="error-message"><?= $errors['username'] ?></span>
                <?php endif; ?>
            </div>

            <div id="email">
                <label for="emailuser">ایمیل</label><br />
                <input type="text" id="emailuser" name="email" placeholder="HaniyehHeydari@gmail.com"
                    title="لطفا ایمیل معتبر وارد کنید"
                    value="<?= htmlspecialchars($email ?? '') ?>" />
                <?php if (isset($errors['email'])): ?>
                    <span class="error-message"><?= $errors['email'] ?></span>
                <?php endif; ?>
            </div>

            <div id="password">
                <label for="pass">رمز عبور</label>
                <input type="password" id="pass" name="password" placeholder="********"
                    title="لطفا یک رمز عبور معتبر وارد کنید." />
                <?php if (isset($errors['password'])): ?>
                    <span class="error-message"><?= $errors['password'] ?></span>
                <?php endif; ?>
            </div>

            <div id="mobile">
                <label for="phone">شماره تلفن</label>
                <input type="text" id="phone" name="phone" placeholder="6224***0910"
                    title="لطفا شماره تلفن معتبر وارد کنید"
                    value="<?= htmlspecialchars($phone ?? '') ?>" />
                <?php if (isset($errors['phone'])): ?>
                    <span class="error-message"><?= $errors['phone'] ?></span>
                <?php endif; ?>
            </div>

            <button name="button" id="sabtnam" type="submit">ثبت نام</button>

            <p id="sabt">
                قبلا ثبت نام کرده‌اید؟<a href="login.php" id="vorodhesab"> ورود به حساب</a>
            </p>
        </form>
    </div>

</body>

</html>