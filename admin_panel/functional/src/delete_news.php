<?php 
    session_start();
    require_once '../../../src/config/connect.php';

    $id = $_GET['id'];

    mysqli_query($connect,"DELETE FROM `Product` WHERE `news`.`id_n` = $id" );
    header("Location: ../edit_news.php");
    