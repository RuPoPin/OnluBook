<?php 
    session_start();
    require_once '../../../src/config/connect.php';
    print_r($_POST);
    $id = $_POST['id'];
    
    $name = $_POST['name'];
    $autor = $_POST['autor'];
    $description = $_POST['description'];
    $resultSelect = mysqli_query($connect, "UPDATE `news` SET `autor` = '$autor', `name_n` = '$name', `description_n` = '$description' WHERE `news`.`id_n` = $id");
    header("Location: ../edit_news.php");
?>