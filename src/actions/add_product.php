<?php
    require_once '../config/connect.php';
    $product = mysqli_query($connect,"SELECT * FROM `Product`" );

    print_r($product)
?>
