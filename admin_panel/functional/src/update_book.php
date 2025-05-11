<?php 
    session_start();
    require_once '../../../src/config/connect.php';

    $id = $_POST['id'];
    
    $name = $_POST['name'];
    $autor = $_POST['autor'];
    $description = $_POST['description'];
    $genre = $_POST['genre'];
    $imprint = $_POST['imprint'];
    $prise = $_POST['prise'];
    $quantity  = $_POST['quantity'];
    $resultSelect = mysqli_query($connect, "UPDATE `Product` SET `name` = '$name', `genre` = '$genre', `imprint` = '$imprint', prise = '$prise', `quantity` = '$quantity' WHERE `Product`.`id` = $id");
    header("Location: ../edit_book.php");
?>