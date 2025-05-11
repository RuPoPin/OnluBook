<?php 
    session_start();
    require_once '../../../src/config/connect.php';

    $id = $_GET['id'];

    mysqli_query($connect,"DELETE FROM `Product` WHERE `Product`.`id` = $id" );
    header("Location: ../edit_book.php");
    