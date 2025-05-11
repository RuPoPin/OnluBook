<?php 
    session_start();
    require_once '../../../src/config/connect.php';

    $product_id = $_GET['id'];
    $resultSelect = $connect->prepare("SELECT * FROM `news` WHERE `id_n` = '$product_id'");
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
    </div>

    <form action="uptate_news.php" id="add-form"  method="post"  enctype="multipart/form-data">
        <input type="hidden" name="id" value='<?=$rows['id_n']?>'>
        <p>Введите название книги</p>
        <div class="input_box">
            <input type="text" placeholder="Введите название книги" name="name" value='<?=$rows['name_n'] ?>'>
        </div>
        <p>Введите Автора книги</p>
        <div class="input_box">
            <input type="text" placeholder="Введите Автора" name="autor" value='<?=$rows['autor'] ?>'>
        </div>
        <p>Введите описание книги</p>
        <div class="input_box" id="description">
            <textarea placeholder="Введите описание" name="description" ><?=$rows['description_n'] ?></textarea>
        </div>
        <p>Вставте изображение книги</p>
        <div class="input_box">
            <input type="file" placeholder="Введите цену" name="file"  ?>
        </div>
        <button type="submit">Изменить</button>
    </form>

    
</body>
</html>