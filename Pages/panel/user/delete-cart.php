<?php
session_start();
include('../../../config/db.php');

if (isset($_POST['cart_id'])) {
    $cart_id = $_POST['cart_id'];

    if ($stmt = $conn->prepare("SELECT * FROM carts WHERE id = ?")) {
        $stmt->bind_param("i", $cart_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            if ($delete_stmt = $conn->prepare("DELETE FROM carts WHERE id = ?")) {
                $delete_stmt->bind_param("i", $cart_id);
                
                if ($delete_stmt->execute() === TRUE) {
                    echo "success";
                    exit();
                } else {
                    echo "خطا در حذف محصول: " . $conn->error;
                }
                $delete_stmt->close();
            } else {
                echo "خطا در اجرای کوئری حذف: " . $conn->error;
            }
        } else {
            echo "محصولی با این شناسه یافت نشد.";
        }

        $stmt->close();
    } else {
        echo "خطا در اجرای کوئری بررسی سبد خرید: " . $conn->error;
    }
} else {
    echo "شناسه محصول مشخص نشده است.";
}

$conn->close();
