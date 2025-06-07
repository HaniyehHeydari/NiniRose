<?php
session_start();
include('../../../config/db.php');

if (!isset($_SESSION['user'])) die("دسترسی غیرمجاز");

$comment_id = $_GET['id'] ?? null;
if (!$comment_id) die("شناسه نامعتبر است.");

$stmt = $conn->prepare("SELECT c.*, p.name AS product_name, u.username AS username
                        FROM comments c
                        JOIN products p ON c.product_id = p.id
                        JOIN users u ON c.user_id = u.id
                        WHERE c.id = ?");
$stmt->bind_param("i", $comment_id);
$stmt->execute();
$comment = $stmt->get_result()->fetch_assoc();

if (!$comment) die("کامنت یافت نشد.");

// دریافت پاسخ‌ها
$stmt = $conn->prepare("SELECT c.*, u.username 
                        FROM comments c 
                        JOIN users u ON c.user_id = u.id 
                        WHERE c.parent_id = ?
                        ORDER BY c.created_at ASC");
$stmt->bind_param("i", $comment_id);
$stmt->execute();
$replies = $stmt->get_result();

// پاسخ به کامنت
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['user']['role'] === 'super_admin') {
    $content = trim($_POST['reply']);
    if ($content) {
        $product_id = $comment['product_id'];
        $user_id = $_SESSION['user']['id'];
        $stmt = $conn->prepare("INSERT INTO comments (product_id, user_id, content, parent_id, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("iisi", $product_id, $user_id, $content, $comment_id);
        $stmt->execute();
        $_SESSION['success_message'] = "پاسخ با موفقیت ارسال شد.";
        header("Location: comment-details.php?id=$comment_id");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>جزئیات نظر</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="p-4">

    <h4>جزئیات نظر</h4>
    <div class="card mb-4">
        <div class="card-header bg-danger text-white"><?= htmlspecialchars($comment['product_name']) ?> - توسط <?= htmlspecialchars($comment['username']) ?></div>
        <div class="card-body"><?= nl2br(htmlspecialchars($comment['content'])) ?></div>
        <div class="card-footer text-muted"><?= $comment['created_at'] ?></div>
    </div>

    <h5>پاسخ‌ها</h5>
    <?php if ($replies->num_rows > 0): ?>
        <?php while ($reply = $replies->fetch_assoc()): ?>
            <div class="border rounded p-3 mb-2">
                <strong><?= htmlspecialchars($reply['username']) ?>:</strong><br>
                <?= nl2br(htmlspecialchars($reply['content'])) ?><br>
                <small class="text-muted"><?= $reply['created_at'] ?></small>
            </div>
        <?php endwhile ?>
    <?php else: ?>
        <div class="alert alert-info">هیچ پاسخی ثبت نشده است.</div>
    <?php endif ?>

    <?php if ($_SESSION['user']['role'] === 'super_admin'): ?>
        <h5 class="mt-4">ارسال پاسخ</h5>
        <form method="POST">
            <textarea name="reply" class="form-control mb-2" rows="3" required></textarea>
            <button class="btn btn-success">ارسال پاسخ</button>
            <a href="manage-comments.php" class="btn btn-secondary">بازگشت</a>
        </form>
    <?php endif; ?>

    <script>
        <?php if (isset($_SESSION['success_message'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'موفقیت',
                text: '<?= $_SESSION['success_message']; ?>',
                timer: 3000,
                showConfirmButton: false
            });
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
    </script>
</body>

</html>