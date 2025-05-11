<?php
session_start();
require_once '../config/connect.php';

// Обработка формы регистрации
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Инициализация переменных
    $errors = [];
    $login = trim($_POST['login'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $conf_password = $_POST['conf_password'] ?? '';

    // Валидация полей
    if (empty($login)) {
        $errors[] = "Вы не заполнили поле 'Логин'";
    } elseif (strlen($login) < 3) {
        $errors[] = "Логин должен содержать минимум 3 символа";
    }

    if (empty($email)) {
        $errors[] = "Вы не заполнили поле 'Email'";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Некорректный формат email";
    }

    if (empty($password)) {
        $errors[] = "Вы не заполнили поле 'Пароль'";
    } elseif (strlen($password) < 6) {
        $errors[] = "Пароль должен содержать минимум 6 символов";
    }

    if ($password !== $conf_password) {
        $errors[] = "Пароли не совпадают";
    }

    // Проверка уникальности логина и email
    if (empty($errors)) {
        $stmt = $connect->prepare("SELECT id FROM user WHERE name = ? OR email = ?");
        $stmt->bind_param("ss", $login, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = 'Пользователь с таким логином или email уже существует';
        }
    }

    // Если ошибок нет - регистрируем пользователя
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $connect->prepare("INSERT INTO user (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $login, $email, $hashed_password);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = 'Регистрация прошла успешно!';
            header('Location: ../../Side/auth.php');
            exit;
        } else {
            $errors[] = 'Ошибка при регистрации: ' . $connect->error;
        }
    }

    // Если есть ошибки - сохраняем их в сессию
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old'] = [
            'login' => $login,
            'email' => $email
        ];
        header('Location: ../../Side/register.php');
        exit;
    }
} else {
    header('Location: ../../Side/register.php');
    exit;
}