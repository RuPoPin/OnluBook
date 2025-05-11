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

// Проверяем, принадлежит ли товар текущему пользователю
$stmt = $connect->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $itemId, $userId);
$stmt->execute();

echo json_encode(['success' => $stmt->affected_rows > 0]);
$stmt->close();
?>