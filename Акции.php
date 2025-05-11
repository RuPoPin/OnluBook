<?php
// 1. ЗАПУСК СЕССИИ И ДРУГАЯ НАЧАЛЬНАЯ PHP-ЛОГИКА (БЕЗ ВЫВОДА HTML)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ini_set('display_errors', 1); // Настройки ошибок
// error_reporting(E_ALL);

// 2. ПОДКЛЮЧЕНИЕ К БАЗЕ ДАННЫХ (БЕЗ ВЫВОДА HTML)
$connect_file_path_db = 'src/config/connect.php';
$critical_db_error_message = '';
$connect = null; // Инициализация
if (file_exists($connect_file_path_db)) {
    include($connect_file_path_db); // Предполагаем, что connect.php создает $connect

    $db_host_debug = isset($db_host) ? htmlspecialchars($db_host) : '[db_host не определен]';
    $db_user_debug = isset($db_user) ? htmlspecialchars($db_user) : '[db_user не определен]';
    $db_name_debug = isset($db_name) ? htmlspecialchars($db_name) : '[db_name не определен]';

    if (!isset($connect) || !($connect instanceof mysqli)) {
        $critical_db_error_message .= "<p>Ошибка: Объект \$connect не создан.</p>";
    } elseif ($connect->connect_error) {
        $critical_db_error_message .= "<p>Ошибка подключения: " . htmlspecialchars($connect->connect_error) . "</p>";
    } elseif (!$connect->set_charset("utf8mb4")) {
        $critical_db_error_message .= "<p>Ошибка кодировки: " . htmlspecialchars($connect->error) . "</p>";
    }
} else {
    $critical_db_error_message .= "<p>Критическая ошибка: Файл '$connect_file_path_db' не найден.</p>";
}

// 3. ЗАПРОС АКЦИЙ (БЕЗ ВЫВОДА HTML)
$result = null;
$promotions_data = []; // Массив для данных акций

if (empty($critical_db_error_message) && isset($connect) && $connect instanceof mysqli && !$connect->connect_error) {
    $sql = "SELECT id, image_src, image_alt, image_srcset, time_range, location, title, description, active
            FROM promotions
            WHERE active = 1
            ORDER BY id DESC";
    $query_result = $connect->query($sql);

    if (!$query_result) {
        $critical_db_error_message .= "<p>Ошибка SQL: " . htmlspecialchars($connect->error) . "</p>";
    } elseif ($query_result instanceof mysqli_result) {
        if ($query_result->num_rows > 0) {
            while ($row = $query_result->fetch_assoc()) {
                $promotions_data[] = $row; // Собираем данные
            }
        }
        $query_result->free(); // Освобождаем результат сразу
    } else {
         $critical_db_error_message .= "<p>Ошибка: query() не вернул mysqli_result.</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Акции</title>
    <link rel="stylesheet" href="Style/style.css?<? echo time()?>">
    <style>
        .content { display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 25px; }
    </style>
</head>
<body>
    <?php
    // 4. ПОДКЛЮЧЕНИЕ МЕНЮ (теперь $_SESSION доступна, $connect тоже)
    $menu_file_path = 'blocks/menu.php';
    if (file_exists($menu_file_path)) {
        include($menu_file_path); // menu.php теперь не содержит session_start()
    } else {
        echo "\n<p style='color:orange;'>ПРЕДУПРЕЖДЕНИЕ: Файл меню '$menu_file_path' не найден.</p>";
    }
    ?>
    <main>
    <section class="promotions-section">
        <h1 class="block1 promotions-section__title">Специальные предложения и Акции</h1>
        <p class="promotions-section__subtitle">Не упустите выгодные условия и эксклюзивные новинки нашего магазина!</p>

        <?php
        if (!empty($critical_db_error_message)) {
            echo '<div class="error-message">' . $critical_db_error_message . '</div>';
        } elseif (empty($promotions_data)) {
            echo "<div class='info-message promotions-section__no-promotions'>
                      <img src='images/icons/sad-box.svg' alt='Пустая коробка' class='info-message__icon'>
                      <h3 class='info-message__title'>Упс, акции закончились!</h3>
                      <p class='info-message__text'>На данный момент активных акций нет, но мы уже готовим что-то интересное. Загляните позже!</p>
                      <a href='index.php' class='btn btn-primary'>Вернуться на главную</a>
                  </div>";
        } else {
        ?>
            <div class="promotions-grid">
                <?php foreach ($promotions_data as $promo):
                    // Используем ?? для значений по умолчанию, если ключи могут отсутствовать
                    $promo_id = htmlspecialchars($promo["id"] ?? '');
                    $detail_page_url = "promotion_detail.php?id=" . urlencode($promo_id); // или ЧПУ: "promotions/" . htmlspecialchars($promo["detail_page_slug"] ?? $promo_id);
                    $image_path = htmlspecialchars($promo["image_src"] ?? 'images/placeholder-promo.png'); // Путь к плейсхолдеру, если нет изображения
                    $image_alt = htmlspecialchars($promo["image_alt"] ?? $promo["title"] ?? 'Изображение акции');
                    $promo_type = htmlspecialchars($promo["promotion_type"] ?? 'Акция');
                    $promo_title = htmlspecialchars($promo["title"] ?? 'Без названия');
                    $promo_description_full = strip_tags($promo["description"] ?? '');
                    $promo_description_short = mb_strlen($promo_description_full) > 120 ? mb_substr($promo_description_full, 0, 117) . "..." : $promo_description_full;
                    $time_range = htmlspecialchars($promo["time_range"] ?? 'Сроки не указаны');
                ?>
                    <article class="promo-card">
                        <a href="<?php echo $detail_page_url; ?>" class="promo-card__link-overlay" aria-label="Подробнее об акции <?php echo $promo_title; ?>"></a>
                        <div class="promo-card__image-container">
                            <img src="<?php echo $image_path; ?>" alt="<?php echo $image_alt; ?>" class="promo-card__image" loading="lazy" 
                                 onerror="this.onerror=null; this.src='images/placeholder-promo-error.png';">
                            <?php if (!empty($promo_type)): ?>
                                <span class="promo-card__type-badge"><?php echo $promo_type; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="promo-card__content">
                            <h3 class="promo-card__title">
                                <a href="<?php echo $detail_page_url; ?>"><?php echo $promo_title; ?></a>
                            </h3>
                            <p class="promo-card__description"><?php echo nl2br(htmlspecialchars($promo_description_short)); ?></p>
                            <div class="promo-card__footer">
                                <span class="promo-card__time-range">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" />
                                    </svg>
                                    <?php echo $time_range; ?>
                                </span>
                                <a href="<?php echo $detail_page_url; ?>" class="btn btn-secondary promo-card__details-btn">Подробнее</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php
        } // конец if/else отображения акций
        ?>
    </section>
</main>
ы
    <?php
    $footer_file_path = 'blocks/footer.php';
    if (file_exists($footer_file_path)) {
        include $footer_file_path;
    }
    // Закрываем соединение с БД здесь, если оно еще открыто
    if (isset($connect) && $connect instanceof mysqli && empty($critical_db_error_message_footer_check)) { // Убедитесь, что футер не нуждается в открытом соединении, или обработайте это
        $connect->close();
    }
    ?>
</body>
</html>