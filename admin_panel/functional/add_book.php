<?php 
    session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <title>Админ панель</title>
</head>
<body>
    <h1>Добавление книги</h1>
    <a href="../" > Назад</a>
    <div class="error">
        <?php
            if(isset($_SESSION['error'])){
                echo $_SESSION['error'];
            }
            unset($_SESSION['error']);
        ?>
    </div>
    <form action="src/add_newbook.php" id="add-form"  method="post"  enctype="multipart/form-data">
        <p>Введите название книги</p>
        <div class="input_box">
            <input type="text" placeholder="Введите название книги" name="name">
        </div>
        <p>Введите Автора книги</p>
        <div class="input_box">
            <input type="text" placeholder="Введите Автора" name="autor">
        </div>
        <p>Введите описание книги</p>
        <div class="input_box" id="description">
            <textarea placeholder="Введите описание" name="description"></textarea>
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
            <input type="text" placeholder="Введите издание" name="imprint">
        </div>
        <p>Введите цену книги</p>
        <div class="input_box">
            <input type="text" placeholder="Введите цену" name="prise">
        </div>
        <p>Введите колличество страниц </p>
        <div class="input_box">
            <input type="text" placeholder="Введите количество страниц" name="quantity">
        </div>
        <p>Вставте изображение книги</p>
        <div class="input_box">
            <input type="file" placeholder="Введите цену" name="file">
        </div>
        <button type="submit">добавить</button>
    </form>
</body>
</html>