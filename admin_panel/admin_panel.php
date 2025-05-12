<?php
session_start();
if ((!$_SESSION['user']['access'] ?? '0') == "1") {
    header("Location: ../index.php"); // или куда-то еще
    exit;
}
include('../src/config/connect.php'); 
if ($connect->connect_error) {
    die("Ошибка подключения к базе данных: " . $connect->connect_error);
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Добавлен viewport -->
    <title>Административная панель</title>
    <!-- Проверьте этот путь и слэш -->
    <link rel="stylesheet" href="functional/style.css?<?php echo time(); ?>"> <!-- Общие стили админки -->
    <style>
        /* Дополнительные стили для главной страницы админки */
        body { font-family: 'Comfortaa', sans-serif; margin: 0; background-color: #f4f4f4; color: #333; } /* Унаследовано из collection_details стилей */
        header, footer { background-color: #34495e; color: white; padding: 10px 20px; text-align: center; }
        header h1 { margin: 0; }
        main { max-width: 1200px; margin: 20px auto; padding: 0 15px; min-height: 60vh;} /* Добавлен min-height */
        nav { max-width: 1200px; margin: 0 auto; padding: 0 15px; background-color: #ecf0f1; }
        .admin-menu { list-style: none; padding: 10px 0; margin: 0; display: flex; flex-wrap: wrap;} /* Flexbox для горизонтального меню */
        .admin-menu li { margin: 0 15px 10px 0; } /* Отступы между пунктами */
        .admin-menu li:last-child { margin-right: 0; }
        .admin-menu a { text-decoration: none; font-size: 1.1em; color: #337ab7; padding: 5px 0; display: block;}
        .admin-menu a:hover { text-decoration: underline; color: #2a6496; }

        /* Адаптивность для меню */
        @media (max-width: 600px) {
            .admin-menu { flex-direction: column; }
            .admin-menu li { margin: 5px 0; }
        }
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
            <li><a href="functional/manage_collections.php">Управление подборками</a></li>
             <li><a href="../logout.php">Выход</a></li> 
        </ul>
    </nav>
    <main>
        <h2>Главная</h2>
        <p>Добро пожаловать в административную панель. Выберите раздел для управления.</p>
        
        <?php
            $book_count = 0;
            $result_count = $connect->query("SELECT COUNT(*) AS total_books FROM Product");
            if ($result_count) {
                $row_count = $result_count->fetch_assoc();
                $book_count = $row_count['total_books'];
                $result_count->free();
            }
            echo "<p>Всего книг в базе: " . $book_count . "</p>";

            $collection_count = 0;
             $result_col_count = $connect->query("SELECT COUNT(*) AS total_collections FROM collections");
            if ($result_col_count) {
                $row_col_count = $result_col_count->fetch_assoc();
                $collection_count = $row_col_count['total_collections'];
                 $result_col_count->free(); 
            }
            echo "<p>Всего подборок: " . $collection_count . "</p>";

             if ($connect instanceof mysqli) {
                 $connect->close();
             }
        ?>
    </main>
    <footer>
        <p>© <?php echo date("Y"); ?> OnlyBook Admin</p>
    </footer>
</body>
</html>