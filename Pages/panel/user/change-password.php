<?php
session_start();
include('../../../config/db.php');

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}


$user_id = $_SESSION['user']['id'];
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['currentPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    $sql = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($storedHash);
    $stmt->fetch();
    $stmt->close();

    if (!password_verify($currentPassword, $storedHash)) {
        $errors['currentPassword'] = "رمز عبور فعلی اشتباه است.";
    }

    if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $newPassword)) {
        $errors['newPassword'] = "رمز عبور باید شامل حداقل ۸ کاراکتر، حروف بزرگ و کوچک، عدد و یک کاراکتر خاص باشد.";
    }

    if ($newPassword !== $confirmPassword) {
        $errors['confirmPassword'] = "رمز عبور جدید با تکرار آن مطابقت ندارد.";
    }

    if (empty($errors)) {
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateSql = "UPDATE users SET password = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("si", $newPasswordHash, $user_id);

        if ($updateStmt->execute()) {
            $success = "رمز عبور با موفقیت تغییر یافت.";
        } else {
            $errors['general'] = "خطا در به‌روزرسانی رمز عبور.";
        }

        $updateStmt->close();
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تغییر رمز عبور</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
<div class="container mt-5">
    <div class="row justify-content-center align-items-stretch">
        <!-- فرم تغییر رمز عبور -->
        <div class="col-12 col-md-8 col-lg-6 mt-4">
            <div class="card p-5 shadow">
                <form method="POST" action="">
                    <div class="mb-4">
                        <label for="currentPassword" class="form-label">رمز عبور فعلی:</label>
                        <div class="input-group">
                            <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control shadow-none" style="border-color: #9FACB9;" id="currentPassword" name="currentPassword" required>
                        </div>
                        <?php if (isset($errors['currentPassword'])): ?>
                            <div class="text-danger mt-1"><?php echo htmlspecialchars($errors['currentPassword']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label for="newPassword" class="form-label">رمز عبور جدید:</label>
                        <div class="input-group">
                            <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control shadow-none" style="border-color: #9FACB9;" id="newPassword" name="newPassword" required>
                        </div>
                        <?php if (isset($errors['newPassword'])): ?>
                            <div class="text-danger mt-1"><?php echo htmlspecialchars($errors['newPassword']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-5">
                        <label for="confirmPassword" class="form-label">تکرار رمز عبور جدید:</label>
                        <div class="input-group">
                            <span class="input-group-text" style="border-color: #9FACB9;"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control shadow-none" style="border-color: #9FACB9;" id="confirmPassword" name="confirmPassword" required>
                        </div>
                        <?php if (isset($errors['confirmPassword'])): ?>
                            <div class="text-danger mt-1"><?php echo htmlspecialchars($errors['confirmPassword']); ?></div>
                        <?php endif; ?>
                    </div>

                    <?php if (isset($errors['general'])): ?>
                        <div class="text-danger text-center mb-2"><?php echo htmlspecialchars($errors['general']); ?></div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-center gap-5">
                        <button type="submit" class="btn btn-success" style="width: 45%;">تغییر رمز عبور</button>
                        <a href="../../view/MainPage.php" class="btn btn-secondary" style="width: 45%;">بازگشت</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- شرایط رمز -->
        <div class="col-12 col-md-4 mb-4 mt-5">
            <div class="border-start p-3">
                <h5 class="mb-3">رمز عبور باید شامل موارد زیر باشد:</h5>
                <ul>
                    <li>حداقل 8 کاراکتر</li>
                    <li>عدد (0-9)</li>
                    <li>حروف بزرگ و کوچک</li>
                    <li>یک کاراکتر خاص (@ ! % * & ...)</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert موفقیت -->
<?php if (!empty($success)): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'موفقیت!',
        text: '<?php echo $success; ?>',
        confirmButtonText: 'باشه'
    }).then(() => {
        window.location.href = "../../view/MainPage.php";
    });
</script>
<?php endif; ?>

</body>
</html>
