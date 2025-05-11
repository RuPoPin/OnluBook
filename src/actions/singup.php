<?php
    session_start();
    require_once '../config/connect.php';
    $name = $_POST['login'];
    $password = $_POST['password'];

    $stmt = $connect->prepare("SELECT * FROM `user` WHERE `name` = ? AND `password` = ?");
    if ($stmt === false) {
        die("Prepare failed: " . $connect->error);
    }
    $stmt->bind_param("ss", $name, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    
        $_SESSION['user'] = [
            "id" => $user['id'],
            "name" => $user['name'],
            "email" => $user['email'],
            "access" => $user['access']
        ];
    
        header('Location: ../../index.php');
        exit();
    }
    else{
        $_SESSION['messange'] = 'Неверный логин или пароль';
        header('Location: ../../Side/register.php');
    }
