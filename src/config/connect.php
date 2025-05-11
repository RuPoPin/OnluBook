<?php
    session_start();
    $connect = mysqli_connect('localhost', 'root', '', 'users');
if (!$connect) {
    die('Ошибка подключения к бд');
}

