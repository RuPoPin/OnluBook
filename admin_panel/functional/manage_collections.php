<?php
require_once '../../src/config/connect.php';
session_start();
if ((!$_SESSION['user']['access'] ?? '0') == "1") {
    header("Location: ../index.php");
    exit;
}
$sql = "SELECT id, title, description, image_path, alt_text FROM collections ORDER BY title ASC";
$result = mysqli_query($connect, $sql);

if (!$result) {
    die("Ошибка выполнения запроса: " . mysqli_error($connect));
}

$collections = [];
if (mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        $collections[] = $row;
    }
}

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
    <link rel="stylesheet" href="style.css?<? echo time()?>"> 
    <style>
        body {
            font-family: sans-serif;
            margin: 20px;
        }
         h1 {
        text-align: center;
        color: #376B44; 
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        border: 1px solid #a8c0af; 
    }
    th, td {
        border: 1px solid #d8e2dc; 
        padding: 10px;
        text-align: left;
        vertical-align: middle;
    }
    th {
        background-color: #cddad4;
        font-weight: bold;
        color: #2a5235; 
    }
    tr:nth-child(even) {
        background-color: #f4f7f5;
    }
    tr:hover {
        background-color: #e1eae5;
    }
    img {
        max-width: 100px;
        height: auto;
        display: block;
        border-radius: 3px;
    }
    .actions a {
        display: inline-block;
        margin-right: 10px;
        margin-bottom: 5px;
        text-decoration: none;
        padding: 5px 10px;
        border-radius: 3px;
        color: white;
        transition: background-color 0.2s ease;
    }
    .actions a.edit {
        background-color: #5a9e6b;
    }
    .actions a.edit:hover {
        background-color: #4a8359; 
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
        background-color: #376B44;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        font-size: 16px;
        transition: background-color 0.2s ease;
    }
    .add-link:hover {
        background-color: #2a5235;
    }
    .no-collections {
        padding: 15px;
        background-color: #e1f0e6; 
        border: 1px solid #c3e0cc;
        color: #2a5235;
        border-radius: 4px;
        margin-top: 20px;
    }
    </style>
</head>
<body>

<h1>Управление подборками</h1>

<a href="src/add_collection.php" class="add-link">Добавить новую подборку</a>

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
                        <a href="src/delete_collection.php?id=<?php echo $collection['id']; ?>"
                           class="delete"
                           onclick="return confirm('Вы уверены, что хотите удалить подборку \'<?php echo htmlspecialchars(addslashes($collection['title'])); ?>\'?');">Удалить</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

</body>
</html>

<?php
mysqli_close($connect);
?>