<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = new mysqli("localhost", "root", "", "sismony");
if ($conn->connect_error) {
    die("اتصال برقرار نشد");
}
$conn->query("SET NAMES utf8");
?>
