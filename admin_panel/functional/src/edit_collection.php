<?php
session_start(); // Один раз в начале

// --- Проверка доступа ---
// Перенаправляем, если пользователь НЕ авторизован ИЛИ ЕГО УРОВЕНЬ ДОСТУПА НЕ 1
if (!isset($_SESSION['user']) || ($_SESSION['user']['access'] ?? 0) != 1) {
    header("Location: ../../../index.php"); // На три уровня вверх к index.php
    exit;
}

// --- Подключение к БД ---
// Предполагается, что connect.php определяет переменную $connect
require_once '../../../src/config/connect.php'; // На три уровня вверх к src/config/

// --- Настройки ---
// Путь для ЗАГРУЗКИ новых изображений ПОДБОРОК (относительно ЭТОГО скрипта)
$upload_dir = '../../../images/more_book/';
// Путь для ЗАПИСИ в БД и для URL изображений ПОДБОРОК (относительно КОРНЯ сайта)
$db_image_path_prefix_collection = 'images/more_book/';
// Путь для URL изображений ПРОДУКТОВ (относительно КОРНЯ сайта) - УТОЧНИТЕ ПРИ НЕОБХОДИМОСТИ
$db_image_path_prefix_product = 'images/book/'; // <-- ПРОВЕРЬТЕ ПУТЬ К ИЗОБРАЖЕНИЯМ ПРОДУКТОВ
$allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']; // Добавил webp
$max_file_size = 10 * 1024 * 1024; // 10 MB

$errors = [];
$collection_id = null;
$title = '';
$description = '';
$link_url = '';
$alt_text = '';
$current_image_path = ''; // Хранит путь к текущему изображению из БД (относительно корня)
$product_images = []; // Массив для хранения путей к изображениям продуктов

// --- Обработка POST-запроса (сохранение изменений) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем данные из формы
    $collection_id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $link_url = isset($_POST['link_url']) && trim($_POST['link_url']) !== '' ? trim($_POST['link_url']) : null; // NULL если пусто
    $alt_text = isset($_POST['alt_text']) && trim($_POST['alt_text']) !== '' ? trim($_POST['alt_text']) : null;  // NULL если пусто
    $current_image_path = isset($_POST['current_image_path']) ? $_POST['current_image_path'] : ''; // Текущий путь из БД
    $selected_existing_path = isset($_POST['existing_image_path']) ? trim($_POST['existing_image_path']) : ''; // Выбранный путь из продуктов

    // Валидация базовых полей
    if (empty($title)) {
        $errors[] = "Название подборки обязательно для заполнения.";
    }
    if ($collection_id === null || $collection_id <= 0) {
         $errors[] = "Неверный ID подборки.";
    }

    $new_image_db_path = $current_image_path; // По умолчанию оставляем старое изображение
    $old_image_to_delete_fs_path = null; // Путь к старому файлу на диске для удаления (только если загружен НОВЫЙ)

    // --- Обработка загрузки НОВОГО файла (Приоритет 1) ---
    if (isset($_FILES['new_image']) && $_FILES['new_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['new_image']['tmp_name'];
        $file_size = $_FILES['new_image']['size'];
        // Используем finfo для более надежного определения MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $file_mime_type = finfo_file($finfo, $file_tmp_path);
        finfo_close($finfo);

        $file_name_parts = explode('.', $_FILES['new_image']['name']);
        $file_extension = strtolower(end($file_name_parts));

        // Валидация файла
        if ($file_size > $max_file_size) {
            $errors[] = "Ошибка: Файл слишком большой (максимум " . ($max_file_size / 1024 / 1024) . " МБ).";
        } elseif (!in_array($file_mime_type, $allowed_mime_types)) {
            $errors[] = "Ошибка: Недопустимый тип файла ($file_mime_type). Разрешены: " . implode(', ', $allowed_mime_types);
        } else {
            // Генерация уникального имени файла
            $unique_filename = 'collection_' . uniqid('', true) . '.' . $file_extension;
            // Полный путь на диске для сохранения НОВОГО файла
            $destination_path = $upload_dir . $unique_filename;

            // Перемещение файла
            if (move_uploaded_file($file_tmp_path, $destination_path)) {
                // Путь для сохранения в БД (относительный к КОРНЮ сайта)
                $new_image_db_path = $db_image_path_prefix_collection . $unique_filename;

                // Помечаем старый файл для удаления ТОЛЬКО ЕСЛИ он был
                // и он отличается от нового пути
                if (!empty($current_image_path) && $current_image_path !== $new_image_db_path) {
                     // Полный путь к СТАРОМУ файлу на диске (относительно скрипта)
                     $old_image_to_delete_fs_path = '../../../' . $current_image_path; // ТРИ уровня вверх!
                 }
            } else {
                $errors[] = "Ошибка при загрузке файла на сервер. Проверьте права на запись в папку: " . realpath($upload_dir);
            }
        }
    } elseif (isset($_FILES['new_image']) && $_FILES['new_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Если была ошибка, но это не "файл не был загружен"
        $errors[] = "Произошла ошибка при загрузке файла. Код ошибки: " . $_FILES['new_image']['error'];
    }
    // --- Обработка ВЫБОРА СУЩЕСТВУЮЩЕГО файла (Приоритет 2, если новый НЕ загружен) ---
    // Сработает только если НОВЫЙ файл НЕ был успешно загружен ($old_image_to_delete_fs_path === null)
    // и если был ВЫБРАН существующий путь, отличный от текущего
    elseif (empty($errors) && $old_image_to_delete_fs_path === null && !empty($selected_existing_path) && $selected_existing_path !== $current_image_path) {
         // Используем выбранный путь из списка продуктов
         // Путь ($selected_existing_path) уже в нужном формате для БД (относительно корня)
        $new_image_db_path = $selected_existing_path;
        // В этом случае старый файл НЕ удаляем
    }

    // --- Обновление данных в БД, если нет ошибок ---
    if (empty($errors)) {

        // Удаляем старый файл с диска, ТОЛЬКО если был загружен НОВЫЙ
         if ($old_image_to_delete_fs_path !== null) {
             // Проверяем существование перед удалением
             if (file_exists($old_image_to_delete_fs_path)) {
                 if (!unlink($old_image_to_delete_fs_path)) {
                     // Логируем ошибку, но не останавливаем процесс
                     error_log("WARNING: Не удалось удалить старый файл: " . $old_image_to_delete_fs_path);
                     $errors[] = "Предупреждение: Не удалось удалить старый файл изображения с сервера."; // Можно показать пользователю
                 }
             } else {
                 // Логируем, если файл для удаления не найден (может быть нормой, если путь в БД некорректный)
                  error_log("NOTICE: Старый файл для удаления не найден: " . $old_image_to_delete_fs_path);
             }
         }

        // --- Обновление записи в БД ---
        // NULL значения для URL и alt текста, если они пустые
        $link_url_for_db = empty($link_url) ? null : $link_url;
        $alt_text_for_db = empty($alt_text) ? null : $alt_text;

        $sql = "UPDATE collections SET
                    title = ?,
                    description = ?,
                    image_path = ?,
                    link_url = ?,
                    alt_text = ?
                WHERE id = ?";

        $stmt = mysqli_prepare($connect, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssssi",
                $title,
                $description,
                $new_image_db_path, // Используем актуальный путь (новый, выбранный или старый)
                $link_url_for_db,   // NULL если пусто
                $alt_text_for_db,   // NULL если пусто
                $collection_id
            );

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                mysqli_close($connect); // Закрываем соединение перед редиректом
                // Успешно обновлено, перенаправляем обратно на страницу управления
                header("Location: ../manage_collections.php?status=updated&id=" . $collection_id); // Передаем ID для возможного сообщения
                exit;
            } else {
                $errors[] = "Ошибка при обновлении данных в БД: " . mysqli_stmt_error($stmt);
                // Если обновление не удалось, но новый файл был перемещен/старый удален - это проблема.
                // В идеале - транзакции или более сложная логика отката. Сейчас просто покажем ошибку.
            }
            mysqli_stmt_close($stmt); // Закрываем стейтмент даже при ошибке execute
        } else {
            $errors[] = "Ошибка подготовки запроса к БД: " . mysqli_error($connect);
        }
    }
    // Если были ошибки, скрипт дойдет до вывода HTML и покажет ошибки и форму с введенными данными

} else {
    // --- Обработка GET-запроса (загрузка данных для формы) ---
    if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
        die("Ошибка: Не указан или неверен ID подборки.");
    }
    $collection_id = (int)$_GET['id'];

    // --- Получаем данные самой коллекции ---
    $sql_collection = "SELECT title, description, image_path, link_url, alt_text FROM collections WHERE id = ?";
    $stmt_collection = mysqli_prepare($connect, $sql_collection);

    if ($stmt_collection) {
        mysqli_stmt_bind_param($stmt_collection, "i", $collection_id);
        mysqli_stmt_execute($stmt_collection);
        $result_collection = mysqli_stmt_get_result($stmt_collection);
        $collection = mysqli_fetch_assoc($result_collection);
        mysqli_stmt_close($stmt_collection);

        if ($collection) {
            $title = $collection['title'];
            $description = $collection['description'];
            $current_image_path = $collection['image_path']; // Текущий путь из БД (относительно корня)
            $link_url = $collection['link_url'];
            $alt_text = $collection['alt_text'];
        } else {
            die("Ошибка: Подборка с ID " . $collection_id . " не найдена.");
        }
    } else {
         die("Ошибка подготовки запроса для получения данных коллекции: " . mysqli_error($connect));
    }

    // --- Получаем список существующих изображений ПРОДУКТОВ ---
    $sql_images = "SELECT DISTINCT image FROM Product WHERE image IS NOT NULL AND image != '' ORDER BY image ASC";
    $result_images = mysqli_query($connect, $sql_images);
    if ($result_images) {
        while ($row_image = mysqli_fetch_assoc($result_images)) {
            // Проверяем, существует ли файл физически (относительно скрипта)
            $image_file_path_relative_to_script = '../../../' . $row_image['image']; // ТРИ уровня вверх!
            if (!empty($row_image['image']) && file_exists($image_file_path_relative_to_script)) {
                 // Сохраняем путь как он есть в БД (относительно корня сайта)
                $product_images[] = $row_image['image'];
            }
        }
        mysqli_free_result($result_images);
    } else {
        error_log("Ошибка получения списка изображений продуктов: " . mysqli_error($connect));
        // Не прерываем выполнение, просто список будет пустым
    }
} // Конец обработки GET

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать подборку</title>
    <!-- Путь к CSS должен быть относительным HTML страницы, которая его подключает -->
    <!-- Если edit_collection.php в admin_panel/functional/src/, а style.css в admin_panel/ -->
    <link rel="stylesheet" href="../../style.css"> <!-- На два уровня вверх -->
    <style>
        body { font-family: sans-serif; margin: 20px; background-color: #f4f4f4; }
        .form-container { max-width: 700px; margin: 30px auto; padding: 25px; border: 1px solid #ddd; border-radius: 8px; background-color: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #333; margin-bottom: 25px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; margin-bottom: 6px; font-weight: bold; color: #555; }
        input[type="text"],
        textarea,
        input[type="url"],
        select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 14px; }
        textarea { min-height: 120px; resize: vertical; }
        input[type="file"] { padding: 5px; border: 1px solid #ccc; border-radius: 4px; }
        button[type="submit"] { display: inline-block; padding: 12px 20px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; transition: background-color 0.3s ease; }
        button[type="submit"]:hover { background-color: #0056b3; }
        .current-image img, #existing-image-preview img { max-width: 150px; height: auto; margin-top: 10px; display: block; border: 1px solid #eee; border-radius: 4px;}
        .error-messages { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 12px; border-radius: 4px; margin-bottom: 20px; }
        .error-messages ul { margin: 0; padding-left: 20px; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #007bff; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
        small { color: #777; font-size: 0.9em; }
    </style>
</head>
<body>

<div class="form-container">
    <h1>Редактировать подборку</h1>

    <a href="../manage_collections.php" class="back-link">← Назад к списку подборок</a>

    <?php if (!empty($errors)): ?>
        <div class="error-messages">
            <strong>Обнаружены ошибки:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['status']) && $_GET['status'] === 'updated'): ?>
        <div style="padding: 10px; margin-bottom: 15px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px;">
            Подборка успешно обновлена!
        </div>
    <?php endif; ?>

    <form action="edit_collection.php?id=<?php echo htmlspecialchars($collection_id); ?>" method="post" enctype="multipart/form-data">
        <!-- Передаем ID через скрытое поле, хотя он и в URL -->
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($collection_id); ?>">
        <!-- Скрытое поле с текущим путем изображения (относительно корня) -->
        <input type="hidden" name="current_image_path" value="<?php echo htmlspecialchars($current_image_path); ?>">

        <div class="form-group">
            <label for="title">Название:</label>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
        </div>

        <div class="form-group">
            <label for="description">Описание:</label>
            <textarea id="description" name="description"><?php echo htmlspecialchars($description); ?></textarea>
        </div>

        <div class="form-group">
            <label for="link_url">URL Ссылки (необязательно):</label>
            <input type="url" id="link_url" name="link_url" value="<?php echo htmlspecialchars($link_url ?? ''); ?>" placeholder="https://example.com/collection_link">
        </div>

         <div class="form-group">
            <label for="alt_text">Alt текст для изображения (необязательно, для SEO и доступности):</label>
            <input type="text" id="alt_text" name="alt_text" value="<?php echo htmlspecialchars($alt_text ?? ''); ?>" placeholder="Краткое описание изображения">
        </div>

        <div class="form-group">
            <label>Текущее изображение:</label>
            <?php
            // Проверяем существование файла относительно СКРИПТА
            $current_image_fs_path = !empty($current_image_path) ? ('../../../' . $current_image_path) : '';
            if (!empty($current_image_path) && file_exists($current_image_fs_path)):
            ?>
                <div class="current-image">
                    <!-- Путь в src должен быть относительным КОРНЯ -->
                    <img src="../../../<?php echo htmlspecialchars($current_image_path); ?>" alt="<?php echo htmlspecialchars($alt_text ?: 'Текущее изображение'); ?>">
                    <p><small>Путь: <?php echo htmlspecialchars($current_image_path); ?></small></p>
                </div>
            <?php elseif (!empty($current_image_path)): ?>
                 <p><small>Файл изображения не найден по пути: <?php echo htmlspecialchars($current_image_path); ?></small></p>
            <?php else: ?>
                <p>Изображение не задано.</p>
            <?php endif; ?>
        </div>

        <hr style="margin: 20px 0; border: none; border-top: 1px solid #eee;">

        <div class="form-group">
            <label for="new_image">Загрузить НОВОЕ изображение (заменит текущее):</label>
            <input type="file" id="new_image" name="new_image" accept=".jpg, .jpeg, .png, .gif, .webp">
            <small>Макс. размер: <?php echo ($max_file_size / 1024 / 1024); ?> МБ. Типы: JPG, PNG, GIF, WEBP.</small>
        </div>

         <div class="form-group">
             <label for="existing_image">ИЛИ выбрать существующее изображение из каталога:</label>
             <?php if (!empty($product_images)): ?>
                 <select name="existing_image_path" id="existing_image">
                     <option value="">-- Не выбирать существующее --</option>
                     <?php foreach ($product_images as $img_path): ?>
                         <option value="<?php echo htmlspecialchars($img_path); ?>" <?php echo ($img_path === $current_image_path) ? 'selected' : ''; ?>>
                             <?php echo htmlspecialchars(basename($img_path)); // Показываем только имя файла ?>
                         </option>
                     <?php endforeach; ?>
                 </select>
                 <div id="existing-image-preview" style="margin-top: 10px;">
                     <?php
                     // Показываем превью, если текущее изображение есть в списке продуктов
                     $current_image_fs_path_preview = !empty($current_image_path) ? ('../../../' . $current_image_path) : '';
                     if (!empty($current_image_path) && in_array($current_image_path, $product_images) && file_exists($current_image_fs_path_preview)):
                     ?>
                          <img src="../../../<?php echo htmlspecialchars($current_image_path); ?>" alt="Выбранное изображение">
                      <?php endif; ?>
                 </div>
             <?php else: ?>
                 <p><small>Не найдено существующих изображений продуктов для выбора.</small></p>
             <?php endif; ?>
         </div>

        <hr style="margin: 20px 0; border: none; border-top: 1px solid #eee;">

        <button type="submit">Сохранить изменения</button>
    </form>
</div>

<script>
    // Простое превью при выборе из select
    const selectElement = document.getElementById('existing_image');
    if (selectElement) { // Проверяем, что элемент существует
        selectElement.addEventListener('change', function() {
            const previewContainer = document.getElementById('existing-image-preview');
            const selectedPath = this.value; // Путь относительно корня (как в value)
            previewContainer.innerHTML = ''; // Очищаем предыдущее

            if (selectedPath) {
                const img = document.createElement('img');
                // Путь к изображению должен быть относительным КОРНЯ сайта для src
                // Добавляем '../../../' чтобы получить путь от текущего HTML к корню
                img.src = '../../../' + selectedPath;
                img.alt = 'Предпросмотр выбранного изображения';
                img.style.maxWidth = '150px';
                img.style.height = 'auto';
                img.style.border = '1px solid #ddd';
                img.style.borderRadius = '4px';
                img.onerror = function() {
                    // Если картинка не загрузилась (не найдена)
                    previewContainer.innerHTML = '<small style="color:red;">Не удалось загрузить превью.</small>';
                };
                previewContainer.appendChild(img);
            }
        });
    }
</script>

</body>
</html>

<?php
// Закрываем соединение, если оно еще открыто и существует
if (isset($connect) && $connect) {
    mysqli_close($connect);
}
?>