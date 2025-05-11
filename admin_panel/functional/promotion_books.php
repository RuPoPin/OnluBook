<?php

session_start();
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header("Location: login.php"); exit;
// }
// include('../../src/config/connect.php'); // Предполагаю, что это ваш основной коннект
$mysqli = new mysqli('localhost', 'root', '', 'users'); // Используем локальный для примера
if ($mysqli->connect_error) {
    die("Ошибка подключения: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");

if (!isset($_GET['promo_id']) || !filter_var($_GET['promo_id'], FILTER_VALIDATE_INT)) {
    $_SESSION['message'] = "Некорректный ID акции.";
    $_SESSION['message_type'] = "error";
    header("Location: promotions_manage.php");
    exit;
}
$promotion_id = (int)$_GET['promo_id'];

// Получаем информацию об акции
$promo_info_sql = "SELECT id, title FROM promotions WHERE id = ?";
$stmt_promo = $mysqli->prepare($promo_info_sql);
$stmt_promo->bind_param("i", $promotion_id);
$stmt_promo->execute();
$promo_result = $stmt_promo->get_result();
if ($promo_result->num_rows === 0) {
    $_SESSION['message'] = "Акция не найдена.";
    $_SESSION['message_type'] = "error";
    header("Location: promotions_manage.php");
    exit;
}
$promotion_title = $promo_result->fetch_assoc()['title'];
$stmt_promo->close();

// Получаем книги, уже привязанные к акции
$linked_books = [];
$linked_sql = "SELECT p.id, p.name, p.autor, p.image
               FROM Product p
               JOIN promotion_products pp ON p.id = pp.product_id
               WHERE pp.promotion_id = ?";
if ($stmt_linked = $mysqli->prepare($linked_sql)) {
    $stmt_linked->bind_param("i", $promotion_id);
    $stmt_linked->execute();
    $result_linked = $stmt_linked->get_result();
    while ($row = $result_linked->fetch_assoc()) {
        $linked_books[] = $row;
    }
    $stmt_linked->close();
}

// Получаем ИЗНАЧАЛЬНЫЙ список всех книг, которые еще НЕ привязаны к этой акции
// Он будет загружен при первой загрузке страницы
$initial_available_books = [];
$available_sql_initial = "SELECT id, name, autor FROM Product
                          WHERE id NOT IN (SELECT product_id FROM promotion_products WHERE promotion_id = ?)
                          ORDER BY name ASC";
if ($stmt_available_initial = $mysqli->prepare($available_sql_initial)) {
    $stmt_available_initial->bind_param("i", $promotion_id);
    $stmt_available_initial->execute();
    $result_available_initial = $stmt_available_initial->get_result();
    while ($row = $result_available_initial->fetch_assoc()) {
        $initial_available_books[] = $row;
    }
    $stmt_available_initial->close();
}
// $mysqli->close(); // Закроем соединение в конце скрипта или в ajax-обработчике
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление книгами для акции: <?php echo htmlspecialchars($promotion_title); ?></title>
    <link rel="stylesheet" href="style.css"> <!-- Убедитесь, что путь к style.css правильный -->
    <style>
        body { font-family: sans-serif; margin: 20px; background-color: #f4f4f4; color: #333; }
        header { background-color: #fff; padding: 15px; border-bottom: 1px solid #ddd; margin-bottom: 20px; }
        header h1 { margin: 0; font-size: 1.8em; }
        header a { color: #007bff; text-decoration: none; }
        header a:hover { text-decoration: underline; }
        main { background-color: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .container { display: flex; justify-content: space-between; gap: 20px; }
        .column { flex: 1; border: 1px solid #eee; padding: 15px; border-radius: 5px; background-color: #fdfdfd; }
        .column h3 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
        .book-item { display: flex; align-items: center; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
        .book-item:last-child { border-bottom: none; }
        .book-item img { width: 50px; height: 70px; margin-right: 10px; object-fit: cover; border: 1px solid #eee; }
        .book-info { flex-grow: 1; }
        .book-info strong { display: block; font-size: 0.9em; }
        .book-info span { font-size: 0.8em; color: #666; }
        .action-button {
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9em;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        .add-btn { background-color: #28a745; }
        .add-btn:hover { background-color: #218838; }
        .remove-btn { background-color: #dc3545; }
        .remove-btn:hover { background-color: #c82333; }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        select[name="book_id_to_add"],
        input[type="text"].search-input {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        input[type="text"].search-input { margin-bottom: 5px; }
        #loading-indicator { display: none; margin-left: 10px; font-style: italic; color: #555; font-size: 0.9em; }
        .search-wrapper { position: relative; }
        .search-wrapper #loading-indicator { position: absolute; right: 10px; top: 50%; transform: translateY(-70%); }
    </style>
</head>
<body>
    <header>
        <h1>Управление книгами для акции: "<?php echo htmlspecialchars($promotion_title); ?>"</h1>
        <p><a href="promotions_manage.php">Назад к списку акций</a></p>
    </header>
    <main>
        <?php if (isset($_SESSION['message'])): ?>
            <p class="message <?php echo isset($_SESSION['message_type']) ? htmlspecialchars($_SESSION['message_type']) : ''; ?>">
                <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
            </p>
        <?php endif; ?>
<div class="container">
        <div class="column">
            <h3>Привязанные книги (<?php echo count($linked_books); ?>)</h3>
            <?php if (!empty($linked_books)): ?>
                <?php foreach ($linked_books as $book): ?>
                    <div class="book-item">
                        <img src="../../images/book/<?php echo htmlspecialchars(trim($book['image'], '/')); ?>" alt="<?php echo htmlspecialchars($book['name']); ?>">
                        <div class="book-info">
                            <strong><?php echo htmlspecialchars($book['name']); ?></strong>
                            <span><?php echo htmlspecialchars($book['autor']); ?> (ID: <?php echo $book['id']; ?>)</span>
                            <?var_dump(htmlspecialchars($book['image']));?>
                        </div>
                        <a href="src/process_promotion.php?action=unlink_book&promo_id=<?php echo $promotion_id; ?>&book_id=<?php echo $book['id']; ?>" class="action-button remove-btn" onclick="return confirm('Отвязать эту книгу от акции?');">Отвязать</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>К этой акции пока не привязано ни одной книги.</p>
            <?php endif; ?>
        </div>

        <div class="column">
            <h3>Доступные книги для добавления (<span id="available_books_count"><?php echo count($initial_available_books); ?></span>)</h3>
            <form action="src/process_promotion.php" method="POST" id="addBookForm">
                <input type="hidden" name="action" value="link_book">
                <input type="hidden" name="promotion_id" value="<?php echo $promotion_id; ?>">

                <div class="form-group">
                    <label for="search_available_book">Поиск по названию/автору:</label>
                    <div class="search-wrapper">
                        <input type="text" id="search_available_book" class="search-input" placeholder="Введите название или автора..." autocomplete="off">
                        <span id="loading-indicator">Загрузка...</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="book_id_to_add">Выберите книгу:</label>
                    <select name="book_id_to_add" id="book_id_to_add" required>
                        <option value="">-- Выберите книгу --</option>
                        <?php if (!empty($initial_available_books)): ?>
                            <?php foreach ($initial_available_books as $book): ?>
                                <option value="<?php echo $book['id']; ?>">
                                    <?php echo htmlspecialchars($book['name']); ?> (<?php echo htmlspecialchars($book['autor']); ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p id="no_books_found_message" style="display:none; color: #777; font-size: 0.9em;">Книги не найдены по вашему запросу.</p>
                </div>
                <button type="submit" class="action-button add-btn" id="addBookButton">Привязать книгу</button>
            </form>
             <p id="initial_no_books_message" style="<?php echo (empty($initial_available_books) && count($initial_available_books) == 0) ? 'display:block;' : 'display:none;'; ?> color: #777; font-size: 0.9em;">
                Нет доступных книг для добавления к этой акции.
            </p>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search_available_book');
    const bookSelect = document.getElementById('book_id_to_add');
    const noBooksFoundMessage = document.getElementById('no_books_found_message');
    const initialNoBooksMessage = document.getElementById('initial_no_books_message');
    const availableBooksCountSpan = document.getElementById('available_books_count');
    const loadingIndicator = document.getElementById('loading-indicator');
    const addBookButton = document.getElementById('addBookButton');

    const promotionId = <?php echo $promotion_id; ?>;
    let searchTimeout;

    // Путь к AJAX обработчику. Убедитесь, что он правильный!
    // Если promotion_books.php и ajax_search_available_books.php в одной папке:
    const ajaxUrl = 'src/ajax_search_available_books.php';
    // Если promotion_books.php в admin/, а ajax_search_available_books.php в корне:
    // const ajaxUrl = '../ajax_search_available_books.php';


    function updateBookList(books, searchTerm) {
        // Очищаем select, сохраняя первую опцию (плейсхолдер)
        while (bookSelect.options.length > 1) {
            bookSelect.remove(1);
        }
        bookSelect.value = ""; // Сбросить выбор

        if (books && books.length > 0) {
            books.forEach(book => {
                const option = document.createElement('option');
                option.value = book.id;
                option.textContent = `${book.name} (${book.autor})`;
                bookSelect.appendChild(option);
            });
            noBooksFoundMessage.style.display = 'none';
            bookSelect.disabled = false;
            addBookButton.disabled = false;
            initialNoBooksMessage.style.display = 'none';
        } else {
            // Если сервер вернул пустой массив или books === null
            if (searchTerm && searchTerm.trim() !== "") { // Был поисковой запрос
                 noBooksFoundMessage.style.display = 'block';
            } else { // Поискового запроса не было (например, при первоначальной загрузке иели пустом поле поиска)
                initialNoBooksMessage.style.display = 'block';
            }
            bookSelect.disabled = true; // Блокируем select, если нет опций кроме плейсхолдра
            addBookButton.disabled = true;
        }
        availableBooksCountSpan.textContent = books ? books.length : 0;
    }

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const searchTerm = this.value.trim();
        loadingIndicator.style.display = 'inline';
        noBooksFoundMessage.style.display = 'none'; // Скрыть предыдущее сообщение
        initialNoBooksMessage.style.display = 'none'; // Скрыть предыдущее сообщение

        searchTimeout = setTimeout(() => {
            fetch(`${ajaxUrl}?promo_id=${promotionId}&search_term=${encodeURIComponent(searchTerm)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    loadingIndicator.style.display = 'none';
                    if (data.error) {
                        console.error('Ошибка от сервера:', data.error);
                        noBooksFoundMessage.textContent = 'Произошла ошибка при поиске: ' + data.error;
                        noBooksFoundMessage.style.display = 'block';
                        updateBookList([], searchTerm); // Очистить список
                    } else {
                        updateBookList(data.books, searchTerm);
                    }
                })
                .catch(error => {
                    loadingIndicator.style.display = 'none';
                    console.error('Ошибка AJAX запроса:', error);
                    noBooksFoundMessage.textContent = 'Ошибка связи с сервером. Попробуйте позже.';
                    noBooksFoundMessage.style.display = 'block';
                    updateBookList([], searchTerm); // Очистить список в случае ошибки
                });
        }, 350); // Задержка в 350 мс
    });

    // Первоначальная проверка состояния кнопки и select
    if (bookSelect.options.length <= 1) { // Только плейсхолдер
        bookSelect.disabled = true;
        addBookButton.disabled = true;
        if (initialNoBooksMessage) initialNoBooksMessage.style.display = 'block';
    } else {
        bookSelect.disabled = false;
        addBookButton.disabled = false;
         if (initialNoBooksMessage) initialNoBooksMessage.style.display = 'none';
    }
});
</script>
</body>
</html>
<?php
if (isset($mysqli)) { // Закрываем соединение, если оно было открыто этим скриптом
    $mysqli->close();
}
?>