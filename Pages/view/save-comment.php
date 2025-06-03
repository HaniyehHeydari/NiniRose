<?php
session_start();
include('../../config/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   

    if (!empty($_SESSION['user'])) {
        $user_id = $_SESSION['user']['id'];
        $product_id = intval($_POST['product_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');

        if ($product_id > 0 && !empty($content)) {
            $stmt = $conn->prepare("INSERT INTO comments (user_id, product_id, content) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $user_id, $product_id, $content);
            $stmt->execute();
            $stmt->close();
        }

        header("Location: ../view/product-detail.php?id=" . $product_id);
        exit;
    }
}

header("Location: ../view/MainPage.php");
exit;
