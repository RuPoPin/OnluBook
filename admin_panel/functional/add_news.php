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
    <h1>Создание Новостей</h1>
    <a href="../" > Назад</a>
    <div class="error">
        <?php
            if(isset($_SESSION['error'])){
                echo $_SESSION['error'];
            }
            unset($_SESSION['error']);
        ?>
    </div>
    <form action="src/add_news.php" id="add-form"  method="post"  enctype="multipart/form-data">
        <p>Введите Заголовок</p>
        <div class="input_box">
            <input type="text" placeholder="Введите заголовок" name="name_n">
        </div>
        <p>Введите автора</p>
        <div class="input_box">
            <input type="text" placeholder="Введите заголовок" name="autor">
        </div>
        <p> Введите описание</p>
        <div class="input_box" id="description">
            <textarea placeholder="Введите описание" name="description_n"></textarea>
        </div>
        <p>Вставте изображение акции</p>
        <div class="input_box">
            <input type="file"  name="file">
        </div>
        <button type="submit">добавить</button>
    </form>
</body>
</html>