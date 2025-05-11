<?php
    session_start();
    require_once '../config/connect.php';

    unset($_SESSION['user']);
    header('Location: ../../index.php');