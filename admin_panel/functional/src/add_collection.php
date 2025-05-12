<?php
session_start();

if (!isset($_SESSION['user']) || ($_SESSION['user']['access'] ?? 0) != 1) {
    header("Location: ../../../index.php");
    exit;
}

require_once '../../../src/config/connect.php';

$upload_dir = '../../../images/more_book/';
$db_image_path_prefix_collection = 'images/more_book/';
$db_image_path_prefix_product = 'images/book/';
$allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$max_file_size = 10 * 1024 * 1024;

$errors = [];
$title = '';
$description = '';
$link_url = '';
$alt_text = '';
$product_images = [];

$sql_images = "SELECT DISTINCT image FROM Product WHERE image IS NOT NULL AND image != '' ORDER BY image ASC";
$result_images = mysqli_query($connect, $sql_images);
if ($result_images) {
    while ($row_image = mysqli_fetch_assoc($result_images)) {
        $image_file_path_relative_to_script = '../../../' . $row_image['image'];
        if (!empty($row_image['image']) && file_exists($image_file_path_relative_to_script)) {
            $product_images[] = $row_image['image'];
        }
    }
    mysqli_free_result($result_images);
} else {
    error_log("Ошибка получения списка изображений продуктов: " . mysqli_error($connect));
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $link_url = isset($_POST['link_url']) && trim($_POST['link_url']) !== '' ? trim($_POST['link_url']) : null;
    $alt_text = isset($_POST['alt_text']) && trim($_POST['alt_text']) !== '' ? trim($_POST['alt_text']) : null;
    $selected_existing_path = isset($_POST['existing_image_path']) ? trim($_POST['existing_image_path']) : '';

    if (empty($title)) {
        $errors[] = "Название подборки обязательно для заполнения.";
    }

    $image_to_save_in_db = null;

    if (isset($_FILES['new_image']) && $_FILES['new_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['new_image']['tmp_name'];
        $file_size = $_FILES['new_image']['size'];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $file_mime_type = finfo_file($finfo, $file_tmp_path);
        finfo_close($finfo);

        $file_name_parts = explode('.', $_FILES['new_image']['name']);
        $file_extension = strtolower(end($file_name_parts));

        if ($file_size > $max_file_size) {
            $errors[] = "Ошибка: Файл слишком большой (максимум " . ($max_file_size / 1024 / 1024) . " МБ).";
        } elseif (!in_array($file_mime_type, $allowed_mime_types)) {
            $errors[] = "Ошибка: Недопустимый тип файла ($file_mime_type). Разрешены: " . implode(', ', $allowed_mime_types);
        } else {
            $unique_filename = 'collection_' . uniqid('', true) . '.' . $file_extension;
            $destination_path = $upload_dir . $unique_filename;

            if (move_uploaded_file($file_tmp_path, $destination_path)) {
                $image_to_save_in_db = $db_image_path_prefix_collection . $unique_filename;
            } else {
                $errors[] = "Ошибка при загрузке файла на сервер. Проверьте права на запись в папку: " . realpath($upload_dir);
            }
        }
    } elseif (isset($_FILES['new_image']) && $_FILES['new_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors[] = "Произошла ошибка при загрузке файла. Код ошибки: " . $_FILES['new_image']['error'];
    }

    if (empty($errors) && $image_to_save_in_db === null && !empty($selected_existing_path)) {
        if (in_array($selected_existing_path, $product_images)) {
             $image_to_save_in_db = $selected_existing_path;
         } else {
             $errors[] = "Выбран некорректный существующий путь изображения.";
         }
    }

    if (empty($errors) && empty($image_to_save_in_db)) {
         $errors[] = "Необходимо загрузить новое изображение или выбрать существующее.";
    }

    if (empty($errors)) {
        $sql = "INSERT INTO collections (title, description, image_path, link_url, alt_text)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($connect, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssss",
                $title,
                $description,
                $image_to_save_in_db,
                $link_url,
                $alt_text
            );

            if (mysqli_stmt_execute($stmt)) {
                $new_collection_id = mysqli_insert_id($connect);
                mysqli_stmt_close($stmt);
                mysqli_close($connect);
                header("Location: ../manage_collections.php?status=added&id=" . $new_collection_id);
                exit;
            } else {
                $errors[] = "Ошибка при добавлении данных в БД: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors[] = "Ошибка подготовки запроса к БД: " . mysqli_error($connect);
        }
    }
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить новую подборку</title>
    <link rel="stylesheet" href="../../style.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap');

        body {
            font-family: 'Nunito', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
            color: #343a40;
            line-height: 1.6;
        }

        .form-container {
            max-width: 750px;
            margin: 40px auto;
            padding: 30px 40px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
        }

        h1 {
            text-align: center;
            color: #376B44;
            margin-bottom: 30px;
            font-weight: 700;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
            font-size: 0.95em;
        }

        input[type="text"],
        textarea,
        input[type="url"],
        select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 1em;
            font-family: inherit;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        input[type="text"]:focus,
        textarea:focus,
        input[type="url"]:focus,
        select:focus {
            border-color: #6b9f79;
            outline: none;
            box-shadow: 0 0 0 3px rgba(55, 107, 68, 0.15);
        }

        textarea {
            min-height: 140px;
            resize: vertical;
        }

        input[type="file"] {
            padding: 8px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 0.95em;
            cursor: pointer;
            width: 100%;
            box-sizing: border-box;
        }
         input[type="file"]::file-selector-button {
            padding: 8px 12px;
            margin-right: 10px;
            background-color: #e9ecef;
            color: #495057;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
         input[type="file"]::file-selector-button:hover {
            background-color: #dee2e6;
        }

        button[type="submit"] {
            display: inline-block;
            padding: 12px 25px;
            background-color: #376B44;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.05em;
            font-weight: 600;
            transition: background-color 0.2s ease, transform 0.1s ease;
        }
        button[type="submit"]:hover {
            background-color: #2a5235;
            transform: translateY(-1px);
        }
         button[type="submit"]:active {
             transform: translateY(0);
         }

        #existing-image-preview img {
            max-width: 180px;
            height: auto;
            margin-top: 10px;
            display: block;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background-color: #f8f9fa;
            padding: 5px;
        }

        .error-messages {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 25px;
            font-size: 0.95em;
        }
        .error-messages strong {
            font-weight: 700;
        }
        .error-messages ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
        }
        .error-messages li {
            margin-bottom: 5px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 25px;
            color: #376B44;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }
        .back-link:hover {
            color: #2a5235;
            text-decoration: underline;
        }

        small {
            color: #6c757d;
            font-size: 0.85em;
            display: block;
            margin-top: 5px;
        }

        hr {
            margin: 30px 0;
            border: none;
            border-top: 1px solid #e9ecef;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h1>Добавить новую подборку</h1>

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

    <form action="add_collection.php" method="post" enctype="multipart/form-data">

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

        <hr>

        <div class="form-group">
            <label for="new_image">Загрузить НОВОЕ изображение:</label>
            <input type="file" id="new_image" name="new_image" accept=".jpg, .jpeg, .png, .gif, .webp">
            <small>Макс. размер: <?php echo ($max_file_size / 1024 / 1024); ?> МБ. Типы: JPG, PNG, GIF, WEBP.</small>
        </div>

         <div class="form-group">
             <label for="existing_image">ИЛИ выбрать существующее изображение из каталога:</label>
             <?php if (!empty($product_images)): ?>
                 <select name="existing_image_path" id="existing_image">
                     <option value="">-- Не выбирать существующее --</option>
                     <?php foreach ($product_images as $img_path): ?>
                         <option value="<?php echo htmlspecialchars($img_path); ?>">
                             <?php echo htmlspecialchars(basename($img_path)); ?>
                         </option>
                     <?php endforeach; ?>
                 </select>
                 <div id="existing-image-preview" style="margin-top: 10px;">
                 </div>
             <?php else: ?>
                 <p><small>Не найдено существующих изображений продуктов для выбора.</small></p>
             <?php endif; ?>
         </div>

        <hr>

        <button type="submit">Добавить подборку</button>
    </form>
</div>

<script>
    const selectElement = document.getElementById('existing_image');
    if (selectElement) {
        selectElement.addEventListener('change', function() {
            const previewContainer = document.getElementById('existing-image-preview');
            const selectedPath = this.value;
            previewContainer.innerHTML = '';

            if (selectedPath) {
                const img = document.createElement('img');
                img.src = '../../../' + selectedPath;
                img.alt = 'Предпросмотр выбранного изображения';
                img.style.maxWidth = '180px';
                img.style.height = 'auto';
                img.style.border = '1px solid #e9ecef';
                img.style.borderRadius = '8px';
                img.style.backgroundColor = '#f8f9fa';
                img.style.padding = '5px';
                img.onerror = function() {
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
if (isset($connect) && $connect) {
    mysqli_close($connect);
}
?>