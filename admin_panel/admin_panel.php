
<?php
session_start();

if ((!$_SESSION['user']['access'] ?? '0') == "1") {
    header("Location: ../index.php"); // или куда-то еще
    exit;
}
// Предположим, у вас есть проверка авторизации
include('../src/config/connect.php'); // Путь к файлу конфигурации БД
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Административная панель</title>
    <link rel="stylesheet" href="functional\style.css?<? echo time()?>"> <!-- Общие стили админки -->
    <style>
        /* Дополнительные стили для главной страницы админки */
        .admin-menu { list-style: none; padding: 0; }
        .admin-menu li { margin: 10px 0; }
        .admin-menu a { text-decoration: none; font-size: 1.2em; color: #337ab7; }
        .admin-menu a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <header>
        <h1>Административная панель</h1>
    </header>
    <nav>
        <ul class="admin-menu">
            <li><a href="functional/add_book.php">Добавить книгу</a></li>
            <li><a href="functional/edit_book.php">Редактировать/Удалить книгу</a></li>
            <li><a href="functional/add_news.php">Добавить новость</a></li>
            <li><a href="functional/edit_news.php">Редактировать/Удалить новость</a></li>
            <li><a href="functional/promotions_manage.php">Управление акциями</a></li>
        </ul>
    </nav>
    <main>
        <p>Добро пожаловать в административную панель. Выберите раздел для управления.</p>
    </main>
    <footer>
        <p>© <?php echo date("Y"); ?> OnlyBook Admin</p>
    </footer>
</body>
</html>