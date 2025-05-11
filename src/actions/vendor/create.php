<?php
    require_once '../config/connect.php';
    $title = $_POST['title'];
    $description = $_POST['description'];
    $prise = $_POST['prise'];

   mysqli_query($connect, "INSERT INTO `goods` (`id`, `title`, `description`, `prise`) VALUES (NULL, '$title', '$description', '$prise')");

    header('Location: ../crad.php');