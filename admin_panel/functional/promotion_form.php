<?php
session_start();
if ((!$_SESSION['user']['access'] ?? '0') == "1") {
    header("Location: ../index.php"); // или куда-то еще
    exit;
}

// Подключение к базе данных (предполагается, что connect.php находится на уровень выше в src/config/)
// Если ваш connect.php находится в другом месте, измените путь
$config_path = __DIR__ . '../../src/config/connect.php';
if (file_exists($config_path)) {
    include($config_path); // $mysqli будет доступен из этого файла
} else {
    // Если connect.php не найден, создаем подключение здесь для примера
    // В реальном проекте лучше убедиться, что connect.php существует и правильно подключается
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'users'; // Убедитесь, что это правильное db_nameимя вашей БД
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $);
    if ($mysqli->connect_error) {
        die("Ошибка подключения к БД (fallback): " . $mysqli->connect_error);
    }
}
$mysqli->set_charset("utf8mb4");

// Инициализация переменных для формы
$promotion = [
    'id' => '',
    'title' => '',
    'image_src' => '',
    'image_alt' => '',
    'image_srcset' => '',
    'time_range' => '',
    'location' => '',
    'description' => '',
    'active' => 1 // По умолчанию новая акция активна
];
$form_action_type = 'add_promotion'; // Действие для обработчика
$page_title = 'Добавить новую акцию';
$submit_button_text = 'Добавить акцию';
$current_image_path = '';

// Проверка, если это режим редактирования
if (isset($_GET['edit_id']) && filter_var($_GET['edit_id'], FILTER_VALIDATE_INT)) {
    $edit_id = (int)$_GET['edit_id'];
    $sql = "SELECT id, title, image_src, image_alt, image_srcset, time_range, location, description, active FROM promotions WHERE id = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            
            $promotion = $result->fetch_assoc();
            $form_action_type = 'edit_promotion';
            $page_title = 'Редактировать акцию: ' . htmlspecialchars($promotion['title']);
            $submit_button_text = 'Сохранить изменения';
            if (!empty($promotion['image_src'])) {
                // Путь к изображению относительно корня сайта, если изображения хранятся в /images/promotions/
                // Если структура другая, скорректируйте путь
                $current_image_path = '../../'. htmlspecialchars($promotion['image_src']);
            }
        } else {
            $_SESSION['message'] = "Акция с ID {$edit_id} не найдена.";
            $_SESSION['message_type'] = "error";
            header("Location: promotions_manage.php");
            exit;
        }
        $stmt->close();
    } else {
        // Обработка ошибки подготовки запроса
        $_SESSION['message'] = "Ошибка подготовки запроса к БД: " . $mysqli->error;
        $_SESSION['message_type'] = "error";
        // Можно не перенаправлять, а просто показать ошибку на этой же странице или записать в лог
        // header("Location: promotions_manage.php");
        // exit;
        die("Ошибка подготовки запроса: " . $mysqli->error); // Для отладки
    }
}

// $mysqli->close(); // Закрывать соединение лучше в конце скрипта или после всех запросов
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="style.css"> <!-- Предполагается, что style.css в той же папке -->
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f4f4f4; color: #333; }
        header { background-color: #333; color: white; padding: 1em 0; text-align: center; }
        header h1 { margin: 0; }
        header a { color: #ffc107; text-decoration: none; }
        header a:hover { text-decoration: underline; }
        main { max-width: 800px; margin: 20px auto; padding: 20px; background-color: white; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; }
        .form-group input[type="text"],
        .form-group textarea,
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .form-group input[type="file"] { padding: 3px; }
        .form-group textarea { min-height: 120px; resize: vertical; }
        .form-group input[type="checkbox"] { width: auto; margin-right: 5px; vertical-align: middle; }
        .current-image-container { margin-top: 10px; }
        .current-image { max-width: 200px; max-height: 150px; display: block; border: 1px solid #ddd; padding: 3px; border-radius: 4px; }
        .button, button[type="submit"] {
            display: inline-block;
            padding: 10px 20px;
            background-color: #5cb85c;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
        }
        .button:hover, button[type="submit"]:hover { background-color: #4cae4c; }
        .error-messages { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .error-messages ul { margin: 0; padding-left: 20px; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message { padding: 10px; border-radius: 4px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <header>
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
        <p><a href="promotions_manage.php">Назад к списку акций</a></p>
    </header>
    <main>
        <?php if (isset($_SESSION['message'])): ?>
            <p class="message <?php echo isset($_SESSION['message_type']) ? htmlspecialchars($_SESSION['message_type']) : ''; ?>">
                <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
            </p>
        <?php endif; ?>

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

        <form action="src/process_promotion.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?php echo $form_action_type; ?>">
            <?php if (!empty($promotion['id'])): ?>
                <input type="hidden" name="promotion_id" value="<?php echo htmlspecialchars($promotion['id']); ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="title">Название акции:</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($promotion['title']); ?>" required>
            </div>

            <div class="form-group">
                <label for="image_src">Изображение акции (оставьте пустым, чтобы не менять текущее):</label>
                <input type="file" id="image_src" name="image_src" accept="image/png, image/jpeg, image/jpg, image/gif, image/webp">
                <?php if ($form_action_type == 'edit_promotion' && !empty($promotion['image_src'])): ?>
                    <div class="current-image-container">
                        <p>Текущее изображение:</p>
                        <img src="<?php echo $current_image_path; ?>" alt="Текущее изображение <?php echo htmlspecialchars($promotion['image_alt']); ?>" class="current-image">
                        <input type="hidden" name="current_image_src" value="<?php echo htmlspecialchars($promotion['image_src']); ?>">
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="image_alt">Alt текст для изображения (опционально):</label>
                <input type="text" id="image_alt" name="image_alt" value="<?php echo htmlspecialchars($promotion['image_alt']); ?>" placeholder="Краткое описание изображения">
            </div>

            <div class="form-group">
                <label for="image_srcset">Srcset для изображения (опционально, для адаптивных изображений):</label>
                <input type="text" id="image_srcset" name="image_srcset" value="<?php echo htmlspecialchars($promotion['image_srcset']); ?>" placeholder="например, image-small.jpg 500w, image-large.jpg 1000w">
            </div>

            <div class="form-group">
                <label for="time_range">Сроки проведения:</label>
                <input type="text" id="time_range" name="time_range" value="<?php echo htmlspecialchars($promotion['time_range']); ?>" required placeholder="Например, 01.07.2024 - 31.07.2024">
            </div>

            <div class="form-group">
                <label for="location">Место проведения:</label>
                <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($promotion['location']); ?>" required placeholder="Например, ИНТЕРНЕТ-МАГАЗИН">
            </div>

            <div class="form-group">
                <label for="description">Полное описание акции:</label>
                <textarea id="description" name="description" required><?php echo htmlspecialchars($promotion['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="active">
                    <input type="checkbox" id="active" name="active" value="1" <?php echo ($promotion['active'] == 1) ? 'checked' : ''; ?>>
                    Акция активна
                </label>
            </div>

            <button type="submit" class="button"><?php echo htmlspecialchars($submit_button_text); ?></button>
        </form>
    </main>
</body>
</html>
<?php
$mysqli->close(); // Закрываем соединение с БД
?>