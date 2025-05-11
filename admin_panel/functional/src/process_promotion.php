<?php
session_start();

// ЗАМЕНИТЕ ЭТО НА ВАШ ФАЙЛ КОНФИГУРАЦИИ, ЕСЛИ НУЖНО
// include('../../../src/config/connect.php');
$mysqli = new mysqli('localhost', 'root', '', 'users'); // Пример подключения
if ($mysqli->connect_error) {
    die("Ошибка подключения: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");

// --- КОНФИГУРАЦИЯ ПУТЕЙ ---
// Путь, который будет храниться в БД (относительно корня вашего сайта, для <img src="...">)
// Например, если корень сайта /var/www/html, а изображения в /var/www/html/images/promotions,
// то этот префикс будет "images/promotions/"
$db_image_path_prefix = "images/promotions/";

// Физический путь к папке с изображениями на сервере (относительно текущего скрипта обработчика)
// Если ваш обработчик находится в admin/actions/process_promotions.php,
// а изображения должны быть в images/promotions (на одном уровне с admin),
// то путь будет "../../../images/promotions/"
$physical_target_dir = "../../../images/promotions/"; // УБЕДИТЕСЬ, ЧТО ЭТОТ ПУТЬ ВЕРНЫЙ!

// --- Определение действия ---
$action = $_REQUEST['action'] ?? null;
if (isset($_GET['delete_id'])) {
    $action = 'delete_promotion';
} elseif (isset($_GET['action'])) { // Для unlink_book, если передается через GET
    $action = $_GET['action'];
}


// --- Функция управления изображениями ---
// Принимает и возвращает ТОЛЬКО имя файла (basename)
function handleImageUpload($file_input_name, $current_image_filename = null) {
    global $physical_target_dir; // Используем глобальную переменную для физического пути

    if (!file_exists($physical_target_dir)) {
        if (!mkdir($physical_target_dir, 0777, true)) {
            return ['error' => "Не удалось создать директорию для изображений: " . $physical_target_dir];
        }
    }

    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == UPLOAD_ERR_OK) {
        $image_file = $_FILES[$file_input_name];
        $filename_original = basename($image_file["name"]);
        $imageFileType = strtolower(pathinfo($filename_original, PATHINFO_EXTENSION));

        // Генерируем уникальное ИМЯ ФАЙЛА, без пути
        $new_filename_base = uniqid('promo_', true) . '.' . $imageFileType;
        $target_file_physical = $physical_target_dir . $new_filename_base; // Полный физический путь для сохранения

        // Проверки
        $check = getimagesize($image_file["tmp_name"]);
        if ($check === false) {
            return ['error' => "Файл не является изображением."];
        }
        if ($image_file["size"] > 5000000) { // 5MB
            return ['error' => "Извините, ваш файл слишком большой (макс 5MB)."];
        }
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($imageFileType, $allowed_types)) {
            return ['error' => "Разрешены только JPG, JPEG, PNG, GIF, WEBP файлы."];
        }

        if (move_uploaded_file($image_file["tmp_name"], $target_file_physical)) {
            // Удалить старое изображение, если оно есть и новое загружено
            if ($current_image_filename && file_exists($physical_target_dir . $current_image_filename)) {
                unlink($physical_target_dir . $current_image_filename);
            }
            return ['filename' => $new_filename_base]; // Возвращаем только имя файла
        } else {
            return ['error' => "Ошибка при загрузке вашего файла на сервер."];
        }
    } elseif (isset($_FILES[$file_input_name])) {
        if ($_FILES[$file_input_name]['error'] == UPLOAD_ERR_INI_SIZE || $_FILES[$file_input_name]['error'] == UPLOAD_ERR_FORM_SIZE) {
            return ['error' => "Файл слишком большой."];
        } elseif ($_FILES[$file_input_name]['error'] == UPLOAD_ERR_NO_FILE) {
            // Файл не был выбран, это не ошибка, если редактирование или не обязательное поле
            return ['filename' => $current_image_filename]; // Возвращаем текущее имя файла
        } elseif ($_FILES[$file_input_name]['error'] != UPLOAD_ERR_OK) {
            return ['error' => "Ошибка загрузки файла: код " . $_FILES[$file_input_name]['error']];
        }
    }
    // Если файл не загружен (или не выбран), возвращаем текущее имя файла (если оно было)
    return ['filename' => $current_image_filename];
}

// --- ОСНОВНОЙ ПЕРЕКЛЮЧАТЕЛЬ ДЕЙСТВИЙ ---
switch ($action) {
    case 'add_promotion':
    case 'edit_promotion':
        $errors = [];
        $title = trim($_POST['title'] ?? '');
        $image_alt = trim($_POST['image_alt'] ?? '');
        $image_srcset = trim($_POST['image_srcset'] ?? ''); // Если вы его используете, убедитесь, что правильно формируете
        $time_range = trim($_POST['time_range'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $active = isset($_POST['active']) ? 1 : 0;
        $promotion_id = isset($_POST['promotion_id']) ? (int)$_POST['promotion_id'] : null;

        // current_image_src из формы должен содержать ПОЛНЫЙ путь из БД (e.g., "images/promotions/old.jpg")
        // если это редактирование. При добавлении это поле отсутствует.
        $current_image_db_path = $_POST['current_image_src'] ?? null;
        $current_image_basename_for_upload_fn = $current_image_db_path ? basename($current_image_db_path) : null;

        // Валидация полей
        if (empty($title)) $errors[] = "Название акции обязательно.";
        // if (empty($image_alt)) $errors[] = "Alt текст для изображения обязателен."; // Раскомментируйте, если нужно
        if (empty($time_range)) $errors[] = "Сроки проведения обязательны.";
        if (empty($location)) $errors[] = "Место проведения обязательно.";
        if (empty($description)) $errors[] = "Описание акции обязательно.";

        // Обработка изображения
        $image_upload_result = handleImageUpload('image_src', $current_image_basename_for_upload_fn);

        if (isset($image_upload_result['error'])) {
            $errors[] = $image_upload_result['error'];
        }

        // $image_upload_result['filename'] содержит ИМЯ нового файла, или старого (если новый не загружен), или null
        $final_image_basename = $image_upload_result['filename'] ?? null;

        // Для новой акции изображение обязательно
        if ($action == 'add_promotion' && empty($final_image_basename)) {
             $errors[] = "Изображение для новой акции обязательно.";
        }

        // Формируем путь для записи в БД
        $image_path_for_db = null;
        if (!empty($final_image_basename)) {
            $image_path_for_db = $db_image_path_prefix . $final_image_basename;
        } elseif ($action == 'edit_promotion' && !empty($current_image_db_path)) {
            // Если при редактировании файл не менялся (не загружался новый, не было ошибок),
            // и $final_image_basename по какой-то причине оказался пуст (например, $_FILES['image_src'] не было),
            // но $current_image_db_path есть, используем его.
            // Эта логика частично дублируется с handleImageUpload, которая должна вернуть $current_image_filename.
            $image_path_for_db = $current_image_db_path;
        }
        // Если $image_path_for_db все еще null (например, удалили существующее изображение при редактировании и не загрузили новое)
        // то в БД для image_src запишется NULL. Это может быть допустимым, если поле image_src в БД разрешает NULL.
        // Если не разрешает, а изображение может быть не задано, то либо делайте его обязательным,
        // либо присваивайте значение по умолчанию, либо измените структуру БД.


        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST; // Сохранить данные формы для предзаполнения
            if ($action == 'add_promotion') {
                header("Location: ../../promotion_form.php"); // Путь к вашей форме добавления
            } else {
                header("Location: ../../promotion_form.php?edit_id=" . $promotion_id); // Путь к вашей форме редактирования
            }
            exit;
        }

        // --- Запись в БД ---
        if ($action == 'add_promotion') {
            $sql = "INSERT INTO promotions (title, image_src, image_alt, image_srcset, time_range, location, description, active, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $mysqli->prepare($sql);
            if (!$stmt) {
                $_SESSION['message'] = "Ошибка подготовки запроса (INSERT): " . $mysqli->error;
                $_SESSION['message_type'] = "error";
                header("Location: ../promotions_manage.php"); // Путь к странице управления акциями
                exit;
            }
            $stmt->bind_param("sssssssi", $title, $image_path_for_db, $image_alt, $image_srcset, $time_range, $location, $description, $active);
        } else { // edit_promotion
            $sql = "UPDATE promotions SET title=?, image_src=?, image_alt=?, image_srcset=?, time_range=?, location=?, description=?, active=?
                    WHERE id=?";
            $stmt = $mysqli->prepare($sql);
            if (!$stmt) {
                $_SESSION['message'] = "Ошибка подготовки запроса (UPDATE): " . $mysqli->error;
                $_SESSION['message_type'] = "error";
                header("Location: ../promotions_manage.php");
                exit;
            }
            $stmt->bind_param("sssssssii", $title, $image_path_for_db, $image_alt, $image_srcset, $time_range, $location, $description, $active, $promotion_id);
        }

        if ($stmt->execute()) {
            $_SESSION['message'] = ($action == 'add_promotion') ? "Акция успешно добавлена." : "Акция успешно обновлена.";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Ошибка выполнения запроса: " . $stmt->error;
            $_SESSION['message_type'] = "error";
            // Если была ошибка SQL, но файл был загружен, его можно удалить, но это усложнит логику.
            // Обычно оставляют как есть, или добавляют транзакции.
        }
        $stmt->close();
        header("Location: ../promotions_manage.php"); // Путь к странице управления акциями
        exit;

    case 'delete_promotion':
        $delete_id = (int)($_GET['delete_id'] ?? 0);
        if ($delete_id > 0) {
            // Сначала получим имя файла изображения, чтобы удалить его
            $img_sql = "SELECT image_src FROM promotions WHERE id = ?";
            $stmt_img = $mysqli->prepare($img_sql);
            $image_db_path_to_delete = null; // Полный путь из БД, например "images/promotions/file.jpg"

            if ($stmt_img) {
                $stmt_img->bind_param("i", $delete_id);
                $stmt_img->execute();
                $img_res = $stmt_img->get_result();
                if ($img_row = $img_res->fetch_assoc()) {
                    $image_db_path_to_delete = $img_row['image_src'];
                }
                $stmt_img->close();
            } else {
                 $_SESSION['message'] = "Ошибка подготовки запроса на получение изображения: " . $mysqli->error;
                 $_SESSION['message_type'] = "error";
                 header("Location: ../promotions_manage.php");
                 exit;
            }


            // Удаляем записи из связующей таблицы (promotion_products)
            // Это хорошая практика, даже если у вас есть CASCADE DELETE в БД
            $delete_links_sql = "DELETE FROM promotion_products WHERE promotion_id = ?";
            $stmt_links = $mysqli->prepare($delete_links_sql);
            if ($stmt_links) {
                $stmt_links->bind_param("i", $delete_id);
                $stmt_links->execute();
                $stmt_links->close();
            } else {
                // Не фатальная ошибка, но стоит залогировать или сообщить
                 $_SESSION['message_warning'] = "Не удалось подготовить запрос на удаление связанных продуктов: " . $mysqli->error;
            }


            // Удаляем саму акцию
            $sql = "DELETE FROM promotions WHERE id = ?";
            $stmt = $mysqli->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $delete_id);
                if ($stmt->execute()) {
                    // Удаляем файл изображения с сервера
                    if ($image_db_path_to_delete) {
                        $filename_to_delete = basename($image_db_path_to_delete);
                        $physical_file_path_to_delete = $physical_target_dir . $filename_to_delete;

                        if (file_exists($physical_file_path_to_delete)) {
                            if (!unlink($physical_file_path_to_delete)) {
                                // Ошибка удаления файла, можно залогировать
                                if (isset($_SESSION['message_warning'])) $_SESSION['message_warning'] .= " ";
                                else $_SESSION['message_warning'] = "";
                                $_SESSION['message_warning'] .= "Не удалось удалить файл изображения " . htmlspecialchars($physical_file_path_to_delete) . ".";
                            }
                        }
                    }
                    $_SESSION['message'] = "Акция успешно удалена.";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Ошибка удаления акции из БД: " . $stmt->error;
                    $_SESSION['message_type'] = "error";
                }
                $stmt->close();
            } else {
                $_SESSION['message'] = "Ошибка подготовки запроса на удаление акции: " . $mysqli->error;
                $_SESSION['message_type'] = "error";
            }
        } else {
            $_SESSION['message'] = "Некорректный ID для удаления.";
            $_SESSION['message_type'] = "error";
        }
        header("Location: ../promotions_manage.php"); // Путь к странице управления акциями
        exit;

    case 'link_book':
        $promotion_id = (int)($_POST['promotion_id'] ?? 0);
        $book_id = (int)($_POST['book_id_to_add'] ?? 0);

        if ($promotion_id > 0 && $book_id > 0) {
            // Проверим, нет ли уже такой связи
            $check_sql = "SELECT 1 FROM promotion_products WHERE promotion_id = ? AND product_id = ?";
            $stmt_check = $mysqli->prepare($check_sql);
            if ($stmt_check) {
                $stmt_check->bind_param("ii", $promotion_id, $book_id);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();

                if ($result_check->num_rows == 0) {
                    $sql = "INSERT INTO promotion_products (promotion_id, product_id) VALUES (?, ?)";
                    $stmt = $mysqli->prepare($sql);
                    if ($stmt) {
                        $stmt->bind_param("ii", $promotion_id, $book_id);
                        if ($stmt->execute()) {
                            $_SESSION['message'] = "Книга успешно привязана к акции.";
                            $_SESSION['message_type'] = "success";
                        } else {
                            $_SESSION['message'] = "Ошибка привязки книги: " . $stmt->error;
                            $_SESSION['message_type'] = "error";
                        }
                        $stmt->close();
                    } else {
                        $_SESSION['message'] = "Ошибка подготовки запроса на привязку книги: " . $mysqli->error;
                        $_SESSION['message_type'] = "error";
                    }
                } else {
                    $_SESSION['message'] = "Эта книга уже привязана к данной акции.";
                    $_SESSION['message_type'] = "warning";
                }
                $stmt_check->close();
            } else {
                 $_SESSION['message'] = "Ошибка подготовки запроса на проверку связи: " . $mysqli->error;
                 $_SESSION['message_type'] = "error";
            }
        } else {
            $_SESSION['message'] = "Некорректные ID для привязки книги.";
            $_SESSION['message_type'] = "error";
        }
        // Путь к странице управления товарами акции
        header("Location: ../promotion_books.php?promo_id=" . $promotion_id);
        exit;

    case 'unlink_book':
        $promotion_id = (int)($_GET['promo_id'] ?? 0);
        $book_id = (int)($_GET['book_id'] ?? 0);

        if ($promotion_id > 0 && $book_id > 0) {
            $sql = "DELETE FROM promotion_products WHERE promotion_id = ? AND product_id = ?";
            $stmt = $mysqli->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ii", $promotion_id, $book_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Книга успешно отвязана от акции.";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Ошибка отвязки книги: " . $stmt->error;
                    $_SESSION['message_type'] = "error";
                }
                $stmt->close();
            } else {
                 $_SESSION['message'] = "Ошибка подготовки запроса на отвязку книги: " . $mysqli->error;
                 $_SESSION['message_type'] = "error";
            }
        } else {
            $_SESSION['message'] = "Некорректные ID для отвязки книги.";
            $_SESSION['message_type'] = "error";
        }
        // Путь к странице управления товарами акции
        header("Location: ../promotion_books.php?promo_id=" . $promotion_id);
        exit;

    default:
        $_SESSION['message'] = "Неизвестное действие: " . htmlspecialchars($action ?? 'не указано');
        $_SESSION['message_type'] = "error";
        header("Location: ../promotions_manage.php"); // Путь к странице управления акциями
        exit;
}

$mysqli->close();
?>