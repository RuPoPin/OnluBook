<?php 
    session_start();
    require_once '../../../src/config/connect.php';

    $product_id = $_GET['id'];
    $resultSelect = $connect->prepare("SELECT * FROM `Product` WHERE `id` = '$product_id'");
    $resultSelect->execute();
    $resultSelect = $resultSelect -> get_result();
    $rows = mysqli_fetch_assoc($resultSelect);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../style.css">
    <title>Админ панель</title>
</head>
<body>
    <h1>Изменение данных </h1>
    <a href="../edit_book.php" > Назад</a>
    <div class="error">
        <?php
            if(isset($_SESSION['error'])){
                echo $_SESSION['error'];
            }
            unset($_SESSION['error']);
        ?>
    </div>

    <form action="update_book.php" id="add-form"  method="post"  enctype="multipart/form-data">
        <input type="hidden" name="id" value='<?=$rows['id']?>'>
        <p>Введите название книги</p>
        <div class="input_box">
            <input type="text" placeholder="Введите название книги" name="name" value='<?=$rows['name'] ?>'>
        </div>
        <p>Введите Автора книги</p>
        <div class="input_box">
            <input type="text" placeholder="Введите Автора" name="autor" value='<?=$rows['autor'] ?>'>
        </div>
        <p>Введите описание книги</p>
        <div class="input_box" id="description">
            <textarea placeholder="Введите описание" name="description" ><?=$rows['description'] ?></textarea>
        </div>
        <p>Введите жанр</p>
        <div class="input_box" >
        <input type="radio" name="genre" value="Фентези">Фентези <br>
        <input type="radio" name="genre" value="Ужасы"> Ужасы <br>
        <input type="radio" name="genre" value="Детектив"> Детектив <br>
        <input type="radio" name="genre" value="романтика"> Романтика
        </div>
        <p>Введите издание книги</p>
        <div class="input_box">
            <input type="text" placeholder="Введите издание" name="imprint" value='<?=$rows['imprint'] ?>'>
        </div>
        <p>Введите цену книги</p>
        <div class="input_box">
            <input type="text" placeholder="Введите цену" name="prise" value='<?=$rows['prise'] ?>'>
        </div>
        <p>Введите колличество страниц </p>
        <div class="input_box">
            <input type="text" placeholder="Введите количество страниц" name="quantity" value='<?=$rows['quantity'] ?>'>
        </div>
        <p>Вставте изображение книги</p>
        <div class="input_box">
            <input type="file" placeholder="Введите цену" name="file"  ?>'>
        </div>
        <button type="submit">Изменить</button>
    </form>

    
</body>
</html>