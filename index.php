<?php 
    session_start();
    require_once 'src/config/connect.php';
    $resultSelect = $connect->prepare("SELECT * FROM `Product`");
    $resultSelect -> execute();
    $resultSelect = $resultSelect -> get_result();
    $rows = mysqli_fetch_all($resultSelect);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name= "description" content="Интернет-магазин OnlyBook – широкий выбор книг, художественной и профессиональной литературы"/>
    <meta name="keywords" content="Книги , комиксы, журналы, психология, манга"/>
    <meta name= "robots" content="index, follow"/>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">     
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OnlyBooK | Книги мира</title>
    <link rel="stylesheet" href="Style/style.css?<? echo time()?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Comfortaa:wght@300..700&display=swap" rel="stylesheet">
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-MD1BSLSZY2"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-MD1BSLSZY2');
    </script>
</head>
<body>
<?php include 'blocks/menu.php'; ?>
<main>
    <?php

    if (!isset($connect)) {
        $connect = new MockDB();
    }

    function display_books_by_genre(array $allBooks, string $genre, int $limit = 6): void {
        if (empty($allBooks)) {
            echo "<p>Нет доступных книг.</p>";
            return;
        }

        $filteredBooks = [];
        foreach ($allBooks as $book) {
            // Ensure the genre index (4) and other necessary indices exist
            // Now assuming index 6 is price and index 7 is sale_price
            if (isset($book[4], $book[0], $book[1], $book[2], $book[6], $book[7], $book[9]) && $book[4] == $genre) {
                $filteredBooks[] = $book;
            }
            if (count($filteredBooks) >= $limit) {
                break;
            }
        }

        if (empty($filteredBooks)) {
            echo "<p>Нет книг в жанре \"" . htmlspecialchars($genre) . "\".</p>";
            return;
        }

        foreach ($filteredBooks as $book) {
            $price = ($book[7] != 0) ? $book[7] : $book[6]; // Use sale_price if not 0, else use regular price
            ?>
            <article class="book">
                <a href="product_detail.php?id=<?= htmlspecialchars($book[0]); ?>" class="product-link-image">
                    <div class="images">
                        <img src="images/book/<?= htmlspecialchars($book[9]); ?>" alt="<?= htmlspecialchars($book[1]); ?>">
                    </div>
                </a>
                <div class="prise">
                    <a href="product_detail.php?id=<?= htmlspecialchars($book[0]); ?>" class="product-link-title">
                        <span class="fonts">
                            <?= htmlspecialchars($book[1]); ?> <br> <b><?= htmlspecialchars($book[2]); ?></b>
                        </span>
                    </a>
                    <div class="price">
                        <?php if ($book[7] != 0): ?>
                            <span class="old-price"><?= htmlspecialchars($book[6]); ?> ₽</span>
                            <span class="sale-price"><?= htmlspecialchars($book[7]); ?> ₽</span>
                        <?php else: ?>
                            <span class="regular-price"><?= htmlspecialchars($book[6]); ?> ₽</span>
                        <?php endif; ?>
                    </div>
                    <form method="post" action="src/actions/add_to_card.php" class="add-to-cart-form">
                        <input type="hidden" name="product_id" value="<?= htmlspecialchars($book[0]); ?>">
                        <button type="submit" class="buy button">В корзину</button>
                    </form>
                </div>
            </article>
            <?php
        }
    }
    ?>

        <div class="content">
            <?php include 'blocks/slider.php'; ?>
            <div class="container">
                <div class="tab">
                    <div class="tab-content" id="content-1">
                        <div class="products">
                            <?php display_books_by_genre($rows, "Фентези", 6); ?>
                        </div>
                    </div>
                    <div class="tab-content" id="content-2">
                        <div class="products">
                            <?php display_books_by_genre($rows, "Ужасы", 6); ?>
                        </div>
                    </div>
                    <div class="tab-content" id="content-3">
                        <div class="products">
                            <?php display_books_by_genre($rows, "Детектив", 6); ?>
                        </div>
                    </div>
                    <div class="tab-nav">
                        <input checked id="tab-btn-1" name="tab-btn" type="radio" value="">
                        <label for="tab-btn-1">ФАНТАСТИКА</label>
                        <input id="tab-btn-2" name="tab-btn" type="radio" value="">
                        <label for="tab-btn-2">УЖАСЫ</label>
                        <input id="tab-btn-3" name="tab-btn" type="radio" value="">
                        <label for="tab-btn-3">ДЕТЕКТИВ</label>
                    </div>
                </div>

            <?php
                // Используем правильные имена столбцов из таблицы `news`
                // Сортируем по `id_n` DESC, чтобы получить последние новости
                $selectNewsStmt = $connect->prepare("SELECT id_n, name_n, description_n, img_n FROM `news` ORDER BY id_n DESC LIMIT 5");

                if ($selectNewsStmt === false) {
                    // Обработка ошибки подготовки запроса новостей
                    error_log("Failed to prepare news query: " . $connect->error);
                    $newsItems = []; // Устанавливаем $newsItems в пустой массив
                } else {
                    $selectNewsStmt->execute();
                    $selectNewsResult = $selectNewsStmt->get_result();
                    $newsItems = $selectNewsResult->fetch_all(MYSQLI_ASSOC); // Fetch as associative array
                    $selectNewsStmt->close(); // Закрываем стейтмент
                }
            ?>
            <div class="news-slider-wrapper">
                <h2>Новости недели</h2>
                <?php if (!empty($newsItems)): ?>
                <div class="slider news-slider">
                    <div class="slides">
                        <?php
                        $isFirstSlide = true;
                        foreach($newsItems as $newsItem):
                        ?>
                            <article class="slide <?= $isFirstSlide ? 'active' : ''; ?>">
                                <a href="news_detail.php?id=<?= htmlspecialchars($newsItem['id_n']); ?>">
                                    <img src="images/news/<?= htmlspecialchars($newsItem['img_n']); ?>" alt="<?= htmlspecialchars($newsItem['name_n']); ?>">
                                    <p class="name_desc"><?= htmlspecialchars($newsItem['name_n']); ?></p>
                                </a>
                            </article>
                        <?php
                        $isFirstSlide = false;
                        endforeach;
                        ?>
                    </div>
                    <button class="prev" onclick="changeSlide(-1)">❮</button>
                    <button class="next" onclick="changeSlide(1)">❯</button>
                </div>
                <?php else: ?>
                <p>На данный момент новостей нет.</p>
                <?php endif; ?>
            </div>
            <script defer src="src/down_script.js"></script>

        </div>
    </div>
</main>
<?php include 'blocks/footer.php'; ?>
</body>
</html>