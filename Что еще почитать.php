<?php
    // Start the session
    session_start();

    // Require the database connection file
    require_once 'src/config/connect.php';

    // Check if connection failed
    if ($connect->connect_error) {
        // Log the error instead of dying immediately in a production environment
        error_log("Ошибка подключения к базе данных: " . $connect->connect_error);
        // Display a user-friendly message and potentially exit
        die("Произошла ошибка при подключении к базе данных. Попробуйте позже.");
    }
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OnlyBook | Что почитать (Компакт)</title>
    <!-- Include main stylesheet -->
    <!-- Используем filemtime() для более надежного сброса кэша, если файл изменился -->
    <link rel="stylesheet" href="Style/style.css?v=<?php echo filemtime('Style/style.css'); ?>">
    <!-- Preconnect for fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Comfortaa:wght@300..700&display=swap" rel="stylesheet">

    <Style>
        /* Общие сбросы и стили для body */
        body {
            font-family: 'Comfortaa', sans-serif;
            margin: 0;
            background-color: #f9f9f9;
            color: #333;
            line-height: 1.6;
        }

        main {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 15px;
        }

        .block1 {
            text-align: center;
            font-size: 2.3em; /* Немного уменьшим главный заголовок */
            margin-bottom: 25px;
            color: #2c3e50;
            font-weight: 700;
        }

        /* --- Стили для КОМПАКТНЫХ Улучшенных карточек --- */

        .content {
            display: grid;
            /* Карточки будут мин. 200px, макс. 1fr. Больше карточек в ряду */
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px; /* Уменьшаем пространство между карточками */
            padding: 10px 0;
        }

        .content > a {
            text-decoration: none;
            color: inherit;
            display: flex;
            transition: transform 0.25s ease-out, box-shadow 0.25s ease-out;
        }

        .insept {
            background-color: #ffffff;
            border-radius: 10px; /* Чуть менее скругленные для компактности */
            box-shadow: 0 4px 12px rgba(50, 50, 93, 0.06), 0 2px 6px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            width: 100%;
            transition: inherit;
        }

        .content > a:hover {
            transform: translateY(-4px); /* Уменьшаем эффект поднятия */
        }
        .content > a:hover .insept {
            box-shadow: 0 7px 20px rgba(50, 50, 93, 0.09), 0 4px 12px rgba(0, 0, 0, 0.06);
        }

        .insept .images {
            width: 100%;
            height: 140px; /* Значительно уменьшаем высоту изображения */
            object-fit: cover;
            display: block;
            transition: opacity 0.3s ease, transform 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        .content > a:hover .insept .images {
            transform: scale(1.04); /* Уменьшаем эффект зума */
        }

        .insept h3 { /* Заголовок */
            font-size: 1.05em; /* Уменьшаем размер заголовка */
            font-weight: 600;
            color: #2c3e50;
            margin: 12px 15px 6px 15px; /* Уменьшаем отступы */
            line-height: 1.25;
        }

        .insept .text { /* Описание */
            font-size: 0.8em; /* Уменьшаем текст описания */
            color: #555e68;
            line-height: 1.5;
            padding: 0 15px 15px 15px; /* Уменьшаем отступы */
            flex-grow: 1;
            margin-bottom: 0;
        }

        /* Адаптивность для очень маленьких экранов */
        @media (max-width: 440px) { /* Порог, когда 200px + gap * 2 уже много */
            .content {
                grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); /* Еще меньше */
                gap: 15px;
            }
            .insept .images {
                height: 110px;
            }
            .insept h3 {
                font-size: 0.95em;
                margin: 10px 10px 5px 10px;
            }
            .insept .text {
                font-size: 0.75em;
                padding: 0 10px 10px 10px;
            }
        }

        @media (max-width: 340px) { /* Одна колонка */
            .content {
                grid-template-columns: 1fr;
            }
        }

    </Style>
</head>
<body>
    <?php
        // Include the menu block
        include 'blocks/menu.php';
    ?>

    <main>
        <h1 class="block1">Что еще почитать?</h1>
        <div class="content">
            <?php
                // SQL query to select collections
                // We still select all fields, but now we use 'id' for the link
                $sql = "SELECT id, title, description, image_path, link_url, alt_text FROM collections ORDER BY id ASC";
                $result = $connect->query($sql);

                // Check if the query was successful
                if ($result === false) {
                    // Log the error and display a user-friendly message
                    error_log("Ошибка SQL запроса: " . $connect->error);
                    echo "<p style='color: red; text-align: center;'>Произошла ошибка при загрузке подборок. Попробуйте позже.</p>";
                } elseif ($result->num_rows > 0) {
                    // Loop through each row of the result
                    while($row = $result->fetch_assoc()) {
                        // --- PREPARE DATA ---
                        // Sanitize the ID for the URL (though it's an integer, good practice)
                        $collection_id = htmlspecialchars($row["id"]);
                        // Sanitize other output using htmlspecialchars
                        $image_path = htmlspecialchars($row["image_path"] ?? '');
                        // Use alt_text if available, otherwise title, otherwise a default
                        $alt_text = htmlspecialchars(!empty($row["alt_text"]) ? $row["alt_text"] : (!empty($row["title"]) ? $row["title"] : "Обложка подборки"));
                        $title = htmlspecialchars($row["title"] ?? 'Без названия');
                        $description = htmlspecialchars($row["description"] ?? 'Без описания');

                        // --- OUTPUT HTML ---
                        // Output the collection card HTML
                        // The Href now points to collection_details.php and passes the collection_id
                        echo <<<HTML
<a href="collection_details.php?collection_id={$collection_id}">
    <div class="insept">
        <img class="images" src="{$image_path}" alt="{$alt_text}">
        <h3>{$title}</h3>
        <div class="text">
            {$description}
        </div>
    </div>
</a>
HTML;
                    }
                } else {
                    // No collections found
                    echo "<p style='text-align: center;'>Подборок пока нет.</p>";
                }

                // Close the database connection
                // Only close if $connect is a valid connection object
                if ($connect && method_exists($connect, 'close')) {
                     $connect->close();
                }
            ?>
        </div>
    </main>

    <?php
        // Include the footer block
        include 'blocks/footer.php';
    ?>
</body>
</html>