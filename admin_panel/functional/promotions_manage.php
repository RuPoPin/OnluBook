<?php
session_start();
if ((!$_SESSION['user']['access'] ?? '0') == "1") {
    header("Location: ../index.php"); // или куда-то еще
    exit;
}
include('../../src/config/connect.php');
$mysqli = new mysqli('localhost', 'root', '', 'users');
if ($mysqli->connect_error) {
    die("Ошибка подключения: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");

$promotions = [];
$sql = "SELECT id, title, time_range, location, active FROM promotions ORDER BY created_at DESC";
$result = $mysqli->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $promotions[] = $row;
    }
}
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление акциями</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .action-links a { margin-right: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .add-button {
            display: inline-block;
            padding: 10px 15px;
            background-color: #5cb85c;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .add-button:hover { background-color: #4cae4c; }
    </style>
</head>
<body>
    <header>
        <h1>Управление акциями</h1>
        <p><a href="../admin_panel.php">Назад в админ-панель</a></p>
    </header>
    <main>
        <a href="promotion_form.php" class="add-button">Добавить новую акцию</a>
        
        <?php if (isset($_SESSION['message'])): ?>
            <p class="message <?php echo isset($_SESSION['message_type']) ? $_SESSION['message_type'] : ''; ?>">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
            </p>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Сроки</th>
                    <th>Место</th>
                    <th>Активна</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($promotions)): ?>
                    <?php foreach ($promotions as $promo): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($promo['id']); ?></td>
                        <td><?php echo htmlspecialchars($promo['title']); ?></td>
                        <td><?php echo htmlspecialchars($promo['time_range']); ?></td>
                        <td><?php echo htmlspecialchars($promo['location']); ?></td>
                        <td><?php echo $promo['active'] ? 'Да' : 'Нет'; ?></td>
                        <td class="action-links">
                            <a href="promotion_form.php?edit_id=<?php echo $promo['id']; ?>">Редактировать</a>
                            <a href="promotion_books.php?promo_id=<?php echo $promo['id']; ?>">Книги</a>
                            <a href="functional/src/process_promotion.php?delete_id=<?php echo $promo['id']; ?>" onclick="return confirm('Вы уверены, что хотите удалить эту акцию?');">Удалить</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">Акций пока нет.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</body>
</html>

<?php
session_start();
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header("Location: login.php"); exit;
// }
include('../../src/config/connect.php');
$mysqli = new mysqli('localhost', 'root', '', 'users');
if ($mysqli->connect_error) {
    die("Ошибка подключения: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");

$promotion = [
    'id' => '', 'title' => '', 'image_src' => '', 'image_alt' => '', 
    'image_srcset' => '', 'time_range' => '', 'location' => '', 
    'description' => '', 'active' => 1
];
$form_action = 'add_promotion';
$page_title = 'Добавить новую акцию';
$submit_button_text = 'Добавить акцию';

if (isset($_GET['edit_id']) && filter_var($_GET['edit_id'], FILTER_VALIDATE_INT)) {
    $edit_id = (int)$_GET['edit_id'];
    $sql = "SELECT * FROM promotions WHERE id = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $promotion = $result->fetch_assoc();
            $form_action = 'edit_promotion';
            $page_title = 'Редактировать акцию';
            $submit_button_text = 'Сохранить изменения';
        } else {
            $_SESSION['message'] = "Акция не найдена.";
            $_SESSION['message_type'] = "error";
            header("Location: promotions_manage.php");
            exit;
        }
        $stmt->close();
    }
}
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input[type="text"],
        .form-group textarea { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        .form-group input[type="file"] { padding: 3px; }
        .form-group textarea { min-height: 100px; }
        .form-group input[type="checkbox"] { width: auto; }
        .current-image { max-width: 200px; max-height: 150px; display: block; margin-top: 5px; }
    </style>
</head>
<body>
    <header>
        <h1><?php echo $page_title; ?></h1>
        <p><a href="promotions_manage.php">Назад к списку акций</a></p>
    </header>
    <main>
        <?php if (isset($_SESSION['form_errors'])): ?>
            <div class="error-messages">
                <p>Пожалуйста, исправьте следующие ошибки:</p>
                <ul>
                    <?php foreach ($_SESSION['form_errors'] as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php unset($_SESSION['form_errors']); ?>
        <?php endif; ?>

        <form action="functional/src/process_promotion.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?php echo $form_action; ?>">
            <?php if ($promotion['id']): ?>
                <input type="hidden" name="promotion_id" value="<?php echo htmlspecialchars($promotion['id']); ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="title">Название акции:</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($promotion['title']); ?>" required>
            </div>

            <div class="form-group">
                <label for="image_src">Изображение акции (оставьте пустым, чтобы не менять):</label>
                <input type="file" id="image_src" name="image_src" accept="image/png, image/jpeg, image/jpg, image/gif, image/webp">
                <?php if ($form_action == 'edit_promotion' && !empty($promotion['image_src'])): ?>
                    <p>Текущее изображение: <img src="../images/promotions/<?php echo htmlspecialchars($promotion['image_src']); ?>" alt="Текущее изображение" class="current-image"></p>
                    <input type="hidden" name="current_image_src" value="<?php echo htmlspecialchars($promotion['image_src']); ?>">
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="image_alt">Alt текст для изображения (опционально):</label>
                <input type="text" id="image_alt" name="image_alt" value="<?php echo htmlspecialchars($promotion['image_alt']); ?>">
            </div>
            
            <div class="form-group">
                <label for="image_srcset">Srcset для изображения (опционально):</label>
                <input type="text" id="image_srcset" name="image_srcset" value="<?php echo htmlspecialchars($promotion['image_srcset']); ?>">
            </div>

            <div class="form-group">
                <label for="time_range">Сроки проведения:</label>
                <input type="text" id="time_range" name="time_range" value="<?php echo htmlspecialchars($promotion['time_range']); ?>" required placeholder="Например, 01.01 - 31.01">
            </div>

            <div class="form-group">
                <label for="location">Место проведения:</label>
                <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($promotion['location']); ?>" required placeholder="Например, ИНТЕРНЕТ-МАГАЗИН">
            </div>

            <div class="form-group">
                <label for="description">Описание акции:</label>
                <textarea id="description" name="description" required><?php echo htmlspecialchars($promotion['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="active">
                    <input type="checkbox" id="active" name="active" value="1" <?php echo ($promotion['active'] == 1) ? 'checked' : ''; ?>>
                    Акция активна
                </label>
            </div>

            <button type="submit" class="button"><?php echo $submit_button_text; ?></button>
        </form>
    </main>
</body>
</html>