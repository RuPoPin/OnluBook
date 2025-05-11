<?php
    require_once '../config/connect.php';
    $title = $_POST['title'];
    $description = $_POST['description'];
    $prise = $_POST['prise'];
    $id = $_POST['id'];
    mysqli_query($connect, "UPDATE `goods` SET `title` = '$title', `description` = '$description', `prise` = '$prise' WHERE `goods`.`id` = '$id'");

    header('Location: ../crad.php');