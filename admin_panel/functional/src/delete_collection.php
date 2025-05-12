<?php
session_start();

if (!isset($_SESSION['user']) || ($_SESSION['user']['access'] ?? 0) != 1) {
    header("Location: ../../../index.php");
    exit;
}

require_once '../../../src/config/connect.php';

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
    header("Location: ../manage_collections.php?error=invalid_id");
    exit;
}

$collection_id = (int)$_GET['id'];
$image_path_to_delete = null;
$error_message = null;
$success = false;

$sql_select = "SELECT image_path FROM collections WHERE id = ?";
$stmt_select = mysqli_prepare($connect, $sql_select);

if ($stmt_select) {
    mysqli_stmt_bind_param($stmt_select, "i", $collection_id);
    mysqli_stmt_execute($stmt_select);
    $result = mysqli_stmt_get_result($stmt_select);
    $collection_data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt_select);

    if ($collection_data && !empty($collection_data['image_path'])) {
        $image_path_to_delete = $collection_data['image_path'];
    }
} else {
    $error_message = "Ошибка подготовки запроса для получения изображения: " . mysqli_error($connect);
    error_log($error_message);
}

if (!$error_message) {
    $sql_delete = "DELETE FROM collections WHERE id = ?";
    $stmt_delete = mysqli_prepare($connect, $sql_delete);

    if ($stmt_delete) {
        mysqli_stmt_bind_param($stmt_delete, "i", $collection_id);

        if (mysqli_stmt_execute($stmt_delete)) {
            if (mysqli_stmt_affected_rows($stmt_delete) > 0) {
                $success = true; 
            } else {
                $success = true; 
                error_log("Попытка удаления несуществующей подборки ID: " . $collection_id);
            }
        } else {
            $error_message = "Ошибка выполнения запроса на удаление: " . mysqli_stmt_error($stmt_delete);
            error_log($error_message);
        }
        mysqli_stmt_close($stmt_delete);
    } else {
        $error_message = "Ошибка подготовки запроса на удаление: " . mysqli_error($connect);
        error_log($error_message);
    }
}

if ($success && $image_path_to_delete) {
    $file_system_path = '../../../' . $image_path_to_delete;

    if (file_exists($file_system_path)) {
        if (!@unlink($file_system_path)) {
            error_log("WARNING: Не удалось удалить файл изображения: " . $file_system_path . " - Проверьте права доступа.");
            header("Location: ../manage_collections.php?status=deleted_db_only&id=" . $collection_id);
            exit;
        }
    } else {
        error_log("NOTICE: Файл для удаления не найден по пути: " . $file_system_path);
    }
}

if (isset($connect) && $connect) {
    mysqli_close($connect);
}

if ($success) {
    header("Location: ../manage_collections.php?status=deleted&id=" . $collection_id);
    exit;
} else {

    $_SESSION['delete_error'] = $error_message ?: "Неизвестная ошибка при удалении.";
    header("Location: ../manage_collections.php?error=delete_failed&id=" . $collection_id);
    exit;
}
?>
