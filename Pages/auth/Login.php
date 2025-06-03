<?php include "./Login-Validation.php" ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../../Public/css/L.css" />
    <title>Login</title>
</head>

<body dir="rtl">

    <div class="background-blur-layer"></div>

    <div class="main">
        <form id="form-sign" name="login" method="POST">
        <div class="logo">
            <a href="../../Pages/view/MainPage.php">
                <img src="../../Public/image/looo.png" alt="Logo" width="160" height="90">
            </a>
        </div>
            <div id="email">
                <label for="emaill">ایمیل</label><br />
                <input type="text" id="emailuser" name="email" placeholder="HaniyehHeydari@gmail.com" />
                <span class="error-message"><?= $errors['email'] ?? '' ?></span>
            </div>
            <div id="password">
                <label for="passwordd">رمز عبور</label>
                <input type="password" id="pass" name="password" placeholder="********" />
                <span class="error-message"><?= $errors['password'] ?? '' ?></span>
            </div>
            <button type="submit" name="submit" id="open">ورود</button>
            <div class="accont">
                <p id="sabt">
                    حساب کاربری ندارید؟<a href="register.php" id="sabtnam"> ثبت نام</a>
                </p>
            </div>
        </form>
    </div>


</body>

</html>