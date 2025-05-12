<?php
require_once '../../src/config/connect.php';
session_start();
if ((!$_SESSION['user']['access'] ?? '0') == "1") {
    header("Location: ../index.php"); // или куда-то еще
    exit;
}
// Получаем все подборки из базы данных
$sql = "SELECT id, title, description, image_path, alt_text FROM collections ORDER BY title ASC";
$result = mysqli_query($connect, $sql);

// Проверяем, был ли запрос успешным
if (!$result) {
    die("Ошибка выполнения запроса: " . mysqli_error($connect));
}

// Сохраняем все подборки в массив (удобнее для проверки на пустоту)
$collections = [];
if (mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        $collections[] = $row;
    }
}

// Освобождаем память от результата запроса (если он был)
if ($result) {
    mysqli_free_result($result);
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление подборками</title>
    <link rel="stylesheet" href="style.css"> <!-- Подключаем стили -->
    <style>
        /* Дополнительные стили для таблицы (можно вынести в style.css) */
        body {
            font-family: sans-serif;
            margin: 20px;
        }
        h1 {
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            vertical-align: middle; /* Выравнивание по вертикали */
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        img {
            max-width: 100px;
            height: auto;
            display: block; /* Убирает лишний отступ под картинкой */
        }
        .actions a {
            display: inline-block; /* Чтобы margin работал */
            margin-right: 10px;
            margin-bottom: 5px; /* Отступ снизу для переноса строк */
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 3px;
        }
        .actions a.edit {
             background-color: #ffc107;
             color: #333;
        }
        .actions a.edit:hover {
            background-color: #e0a800;
        }
        .actions a.delete {
            background-color: #dc3545;
            color: white;
        }
         .actions a.delete:hover {
             background-color: #c82333;
         }
         .add-link {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 15px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 16px;
        }
        .add-link:hover {
            background-color: #218838;
        }
        .no-collections {
            padding: 15px;
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            border-radius: 4px;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<h1>Управление подборками</h1>

<a href="add_collection.php" class="add-link">Добавить новую подборку</a>

<?php if (empty($collections)): ?>
    <p class="no-collections">Подборок пока нет.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Изображение</th>
                <th>Название</th>
                <th>Описание (кратко)</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($collections as $collection): ?>
                <tr>
                    <td><?php echo htmlspecialchars($collection['id']); ?></td>
                    <td>
                        <?php if (!empty($collection['image_path'])): ?>
                            <img src="../../<?php echo htmlspecialchars($collection['image_path']); ?>"
                                 alt="<?php echo htmlspecialchars($collection['alt_text'] ?? $collection['title']); ?>">
                        <?php else: ?>
                            Нет изображения
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($collection['title']); ?></td>
                    <td>
                        <?php
                        $description = $collection['description'] ?? '';
                        echo htmlspecialchars(mb_substr($description, 0, 100));
                        if (mb_strlen($description) > 100) {
                            echo '...';
                        }
                        ?>
                    </td>
                    <td class="actions">
                        <a href="src/edit_collection.php?id=<?php echo $collection['id']; ?>" class="edit">Редактировать</a>
                        <a href="delete_collection.php?id=<?php echo $collection['id']; ?>"
                           class="delete"
                           onclick="return confirm('Вы уверены, что хотите удалить подборку \'<?php echo htmlspecialchars(addslashes($collection['title'])); ?>\'?');">Удалить</a>
                           <!-- addslashes нужен внутри JS confirm для корректной обработки кавычек в названии -->
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

</body>
</html>

<?php
// Закрываем соединение с базой данных
mysqli_close($connect);
?>