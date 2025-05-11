<?php
// session_start(); // Раскомментируйте, если нужна проверка авторизации и здесь
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header('HTTP/1.1 403 Forbidden');
//     echo json_encode(['error' => 'Доступ запрещен.']);
//     exit;
// }

header('Content-Type: application/json');

// include('../../src/config/connect.php'); // Если у вас общий файл для подключения
$mysqli = new mysqli('localhost', 'root', '', 'users'); // Или настройте подключение здесь
if ($mysqli->connect_error) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => "Ошибка подключения к БД: " . $mysqli->connect_error, 'books' => []]);
    exit;
}
$mysqli->set_charset("utf8mb4");

$promotion_id = isset($_GET['promo_id']) ? (int)$_GET['promo_id'] : 0;
$search_term = isset($_GET['search_term']) ? $mysqli->real_escape_string(trim($_GET['search_term'])) : '';

if ($promotion_id <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Некорректный ID акции.', 'books' => []]);
    exit;
}

$books = [];
$sql = "SELECT id, name, autor FROM Product
        WHERE id NOT IN (SELECT product_id FROM promotion_products WHERE promotion_id = ?)";

$params = [$promotion_id];
$types = "i";

if (!empty($search_term)) {
    $sql .= " AND (name LIKE ? OR autor LIKE ?)";
    $like_search_term = "%" . $search_term . "%";
    $params[] = $like_search_term;
    $params[] = $like_search_term;
    $types .= "ss";
}
$sql .= " ORDER BY name ASC";

$stmt = $mysqli->prepare($sql);

if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка подготовки SQL запроса: ' . $mysqli->error, 'books' => []]);
    $mysqli->close();
    exit;
}

$mysqli->close();
echo json_encode(['books' => $books]);
?>