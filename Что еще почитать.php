<?php 
    session_start()
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OnlyBook | Что почитать</title>
    <link rel="stylesheet" href="Style/style.css?<?echo time()?>">
    <link rel="stylesheet" href="Side/Style/stylez.css?<?echo time()?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Comfortaa:wght@300..700&display=swap" rel="stylesheet">
</head>
<body>
    <? include 'blocks/menu.php';?>
    <main>
        <h1 class="block1">Что еще почитать?</h1>
        <div class="content">
            
            <a href="Side/holidei_season.php"><div class="insept">
                <img class="images" src="images/more_book/asiat_fentesy.jpg" alt="" srcset="">
                <h3>Азиатское фэнтези</h3>
                <div class="text">
                    Книги с атмосферой Японии, Китая и Корё
                </div>
            </div></a>
            <a href="Side/evrifing.php"><div class="insept">
                <img class="images" src="images/more_book/fantesy_castel.jpg" alt="" srcset="">
                <h3>Фэнтези о дворцовых интригах</h3>
                <div class="text">
                    Книги Ли Бардуго, Виктории Авеярд, Лии Арден и не только
                </div>
            </div></a>
            <a href="Side/anime-desert.php"><div class="insept">
                <img class="images" src="images/more_book/books_last.jpg" alt="" srcset="">
                <h3>Книги о прошлом</h3>
                <div class="text">
                    От Николая Свечина до Бернанда Корнуэлла
                </div>
            </div></a>
            <a href="Side/big_book_sale.php"><div class="insept">
                <img class="images" src="images/more_book/play_of_survale.jpg" alt="" srcset="">
                <h3>Игры на выживание</h3>
                <div class="text">
                    "Бегущий в лабиринте", "Наследие Хоторнов", "Игра в кальмара"
                </div>
            </div></a>
            <a href="Side/books_hours.php"><div class="insept">
                <img class="images" src="images/more_book/korea.jpg" alt="" srcset="">
                <h3>Корея</h3>
                <div class="text">
                    От дорам до BTS
                </div>
            </div></a>
            <a href="Side/final_of_trilogi.php"><div class="insept">
                <img class="images" src="images/more_book/saga_of_dragon.jpg" alt="" srcset="">
                <h3>Саги о драконах</h3>
                <div class="text">
                   Книги Джорджа Мартина, Крессиды Коуэлл, Робин Хобб и других писателей
                </div>
            </div></a>
            <a href="Side/healf.php"><div class="insept">
                <img class="images" src="images/more_book/sport_and_love.jpg" alt="" srcset="">
                <h3>Про спорт и любовь</h3>
                <div class="text">
                    Романтичные книги. 1:0 в пользу чувств
                </div>
            </div></a>
            <a href="Side/Minecraft.php"><div class="insept">
                <img class="images" src="images/more_book/manga.jpg" alt="" srcset="">
                <h3>Манга: Комедия</h3>
                <div class="text">
                    Веселые японские сюжеты
                </div>
            </div></a>
            <a href="Side/horror.php"><div class="insept">
                <img class="images" src="images/more_book/Brain_book.jpg" alt="" srcset="">
                <h3>Умные книги</h3>
                <div class="text">
                   От Юкио Мисимы до Шамиля Идиатуллина
                </div>
            
    </main>
    <? include 'blocks/footer.php';?>
</body>
</html>