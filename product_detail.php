<?php
session_start();
require_once 'src/config/connect.php'; 

$product_id = null;
$product = null;
$error_message = '';

if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $product_id = (int)$_GET['id'];

    if ($connect) {
        $stmt = $connect->prepare("SELECT id, name, autor, description, genre, imprint, prise, sale_prise, quantity, image FROM `Product` WHERE `id` = ?");
        
        if ($stmt === false) {
            error_log("Failed to prepare product query: " . $connect->error);
            $error_message = "Произошла ошибка при загрузке товара. Пожалуйста, попробуйте позже.";
        } else {
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $product = $result->fetch_assoc();
            } else {
                $error_message = "Товар не найден.";
            }
            $stmt->close();
        }
    } else {
        error_log("Database connection not established.");
        $error_message = "Ошибка подключения к базе данных. Пожалуйста, попробуйте позже.";
    }
} else {
    $error_message = "Некорректный ID товара.";
}

// --- КОД ДЛЯ ОТЗЫВОВ (С УЧЕТОМ НОВОЙ СТРУКТУРЫ СЕССИИ) ---
$reviews = [];
$average_rating = 0.0;
$total_reviews = 0;
$product_review_count = 0; 
$product_average_rating = 0.0;

$review_success_message = '';
$review_error_message = '';
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isset($_SESSION['user']['id']) || !isset($_SESSION['user']['name'])) { 
        $_SESSION['review_flash_message'] = "Для отправки отзыва необходимо авторизоваться.";
        $_SESSION['review_flash_type'] = 'error'; 
        $redirect_product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : $product_id; 
        if ($redirect_product_id) {
            $redirect_url = strtok($_SERVER["REQUEST_URI"], '?') . "?id=" . $redirect_product_id . "#review-form-section";
            header("Location: " . $redirect_url);
            exit();
        } else {
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }
    }

    $review_product_id_raw = $_POST['product_id'] ?? null;
    $review_product_id = filter_var($review_product_id_raw, FILTER_VALIDATE_INT);
    
    $author_name = $_SESSION['user']['name']; 
    
    $rating_raw = $_POST['rating'] ?? null;
    $rating = filter_var($rating_raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
    
    $text_raw = $_POST['review_text'] ?? '';
    $text = trim(filter_var($text_raw, FILTER_SANITIZE_STRING));
    
    $user_id = (int)$_SESSION['user']['id']; 

    if (!$review_product_id || ($product && $review_product_id !== $product['id'])) {
        $review_error_message = "Ошибка: неверный товар для отзыва.";
    } elseif ($rating === false) {
        $review_error_message = "Пожалуйста, выберите оценку от 1 до 5.";
    } elseif (empty($text)) {
        $review_error_message = "Пожалуйста, напишите текст отзыва.";
    } elseif (mb_strlen($text) < 10) {
        $review_error_message = "Текст отзыва слишком короткий (минимум 10 символов).";
    } else {
        if ($connect) {
            $is_approved = 1;
            $stmt_insert_review = $connect->prepare("INSERT INTO `Reviews` (product_id, user_id, author_name, rating, text, is_approved) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt_insert_review) {
                $stmt_insert_review->bind_param("iisisi", $review_product_id, $user_id, $author_name, $rating, $text, $is_approved);
                if ($stmt_insert_review->execute()) {
                    $_SESSION['review_flash_message'] = "Спасибо! Ваш отзыв " . ($is_approved ? "опубликован." : "отправлен на модерацию.");
                    $_SESSION['review_flash_type'] = 'success';
                    $redirect_url = strtok($_SERVER["REQUEST_URI"], '?') . "?id=" . $product_id . "#reviews-section";
                    header("Location: " . $redirect_url);
                    exit();
                } else {
                    error_log("Failed to insert review: " . $stmt_insert_review->error);
                    $review_error_message = "Не удалось отправить отзыв. Ошибка БД.";
                }
                $stmt_insert_review->close();
            } else {
                error_log("Failed to prepare insert review query: " . $connect->error);
                $review_error_message = "Ошибка сервера при подготовке запроса.";
            }
        } else {
            $review_error_message = "Ошибка подключения к базе данных.";
        }
    }

    if ($review_error_message) {
        $form_data = $_POST; 
    }
}

if (isset($_SESSION['review_flash_message'])) {
    if ($_SESSION['review_flash_type'] === 'success') {
        $review_success_message = $_SESSION['review_flash_message'];
    } else { 
        $review_error_message = $_SESSION['review_flash_message'];
    }
    unset($_SESSION['review_flash_message']);
    unset($_SESSION['review_flash_type']);
}

if ($product && $connect) {
    $stmt_reviews = $connect->prepare("SELECT author_name, rating, text, created_at FROM `Reviews` WHERE `product_id` = ? AND `is_approved` = 1 ORDER BY created_at DESC");
    if ($stmt_reviews) {
        $stmt_reviews->bind_param("i", $product['id']);
        $stmt_reviews->execute();
        $result_reviews = $stmt_reviews->get_result();
        while ($row = $result_reviews->fetch_assoc()) {
            $reviews[] = $row;
        }
        $stmt_reviews->close();
        
        if (!empty($reviews)) {
            $total_rating_sum = 0;
            foreach ($reviews as $review) {
                $total_rating_sum += (int)$review['rating'];
            }
            $total_reviews = count($reviews);
            if ($total_reviews > 0) {
                $average_rating = round($total_rating_sum / $total_reviews, 1);
            }
        }
    } else {
        error_log("Failed to prepare reviews query: " . $connect->error);
    }
}
$product_review_count = $total_reviews;
$product_average_rating = $average_rating;
// --- КОНЕЦ КОДА ДЛЯ ОТЗЫВОВ ---


$page_title = "Товар не найден";
if ($product && isset($product['name'])) {
    $page_title = "OnlyBooK | " . htmlspecialchars($product['name']);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="Style/style.css?<? echo time()?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Comfortaa:wght@300..700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Style/product_detail_variant1.css?<? echo time()?>"> 
    <!-- 
        Убедитесь, что CSS-стили для отзывов и сообщения об авторизации добавлены 
        в Style/product_detail_variant1.css (они были в предыдущем ответе).
    -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-MD1BSLSZY2"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-MD1BSLSZY2');
    </script>
    <style>
        .modal-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background-color:rgba(0,0,0,.6);z-index:1000;opacity:0;transition:opacity .3s ease-out}
        .modal-overlay.active{display:block;opacity:1}
        .service-modal{display:none;position:fixed;left:50%;top:50%;transform:translate(-50%,-55%);background-color:#fff;padding:25px 30px;border-radius:10px;box-shadow:0 8px 25px rgba(0,0,0,.15);z-index:1001;width:90%;max-width:500px;color:#333;text-align:left;box-sizing:border-box;opacity:0;transition:opacity .3s ease-out,transform .3s ease-out}
        .service-modal.active{display:block;opacity:1;transform:translate(-50%,-50%)}
        .service-modal .modal-content h3{margin-top:0;margin-bottom:15px;font-size:1.3em;color:#34495e;font-family:'Montserrat',sans-serif}
        .service-modal .modal-content p{font-size:.9em;line-height:1.7;margin-bottom:10px;font-family:'Comfortaa',sans-serif}
        .service-modal .modal-content p:last-child{margin-bottom:0}
        .close-modal-btn{position:absolute;top:10px;right:15px;font-size:26px;font-weight:700;color:#95a5a6;cursor:pointer;transition:color .2s ease;line-height:1}
        .close-modal-btn:focus,.close-modal-btn:hover{color:#34495e;text-decoration:none}
        .service-link[data-modal-target]{cursor:pointer}
        body.modal-open{overflow:hidden}
    </style>
</head>
<body>
    <?php 
        if (file_exists('blocks/menu.php')) {
            include 'blocks/menu.php';
        }
    ?>

    <main class="product-detail-page modern-minimalist">
        <div class="container">
            <?php if ($product): ?>
                <div class="product-breadcrumb">
                    <a href="index.php">Главная</a> » 
                    <?php if (isset($product['genre']) && !empty($product['genre'])): ?>
                        <a href="catalog.php?genre=<?= urlencode($product['genre']); ?>"><?= htmlspecialchars($product['genre']); ?></a> »
                    <?php endif; ?>
                    <span><?= htmlspecialchars($product['name']); ?></span>
                </div>

                <div class="product-main-info">
                    <div class="product-gallery">
                        <img src="images/book/<?= htmlspecialchars($product['image']); ?>" alt="<?= htmlspecialchars($product['name']); ?>" class="main-product-image">
                    </div>
                    <div class="product-summary">
                        <h1 class="product-title"><?= htmlspecialchars($product['name']); ?></h1>
                        <div class="product-subtitle-info">
                            <p class="product-author">Автор: <a href="catalog.php?author=<?= urlencode($product['autor']); ?>"><?= htmlspecialchars($product['autor']); ?></a></p>
                            <div class="product-rating-reviews">
                                <?php if ($product_review_count > 0): ?>
                                    <span class="stars" title="Рейтинг: <?= number_format($product_average_rating, 1) ?> из 5">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= round($product_average_rating)): ?>★<?php else: ?>☆<?php endif; ?>
                                        <?php endfor; ?>
                                    </span> 
                                    <a href="#reviews-section" class="review-count-link" onclick="openTabAndScroll(event, 'reviews-section', 'reviews-section'); return false;">
                                        (<span class="review-count"><?= $product_review_count ?></span> <?= ($product_review_count % 10 == 1 && $product_review_count % 100 != 11 ? 'отзыв' : (($product_review_count % 10 >= 2 && $product_review_count % 10 <= 4 && ($product_review_count % 100 < 10 || $product_review_count % 100 >= 20)) ? 'отзыва' : 'отзывов')) ?>)
                                    </a>
                                <?php else: ?>
                                    <span class="stars">☆☆☆☆☆</span>
                                    <a href="#reviews-section" class="review-count-link" onclick="openTabAndScroll(event, 'reviews-section', 'review-form-section'); return false;">(Оставить первый отзыв)</a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="product-meta-short">
                            <?php if (isset($product['genre']) && !empty($product['genre'])): ?>
                                <span class="meta-item genre-tag"><i class="fa fa-tag"></i> <?= htmlspecialchars($product['genre']); ?></span>
                            <?php endif; ?>
                            <?php if (isset($product['imprint']) && !empty($product['imprint'])): ?>
                                <span class="meta-item publisher-tag"><i class="fa fa-building-o"></i> <?= htmlspecialchars($product['imprint']); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="product-price-action-wrapper">
                            <div class="price-block">
                                <?php if (isset($product['sale_prise']) && (float)$product['sale_prise'] != 0 && (float)$product['sale_prise'] < (float)$product['prise']): ?>
                                    <span class="current-price sale"><?= htmlspecialchars(number_format((float)$product['sale_prise'], 0, '.', ' ')); ?> ₽</span>
                                    <span class="original-price"><?= htmlspecialchars(number_format((float)$product['prise'], 0, '.', ' ')); ?> ₽</span>
                                    <?php
                                        $discount_percentage = 0;
                                        if ((float)$product['prise'] > 0) {
                                            $discount_percentage = round((( (float)$product['prise'] - (float)$product['sale_prise'] ) / (float)$product['prise']) * 100);
                                        }
                                    ?>
                                    <?php if ($discount_percentage > 0): ?>
                                        <span class="discount-badge">-<?= $discount_percentage; ?>%</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="current-price"><?= htmlspecialchars(number_format((float)$product['prise'], 0, '.', ' ')); ?> ₽</span>
                                <?php endif; ?>
                            </div>
                            
                            <form method="post" action="src/actions/add_to_card.php" class="add-to-cart-form">
                                <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id']); ?>">
                                <?php if ((int)$product['quantity'] > 0): ?>
                                    <p class="availability-status positive"><i class="fa fa-check-circle"></i> В наличии (<?= htmlspecialchars($product['quantity']); ?> шт.)</p>
                                    <div class="actions-row">
                                        <div class="quantity-selector">
                                            <label for="quantity_<?= $product['id'] ?>" class="sr-only">Количество:</label>
                                            <button type="button" class="quantity-btn minus" aria-label="Уменьшить количество">-</button>
                                            <input type="number" id="quantity_<?= $product['id'] ?>" name="quantity" value="1" min="1" max="<?= (int)$product['quantity'] ?>" class="quantity-input" readonly>
                                            <button type="button" class="quantity-btn plus" aria-label="Увеличить количество">+</button>
                                        </div>
                                        <button type="submit" class="add-to-cart-btn"><i class="fa fa-shopping-cart"></i> В корзину</button>
                                    </div>
                                    <button type="button" class="wishlist-btn" title="Добавить в избранное"><i class="fa fa-heart-o"></i> <span>В избранное</span></button>
                                <?php else: ?>
                                    <p class="availability-status negative"><i class="fa fa-times-circle"></i> Нет в наличии</p>
                                    <button type="button" class="add-to-cart-btn disabled" disabled>Нет в наличии</button>
                                    <button type="button" class="wishlist-btn disabled" title="Добавить в избранное" disabled><i class="fa fa-heart-o"></i> <span>В избранное</span></button>
                                <?php endif; ?>
                            </form>
                        </div>

                        <div class="product-service-info">
                             <div class="service-item">
                                <div class="service-item-content">
                                    <i class="fa fa-truck"></i>
                                    <span class="service-item-text">Быстрая доставка по РФ</span>
                                </div>
                                <a href="#" class="service-link-details" data-modal-target="modal-delivery">Подробнее</a>
                                <div id="modal-delivery" class="service-modal">
                                    <div class="modal-content">
                                        <span class="close-modal-btn" title="Закрыть">×</span>
                                        <h3>Быстрая доставка по РФ</h3>
                                        <p>Мы предлагаем быструю и надежную доставку ваших заказов по всей территории Российской Федерации. Сроки доставки зависят от вашего региона и выбранного способа доставки (например, Почта России, СДЭК, курьерская служба). Обычно это занимает от 2 до 10 рабочих дней.</p>
                                        <p>Вы можете отслеживать статус вашего заказа в личном кабинете после его отправки. Стоимость доставки рассчитывается автоматически при оформлении заказа.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="service-item">
                                <div class="service-item-content">
                                    <i class="fa fa-credit-card"></i>
                                    <span class="service-item-text">Удобные способы оплаты</span>
                                </div>
                                <a href="#" class="service-link-details" data-modal-target="modal-payment">Подробнее</a>
                                <div id="modal-payment" class="service-modal">
                                    <div class="modal-content">
                                        <span class="close-modal-btn" title="Закрыть">×</span>
                                        <h3>Удобные способы оплаты</h3>
                                        <p>Оплачивайте ваши покупки любым удобным для вас способом: банковской картой (Visa, MasterCard, МИР) онлайн на сайте, через системы электронных платежей (например, ЮMoney, QIWI) или наличными при получении (если доступна опция наложенного платежа для вашего региона).</p>
                                        <p>Все онлайн-платежи защищены и безопасны.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="service-item">
                                <div class="service-item-content">
                                    <i class="fa fa-undo"></i>
                                    <span class="service-item-text">Гарантия возврата</span>
                                </div>
                                <a href="#" class="service-link-details" data-modal-target="modal-returns">Подробнее</a>
                                <div id="modal-returns" class="service-modal">
                                    <div class="modal-content">
                                        <span class="close-modal-btn" title="Закрыть">×</span>
                                        <h3>Гарантия возврата</h3>
                                        <p>Мы гарантируем возможность возврата или обмена товара в течение 14 дней с момента получения заказа, если книга не подошла вам по каким-либо причинам (при условии сохранения товарного вида и отсутствия следов использования).</p>
                                        <p>Пожалуйста, ознакомьтесь с полной политикой возврата на соответствующей странице нашего сайта или свяжитесь с нашей службой поддержки для получения подробной информации.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="product-details-extended">
                    <div class="tabs">
                        <button class="tab-link active" onclick="openTab(event, 'description')">Описание</button>
                        <button class="tab-link" onclick="openTab(event, 'specs')">Характеристики</button>
                        <button class="tab-link" onclick="openTab(event, 'reviews-section')">Отзывы (<span id="reviews-tab-count"><?= $product_review_count ?></span>)</button>
                    </div>

                    <div id="description" class="tab-content active">
                        <h2>Описание</h2>
                        <?php if (isset($product['description']) && !empty($product['description'])): ?>
                            <p><?= nl2br(htmlspecialchars($product['description'])); ?></p>
                        <?php else: ?>
                            <p>Описание для этого товара пока не добавлено.</p>
                        <?php endif; ?>
                    </div>

                    <div id="specs" class="tab-content">
                        <h2>Характеристики</h2>
                        <ul>
                            <li><strong>Автор:</strong> <?= htmlspecialchars($product['autor']); ?></li>
                            <li><strong>Жанр:</strong> <?= htmlspecialchars($product['genre']); ?></li>
                            <li><strong>Издательство:</strong> <?= htmlspecialchars($product['imprint']); ?></li>
                            <li><strong>В наличии:</strong> <?= (int)$product['quantity'] > 0 ? htmlspecialchars($product['quantity']) . ' шт.' : 'Нет'; ?></li>
                        </ul>
                    </div>

                    <div id="reviews-section" class="tab-content">
                        <h2>Отзывы о товаре "<?= htmlspecialchars($product['name']); ?>" (<span id="reviews-main-count"><?= $product_review_count ?></span>)</h2>
                        
                        <div class="reviews-summary-actions">
                            <?php if ($product_review_count > 0): ?>
                                <div class="average-rating-display">
                                    Средняя оценка: <strong><?= number_format($product_average_rating, 1) ?></strong> из 5
                                    <div class="stars-display big-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= round($product_average_rating)): ?>★<?php else: ?>☆<?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <button class="btn-cta btn-write-review" onclick="document.getElementById('review-form-section').scrollIntoView({ behavior: 'smooth' }); if(document.getElementById('review_text')) {document.getElementById('review_text').focus();} ">Написать отзыв</button>
                        </div>

                        <?php if (!empty($review_success_message)): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($review_success_message); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($review_error_message)): ?>
                            <div class="alert alert-danger">
                                <strong>Ошибка:</strong> <?= htmlspecialchars($review_error_message); ?>
                            </div>
                        <?php endif; ?>

                        <div class="reviews-list">
                            <?php if (!empty($reviews)): ?>
                                <?php foreach ($reviews as $review): ?>
                                    <div class="review-item">
                                        <div class="review-header">
                                            <strong class="review-author"><?= htmlspecialchars($review['author_name']); ?></strong>
                                            <span class="review-date"><?= date("d.m.Y в H:i", strtotime($review['created_at'])); ?></span>
                                        </div>
                                        <div class="review-rating stars-display">
                                            <?php for ($s_i = 1; $s_i <= 5; $s_i++): ?>
                                                <?php if ($s_i <= (int)$review['rating']): ?>★<?php else: ?>☆<?php endif; ?>
                                            <?php endfor; ?>
                                        </div>
                                        <div class="review-text">
                                            <p><?= nl2br(htmlspecialchars($review['text'])); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="no-reviews-yet">Отзывов об этом товаре пока нет. Будьте первым!</p>
                            <?php endif; ?>
                        </div>

                        <div id="review-form-section" class="review-form-container">
                            <?php 
                            $is_user_logged_in = isset($_SESSION['user']['id']) && isset($_SESSION['user']['name']);
                            ?>

                            <?php if ($is_user_logged_in): ?>
                                <h3>Оставить свой отзыв</h3>
                                <form method="post" action="<?= htmlspecialchars($_SERVER['REQUEST_URI']); ?>#review-form-section" class="styled-form">
                                    <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id']); ?>">
                                    <p class="form-static-text">Вы оставляете отзыв как: <strong><?= htmlspecialchars($_SESSION['user']['name']); ?></strong></p>

                                    <div class="form-group">
                                        <label>Ваша оценка <span class="required">*</span>:</label>
                                        <div class="rating-stars-input">
                                            <?php for ($r_i = 5; $r_i >= 1; $r_i--): 
                                                $checked_star = '';
                                                if (isset($form_data['rating']) && (int)$form_data['rating'] === $r_i) {
                                                    $checked_star = 'checked';
                                                }
                                            ?>
                                                <input type="radio" id="star<?= $r_i ?>" name="rating" value="<?= $r_i ?>" required <?= $checked_star ?>><label for="star<?= $r_i ?>" title="<?= $r_i ?> <?= ($r_i % 10 == 1 && $r_i != 11) ? 'звезда' : (($r_i % 10 >=2 && $r_i % 10 <=4 && ($r_i<10 || $r_i>20)) ? 'звезды' : 'звезд') ?>">☆</label>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="review_text">Текст отзыва (минимум 10 символов) <span class="required">*</span>:</label>
                                        <textarea id="review_text" name="review_text" rows="5" required minlength="10"><?= isset($form_data['review_text']) ? htmlspecialchars($form_data['review_text']) : '' ?></textarea>
                                    </div>
                                    <button type="submit" name="submit_review" class="btn-cta btn-submit-review">Отправить отзыв</button>
                                </form>
                            <?php else: ?>
                                <h3>Оставить свой отзыв</h3>
                                <div class="auth-required-message">
                                    <p>Пожалуйста, <a href="login.php">войдите</a> или <a href="register.php">зарегистрируйтесь</a>, чтобы оставить отзыв.</p>
                                    <p><i class="fa fa-info-circle"></i> Только зарегистрированные пользователи могут оставлять отзывы о товарах.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            <?php elseif ($error_message): ?>
                <div class="error-message">
                    <p><?= htmlspecialchars($error_message); ?></p>
                    <p><a href="index.php" class="button-like">Вернуться на главную</a></p>
                </div>
            <?php else: ?>
                 <div class="error-message">
                    <p><?= htmlspecialchars($error_message ?: "Запрошенный товар не найден или указан неверный ID."); ?></p>
                    <p><a href="index.php" class="button-like">Вернуться на главную</a></p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php 
        if (file_exists('blocks/footer.php')) {
            include 'blocks/footer.php';
        }
    ?>

    <div id="modal-overlay" class="modal-overlay"></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // --- QUANTITY SELECTOR ---
    document.querySelectorAll('.quantity-selector').forEach(selector => {
        const input = selector.querySelector('.quantity-input');
        const btnMinus = selector.querySelector('.quantity-btn.minus');
        const btnPlus = selector.querySelector('.quantity-btn.plus');
        if (!input || !btnMinus || !btnPlus) return;
        const maxQuantity = parseInt(input.max);
        btnMinus.addEventListener('click', () => { if (parseInt(input.value) > 1) input.value = parseInt(input.value) - 1; });
        btnPlus.addEventListener('click', () => { if (parseInt(input.value) < maxQuantity) input.value = parseInt(input.value) + 1; });
        if (input.readOnly) input.addEventListener('keydown', (e) => e.preventDefault());
    });

    // --- WISHLIST BUTTON ---
    document.querySelectorAll('.wishlist-btn:not(.disabled)').forEach(button => {
        button.addEventListener('click', function() {
            this.classList.toggle('active');
            const textSpan = this.querySelector('span');
            if(textSpan) textSpan.textContent = this.classList.contains('active') ? 'В избранном' : 'В избранное';
        });
    });

    // --- TABS ---
    window.openTab = function(evt, tabName) {
        document.querySelectorAll(".tab-content").forEach(tc => { 
            tc.style.display = "none"; 
            tc.classList.remove("active"); 
        });
        document.querySelectorAll(".tab-link").forEach(tl => { 
            tl.classList.remove("active"); 
        });
        const tabToOpen = document.getElementById(tabName);
        if (tabToOpen) { 
            tabToOpen.style.display = "block"; 
            tabToOpen.classList.add("active"); 
        }
        if (evt && evt.currentTarget) { 
            evt.currentTarget.classList.add("active");
        } else { 
            const tabButton = document.querySelector(`.tab-link[onclick*="'${tabName}'"]`);
            if (tabButton) {
                tabButton.classList.add("active");
            }
        }
    }
    
    window.openTabAndScroll = function(evt, tabName, scrollTargetId) {
        openTab(evt, tabName); 
        setTimeout(() => {
            const scrollTarget = document.getElementById(scrollTargetId);
            if (scrollTarget) {
                scrollTarget.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 50); 
    }

    const activeTabLink = document.querySelector(".tab-link.active");
    const firstTabLink = document.querySelector(".tab-link");
    let initialTabToOpen = null;
    if (activeTabLink) {
        const onclickAttribute = activeTabLink.getAttribute('onclick');
        if (onclickAttribute) {
            const match = onclickAttribute.match(/openTab(?:AndScroll)?\s*\(\s*event\s*,\s*'([^']+)'/);
            if (match && match[1]) initialTabToOpen = match[1];
        }
    }
    if (!initialTabToOpen && firstTabLink) {
        const firstTabOnclick = firstTabLink.getAttribute('onclick');
        const firstMatch = firstTabOnclick.match(/openTab(?:AndScroll)?\s*\(\s*event\s*,\s*'([^']+)'/);
        if (firstMatch && firstMatch[1]) initialTabToOpen = firstMatch[1];
    }
    if (initialTabToOpen) {
        openTab(null, initialTabToOpen);
    } else {
        const firstTabContent = document.querySelector('.tab-content.active') || document.querySelector('.tab-content');
        if (firstTabContent && firstTabContent.id) { 
             openTab(null, firstTabContent.id);
        } else if (firstTabContent) {
            firstTabContent.style.display = "block"; 
            firstTabContent.classList.add("active");
        }
    }

    // --- SERVICE MODALS ---
    const serviceInfoBlock = document.querySelector('.product-service-info');
    const overlay = document.getElementById('modal-overlay');
    const allServiceModals = document.querySelectorAll('.service-modal'); 
    if (!overlay) { console.error("Modal overlay ('#modal-overlay') not found. Modals will not work."); }
    if (serviceInfoBlock && overlay) { 
        serviceInfoBlock.addEventListener('click', function(event) {
            const trigger = event.target.closest('.service-link-details[data-modal-target]'); 
            if (trigger) {
                event.preventDefault(); 
                const modalId = trigger.dataset.modalTarget;
                const modalToOpen = document.getElementById(modalId);
                if (modalToOpen) {
                    allServiceModals.forEach(m => {
                        if (m.id !== modalId && m.classList.contains('active')) { m.classList.remove('active'); }
                    });
                    modalToOpen.classList.add('active');
                    overlay.classList.add('active');
                    document.body.classList.add('modal-open'); 
                } else { console.error("Modal content element with ID '" + modalId + "' not found."); }
            }
        });
    } else { if (!serviceInfoBlock) console.warn("Service info block ('.product-service-info') not found."); }
    function closeAllOpenServiceModals() {
        let modalWasClosed = false;
        allServiceModals.forEach(modal => {
            if (modal.classList.contains('active')) { modal.classList.remove('active'); modalWasClosed = true; }
        });
        if (overlay && overlay.classList.contains('active')) { overlay.classList.remove('active'); modalWasClosed = true; }
        if (modalWasClosed) { document.body.classList.remove('modal-open'); }
    }
    document.querySelectorAll('.service-modal .close-modal-btn').forEach(button => {
        button.addEventListener('click', closeAllOpenServiceModals);
    });
    if (overlay) {
        overlay.addEventListener('click', (e) => { if (e.target === overlay) { closeAllOpenServiceModals(); } });
    }
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && Array.from(allServiceModals).some(m => m.classList.contains('active'))) {
            closeAllOpenServiceModals();
        }
    });

    // --- АКТИВАЦИЯ ВКЛАДКИ "ОТЗЫВЫ" И СКРОЛЛ ПРИ НАЛИЧИИ ЯКОРЯ ИЛИ СООБЩЕНИЯ ---
    const urlHash = window.location.hash;
    if (urlHash === '#reviews-section' || urlHash === '#review-form-section') {
        const targetElementId = (urlHash === '#review-form-section') ? 'review-form-section' : 'reviews-section';
        openTabAndScroll(null, 'reviews-section', targetElementId);
    } else {
        const reviewAlert = document.querySelector('#reviews-section .alert');
        if (reviewAlert) {
            const reviewsTabContent = document.getElementById('reviews-section');
            if (reviewsTabContent && !reviewsTabContent.classList.contains('active')) {
                 openTabAndScroll(null, 'reviews-section', 'review-form-section');
            } else if (reviewsTabContent && reviewsTabContent.classList.contains('active')) {
                const formSection = document.getElementById('review-form-section');
                // Проверяем, что алерт видим (не скрыт) и является частью секции формы или сразу после списка отзывов
                const isAlertVisible = !!( reviewAlert.offsetWidth || reviewAlert.offsetHeight || reviewAlert.getClientRects().length );
                if (isAlertVisible) {
                    if (formSection && (reviewAlert.parentElement.id === 'review-form-section' || reviewAlert.closest('#review-form-section'))) {
                         formSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    } else if (reviewAlert.previousElementSibling && reviewAlert.previousElementSibling.classList.contains('reviews-list') ) {
                        reviewAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    } else { // Общий случай, если алерт просто на вкладке отзывов
                        reviewAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            }
        }
    }
    
    console.log("All page scripts initialized.");
});
</script>
</body>
</html>