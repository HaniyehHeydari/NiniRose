<?php
include('../../config/db.php');

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (empty($email)) {
        $errors['email'] = 'لطفا ایمیل خود را وارد کنید';
    }
    if (empty($password)) {
        $errors['password'] = 'لطفا رمز عبور خود را وارد کنید';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                // ذخیره اطلاعات کاربر در سشن
                $_SESSION["user"] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'store_id' => $user['store_id']
                ];

                // هدایت به صفحه اصلی
                header("Location: ../view/MainPage.php");
                exit();
            } else {
                $errors['password'] = "رمز عبور اشتباه است";
            }
        } else {
            $errors['email'] = "کاربری با این ایمیل یافت نشد. لطفاً ثبت‌نام کنید.";
        }

        $stmt->close();
    }
}

$conn->close();
