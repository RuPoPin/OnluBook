<?php
session_start();
require_once '../config/connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || !isset($_POST['item_id'])) {
    echo json_encode(['success' => false]);
    exit();
}

$userId = $_SESSION['user']['id'];
$itemId = $_POST['item_id'];
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

// Проверяем, принадлежит ли товар текущему пользователю
$checkStmt = $connect->prepare("SELECT * FROM cart WHERE id = ? AND user_id = ?");
$checkStmt->bind_param("ii", $itemId, $userId);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows > 0) {
    $updateStmt = $connect->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
    $updateStmt->bind_param("iii", $quantity, $itemId, $userId);
    $updateStmt->execute();
    $success = $updateStmt->affected_rows > 0;
    $updateStmt->close();
} else {
    $success = false;
}

$checkStmt->close();
echo json_encode(['success' => $success]);
?>