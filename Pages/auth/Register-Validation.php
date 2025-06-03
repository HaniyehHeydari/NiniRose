<?php
include('../../config/db.php');

$errors = [];
$username = '';
$email = '';
$password = '';
$phone = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $phone = $_POST['phone'] ?? '';

    // اعتبارسنجی ایمیل
    if (empty($email)) {
        $errors['email'] = 'لطفا یک ایمیل وارد کنید';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors['email'] = 'ایمیل وجود دارد';
        }
        $stmt->close();
    }
    
    // اعتبارسنجی نام کاربری
    if (!preg_match('/^(?![0-9])[آ-یa-zA-Z0-9\s‌]{3,}$/u', $username)) {
        $errors['username'] = 'لطفا یک نام کاربری معتبر وارد کنید';
    }

    // اعتبارسنجی فرمت ایمیل
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'لطفا یک ایمیل معتبر وارد کنید';
    }

    // اعتبارسنجی رمز عبور
    if (!preg_match('/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*]).{8,}/', $password)) {
        $errors['password'] = 'رمز عبور باید حداقل ۸ کاراکتر و شامل حروف بزرگ، کوچک، عدد و نماد باشد';
    }

    // اعتبارسنجی شماره تلفن
    if (!preg_match('/^09\d{9}$/', $phone)) {
        $errors['phone'] = 'لطفا شماره تلفن معتبر وارد کنید';
    }

    if (count($errors) === 0) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, email, phone, password) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $username, $email, $phone, $hashed_password);
        $stmt->execute();
        $stmt->close();

        header("Location: ./login.php");
        exit();
    }
}

$conn->close();
