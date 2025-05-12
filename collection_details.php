<?php
    session_start();
    require_once 'src/config/connect.php'; 

    if (!isset($_GET['collection_id']) || !filter_var($_GET['collection_id'], FILTER_VALIDATE_INT)) {
        http_response_code(400);
        echo "<p style='color: red; text-align: center; font-size: 1.2em; margin-top: 50px;'>Ошибка: Некорректный ID подборки.</p>";

        exit; 
    }

    $collection_id = (int)$_GET['collection_id'];

    if ($connect->connect_error) {
         error_log("Ошибка подключения к базе данных перед запросами: " . $connect->connect_error);
         echo "<p style='color: red; text-align: center;'>Произошла ошибка при загрузке данных. Попробуйте позже.</p>";

         exit; 
    }
    $collection_info = null;
    $stmt_collection = $connect->prepare("SELECT title, description FROM collections WHERE id = ?");
    if ($stmt_collection) {
        $stmt_collection->bind_param("i", $collection_id);
        $stmt_collection->execute();
        $result_collection = $stmt_collection->get_result();
        if ($result_collection->num_rows > 0) {
            $collection_info = $result_collection->fetch_assoc();
        }
        $stmt_collection->close();
    } else {
        error_log("Ошибка подготовки запроса (коллекция): " . $connect->error);
        echo "<p style='color: red; text-align: center;'>Ошибка при загрузке информации о подборке.</p>";

    }

    if (!$collection_info) {
        http_response_code(404); 
        echo "<p style='color: #555; text-align: center; font-size: 1.2em; margin-top: 50px;'>Подборка не найдена.</p>";

    }

    $books = [];

    $stmt_books = $connect->prepare(
        "SELECT p.id, p.name, p.autor, p.description, p.image
         FROM Product p
         INNER JOIN collection_products cp ON p.id = cp.product_id
         WHERE cp.collection_id = ?
         ORDER BY p.name ASC" 
    );

    if ($stmt_books) {
        $stmt_books->bind_param("i", $collection_id);
        $stmt_books->execute();
        $result_books = $stmt_books->get_result();
        while ($row = $result_books->fetch_assoc()) {
            $books[] = $row;
        }
        $stmt_books->close();
    } else {
        error_log("Ошибка подготовки запроса (книги через JOIN): " . $connect->error);
        echo "<p style='color: red; text-align: center;'>Ошибка при загрузке книг для этой подборки.</p>";
    }
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OnlyBook | <?php echo htmlspecialchars($collection_info['title'] ?? 'Подборка'); ?></title>
    <link rel="stylesheet" href="Style/style.css?v=<?php echo @filemtime('Style/style.css'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Comfortaa:wght@300..700&display=swap" rel="stylesheet">
    <style>
     
         body {
            font-family: 'Comfortaa', sans-serif;
            margin: 0;
            background-color: #f9f9f9;
            color: #333;
            line-height: 1.6;
        }
        main {
            max-width: 1200px;
            margin: 40px auto;
            padding: 60px 15px;
        }
        .collection-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .collection-header h1 {
            font-size: 2.5em;
            color: #2c3e50;
            margin: 0 0 10px 0;
        }
        .collection-header p {
            font-size: 1.1em;
            color: #555e68;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            white-space: pre-wrap;
        }
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
            gap: 25px;
            padding: 10px 0;
        }

        .book-card {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(50, 50, 93, 0.07), 0 2px 6px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: inherit;
            transition: transform 0.25s ease-out, box-shadow 0.25s ease-out;
        }
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 7px 20px rgba(50, 50, 93, 0.1), 0 4px 12px rgba(0, 0, 0, 0.07);
        }
        .book-card img {
            width: 100%;
            height: 200px; 
            object-fit: contain; 
            display: block;
        }
        .book-card .book-info {
            padding: 15px;
            flex-grow: 1; 
            display: flex;
            flex-direction: column;
        }
        .book-card h3 {
            font-size: 1.1em;
            font-weight: 600;
            color: #2c3e50;
            margin: 0 0 8px 0;
            line-height: 1.3;
        }
        .book-card .author {
            font-size: 0.9em;
            color: #777;
            margin-bottom: 10px;
        }
        .book-card .description { 
            font-size: 0.85em;
            color: #555e68;
            line-height: 1.5;
            margin-bottom: 0; 
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .no-books {
            text-align: center;
            font-size: 1.1em;
            color: #777;
            padding: 30px 0;
        }

        @media (max-width: 500px) {
            .books-grid {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 15px;
            }
            .book-card img {
                height: 160px;
            }
            .book-card h3 {
                font-size: 1em;
            }
        }
         @media (max-width: 400px) {
            .books-grid {
                grid-template-columns: 1fr; 
            }
         }
    </style>
</head>
<body>
    <?php
        include 'blocks/menu.php';
    ?>

    <main>
        <?php if ($collection_info): ?>
            <div class="collection-header">
                <h1><?php echo htmlspecialchars($collection_info['title']); ?></h1>
                <?php if (!empty($collection_info['description'])): ?>
                    <p><?php echo nl2br(htmlspecialchars($collection_info['description'])); ?></p>
                <?php endif; ?>
            </div>
        <?php endif;  ?>


        <?php if (!empty($books)): ?>
            <div class="books-grid">
                <?php foreach ($books as $book): ?>
                    <?php
                       
                        $book_id_safe = htmlspecialchars($book['id']);
                        $book_title_safe = htmlspecialchars($book['name'] ?? 'Без названия'); 
                        $book_author_safe = htmlspecialchars($book['autor'] ?? 'Автор неизвестен'); 
                        $book_image_safe = htmlspecialchars(!empty($book['image']) ? $book['image'] : 'images/default_book.jpg');
                        $book_description_safe = htmlspecialchars($book['description'] ?? '');
                        $book_link_url = "product_detail.php?id={$book_id_safe}";
                    ?>
                    <a href="<?php echo $book_link_url; ?>" class="book-card">
                        <img src="images/book/<?php echo $book_image_safe; ?>" alt="Обложка книги: <?php echo $book_title_safe; ?>">
                        <div class="book-info">
                            <h3><?php echo $book_title_safe; ?></h3>
                            <p class="author"><?php echo $book_author_safe; ?></p>
                            <?php if (!empty($book_description_safe)): ?>
                                <p class="description"><?php echo $book_description_safe; ?></p>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php elseif ($collection_info):  ?>
            <p class="no-books">В этой подборке пока нет книг.</p>
        <?php endif; ?>

    </main>

    <?php
        include 'blocks/footer.php';
    ?>

<?php
    if (isset($connect) && $connect instanceof mysqli && !$connect->connect_error) {
         $connect->close();
    }
?>
</body>
</html>