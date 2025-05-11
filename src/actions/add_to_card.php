<?php
session_start();
require_once '../config/connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || !isset($_POST['product_id'])) {
    echo json_encode(['success' => false]);
    exit();
}

$userId = $_SESSION['user']['id'];
$productId = $_POST['product_id'];

// Проверяем, есть ли уже такой товар в корзине
$checkStmt = $connect->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
$checkStmt->bind_param("ii", $userId, $productId);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows > 0) {
    // Если товар уже есть, увеличиваем количество
    $updateStmt = $connect->prepare("UPDATE cart SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?");
    $updateStmt->bind_param("ii", $userId, $productId);
    $updateStmt->execute();
    $success = $updateStmt->affected_rows > 0;
    $updateStmt->close();
} else {
    // Если товара нет, добавляем новый
    $insertStmt = $connect->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
    $insertStmt->bind_param("ii", $userId, $productId);
    $insertStmt->execute();
    $success = $insertStmt->affected_rows > 0;
    $insertStmt->close();
}

$checkStmt->close();
echo json_encode(['success' => $success]);
header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();