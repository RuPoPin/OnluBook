<?php
// Файл: promotion_detail.php
session_start();
// Подключаем конфигурацию БД ОДИН РАЗ. Переменная $connect станет доступна.
include_once 'src/config/connect.php';

$promotion_id = null;
$promotion = null;
$promotion_books = [];
$error_message = '';
$promotion_data_loaded = false; // Флаг для отслеживания загрузки основных данных акции

// Проверяем, было ли успешно установлено соединение в connect.php
if (!$connect instanceof mysqli || $connect->connect_error) {
    $error_message = "Ошибка подключения к базе данных. Попробуйте позже.";
    if ($connect instanceof mysqli && $connect->connect_error) { // Если объект создан, но есть ошибка
         $error_message .= " Детали: " . $connect->connect_error;
    }
    // Дальнейшие операции с БД невозможны
} else {
    // Продолжаем, только если соединение в порядке
    if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
        $promotion_id = (int)$_GET['id'];
    } else {
        $error_message = "Некорректный или отсутствующий ID акции.";
    }

    if ($promotion_id && empty($error_message)) { // Продолжаем, если ID валиден и нет ошибок соединения
        // Получаем информацию об акции
        $sql_promotion = "SELECT title, image_src, image_alt, image_srcset, time_range, location, description FROM promotions WHERE id = ? AND active = TRUE";

        if ($stmt_promotion = $connect->prepare($sql_promotion)) {
            $stmt_promotion->bind_param("i", $promotion_id);
            $stmt_promotion->execute();
            $result_promotion = $stmt_promotion->get_result();

            if ($result_promotion && $result_promotion->num_rows > 0) {
                $promotion = $result_promotion->fetch_assoc();
                $promotion_data_loaded = true; // Основные данные акции загружены

                // Если акция найдена, получаем связанные с ней книги
                $sql_books = "SELECT p.id, p.name, p.autor, p.image, p.prise, p.sale_prise
                              FROM Product p
                              JOIN promotion_products pp ON p.id = pp.product_id
                              WHERE pp.promotion_id = ?";

                if ($stmt_books = $connect->prepare($sql_books)) {
                    $stmt_books->bind_param("i", $promotion_id);
                    $stmt_books->execute();
                    $result_books = $stmt_books->get_result();

                    while ($row_book = $result_books->fetch_assoc()) {
                        $promotion_books[] = $row_book;
                    }
                    $stmt_books->close();
                } else {
                    // Добавляем ошибку, не перезаписывая существующие
                    $error_message .= (empty($error_message) ? "" : " ") . "Ошибка загрузки книг для акции: " . $connect->error;
                }
            } else {
                $error_message = "Акция не найдена или не активна.";
            }
            $stmt_promotion->close();
        } else {
            $error_message = "Ошибка подготовки запроса (информация об акции) к базе данных: " . $connect->error;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $promotion ? htmlspecialchars($promotion['title']) : 'Детали акции'; ?> - Название вашего сайта</title>
    <link rel="stylesheet" href="Style/style.css">
    <style>
        .promotion-detail-container { max-width: 800px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9; }
        .promotion-detail-container img.promo-image { max-width: 100%; height: auto; border-radius: 4px; margin-bottom: 15px; }
        .promotion-detail-container .meta-info { font-size: 0.9em; color: #555; margin-bottom: 10px; }
        .promotion-detail-container .meta-info span { margin-right: 15px; padding: 5px 8px; background-color: #eee; border-radius: 4px; }
        .promotion-detail-container .description { line-height: 1.6; }
        .error-message { color: red; text-align: center; padding: 10px; border: 1px dashed red; margin-bottom:15px; background-color: #ffecec; }
        .back-link { display: inline-block; margin-bottom: 20px; padding: 10px 15px; background-color: rgb(160, 210, 180); color: white; text-decoration: none; border-radius: 4px; }
        .back-link:hover { background-color: rgb(174, 239, 200); }
        .promo-books-fullwidth-section { width: 100%; background-color: #f8f9fa; padding: 40px 0; margin-top: 30px; border-top: 1px solid #e0e0e0; border-bottom: 1px solid #e0e0e0; }
        .promo-books-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .promo-books-container h2 { margin-bottom: 30px; font-size: 1.8em; color: #333; text-align: center; }
        .books-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 25px; }
        .book-card-promo { border: 1px solid #e0e0e0; border-radius: 6px; padding: 15px; text-align: center; background-color: #fff; transition: box-shadow 0.3s ease, transform 0.2s ease; display: flex; flex-direction: column; justify-content: space-between; }
        .book-card-promo:hover { box-shadow: 0 6px 12px rgba(0,0,0,0.1); transform: translateY(-3px); }
        .book-card-promo img { max-width: 100%; height: 220px; object-fit: contain; border-radius: 4px; margin-bottom: 10px; }
        .book-card-promo h3 { font-size: 1.1em; margin: 10px 0 5px; color: #333; line-height: 1.3; height: 2.6em; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        .book-card-promo .author { font-size: 0.9em; color: #555; margin-bottom: 10px; height: 1.1em; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .book-card-promo .price { font-size: 1.15em; font-weight: bold; color: #007bff; margin-top: auto; }
        .book-card-promo .price .original-price { text-decoration: line-through; color: #999; font-size: 0.85em; margin-right: 8px; }
        .book-card-promo .price .sale-price { color: #d9534f; }
        .book-card-promo .book-link { text-decoration: none; color: inherit; display: flex; flex-direction: column; height: 100%; }
    </style>
</head>
<body>
    <?php
        // Переменная $connect (из src/config/connect.php) уже находится в области видимости.
        // menu.php использует `global $connect;`, которая получит значение $GLOBALS['db_connection'].
        include 'blocks/menu.php';
    ?>

    <main>
        <div class="promotion-detail-container">
            <a href="Акции.php" class="back-link">← Назад к акциям</a>

            <?php if (!empty($error_message) && !$promotion_data_loaded): // Показываем основную ошибку, если данные акции не загрузились ?>
                <p class="error-message"><?php echo htmlspecialchars(trim($error_message)); ?></p>
            <?php elseif ($promotion): ?>
                <h1><?php echo htmlspecialchars($promotion['title']); ?></h1>

                <img src="<?php echo htmlspecialchars($promotion['image_src']); ?>"
                     class="promo-image"
                     alt="<?php echo htmlspecialchars($promotion['image_alt'] ? $promotion['image_alt'] : $promotion['title']); ?>"
                     <?php if (!empty($promotion['image_srcset'])): ?>
                         srcset="<?php echo htmlspecialchars($promotion['image_srcset']); ?>"
                     <?php endif; ?>>

                <div class="meta-info">
                    <span>Сроки: <?php echo htmlspecialchars($promotion['time_range']); ?></span>
                    <span>Место: <?php echo htmlspecialchars($promotion['location']); ?></span>
                </div>

                <div class="description">
                    <?php echo nl2br(htmlspecialchars($promotion['description'])); ?>
                </div>

                <?php if (!empty($error_message) && $promotion_data_loaded && strpos($error_message, "книг") !== false): // Показываем ошибку, связанную с книгами, если акция загрузилась, а книги - нет ?>
                    <p class="error-message"><?php echo htmlspecialchars(trim($error_message)); ?></p>
                <?php endif; ?>

            <?php elseif (empty($error_message)): // Если нет данных об акции и нет конкретной ошибки (например, ID не был передан)
                 echo '<p class="error-message">Информация об акции не доступна или ID не указан.</p>';
            ?>
            <?php endif; ?>
        </div> <!-- Конец .promotion-detail-container -->

        <!-- Блок с книгами по акции -->
        <?php if ($promotion_data_loaded): // Показываем секцию с книгами, только если детали акции были успешно загружены ?>
            <?php if (!empty($promotion_books)): ?>
                <section class="promo-books-fullwidth-section">
                    <div class="promo-books-container">
                        <h2>Книги, участвующие в акции:</h2>
                        <div class="books-grid">
                            <?php foreach ($promotion_books as $book): ?>
                                <div class="book-card-promo">
                                    <a href="product_detail.php?id=<?php echo $book['id']; ?>" class="book-link">
                                        <img src="images/book/<?php echo htmlspecialchars($book['image']); ?>" alt="<?php echo htmlspecialchars($book['name']); ?>">
                                        <h3><?php echo htmlspecialchars($book['name']); ?></h3>
                                        <p class="author"><?php echo htmlspecialchars($book['autor']); ?></p>
                                        <p class="price">
                                            <?php if (!empty($book['sale_prise']) && $book['sale_prise'] > 0 && $book['sale_prise'] < $book['prise']): ?>
                                                <span class="original-price"><?php echo htmlspecialchars(number_format($book['prise'], 0, ',', ' ')); ?> руб.</span>
                                                <span class="sale-price"><?php echo htmlspecialchars(number_format($book['sale_prise'], 0, ',', ' ')); ?> руб.</span>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars(number_format($book['prise'], 0, ',', ' ')); ?> руб.
                                            <?php endif; ?>
                                        </p>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php elseif (strpos($error_message, "Ошибка загрузки книг") === false): // Если книг нет, но не было ошибки их загрузки (т.е. их просто не привязали к акции) ?>
                <section class="promo-books-fullwidth-section">
                    <div class="promo-books-container">
                        <p style="text-align: center; font-size: 1.1em;">По этой акции пока нет специальных предложений на книги.</p>
                    </div>
                </section>
            <?php endif; ?>
        <?php endif; ?>
        <!-- Конец блока с книгами по акции -->
    </main>

    <?php
    include 'blocks/footer.php'; // Подключаем футер

    // Закрываем глобальное соединение с БД в самом конце выполнения скрипта,
    // если оно было установлено и является объектом mysqli
    if (isset($GLOBALS['db_connection']) && $GLOBALS['db_connection'] instanceof mysqli) {
        $GLOBALS['db_connection']->close();
    }
    ?>
</body>
</html>