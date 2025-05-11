<?php
    session_start();
    global $connect;
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"> 
<header>
    <div class="logo">
        <a href="index.php"><img class="img" src="images/рисунок.png" alt="Логотип"></a>   
    </div>
    <div class="search-container">
        <input type="text" id="search-input" placeholder="Поиск книг..." class="search-input">
        <button id="search-button" class="search-button">
            <svg viewBox="0 0 24 24" width="20" height="20">
            <path d="M15.5 14h-.79l-.28-.27a6.5 6.5 0 0 0 1.48-5.34c-.47-2.78-2.79-5-5.59-5.34a6.505 6.505 0 0 0-7.27 7.27c.34 2.8 2.56 5.12 5.34 5.59a6.5 6.5 0 0 0 5.34-1.48l.27.28v.79l4.25 4.25c.41.41 1.08.41 1.49 0 .41-.41.41-1.08 0-1.49L15.5 14zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"></path>
            </svg>
        </button>
        <div id="search-results" class="search-results"></div>
    </div>
    <script src="../Style/serch.js"></script>

    <nav>
        <ul class="menu">
        <li>
            <a href="#" class="down">Каталог</a>
            <ul class="submenu">
                <li><a href="Horror.php" onclick="trackButtonClick('horror_category')">Ужасы</a></li>
                <li><a href="Detektiv.php" onclick="trackButtonClick('detective_category')">Детектив</a></li>
                <li><a href="fantasy.php" onclick="trackButtonClick('fantasy_category')">Фентези</a></li>
            </ul>
        </li>
            <li><a href="Акции.php" onclick="trackButtonClick('promotions')">Акции</a></li>
            <li><a href="Что еще почитать.php" onclick="trackButtonClick('what_to_read')">Что еще почитать?</a></li>
            <li>
                <a href="korzina.php" class="cart-icon" onclick="trackButtonClick('cart')">
                    <i class="fa fa-shopping-cart"></i>
                    <?php if (isset($_SESSION['user']['id'])): ?>
                        <?php 
                            $count = 0;
                            if (isset($connect)) {
                                $stmt = $connect->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
                                $stmt->bind_param("i", $_SESSION['user']['id']);
                                if ($stmt->execute()) {
                                    $result = $stmt->get_result();
                                    $row = $result->fetch_assoc();
                                    $count = $row['total'] ?? 0;
                                }
                            }
                        ?>
                        <span class="cart-count"><?= htmlspecialchars($count) ?></span>
                    <?php else: ?>
                        <span class="cart-count">0</span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
            </li>
            <?php if (isset($_SESSION['user']) && !empty($_SESSION['user'])): ?>
                <li> 
                    <i class="fa fa-user-circle-o" aria-hidden="true" id="user"></i>
                    <ul class="submenu2">
                        <li><a class='logout' href="" onclick="trackButtonClick('user_profile')"><?= htmlspecialchars($_SESSION['user']['name'] ?? '') ?></a></li>
                        <?php if (($_SESSION['user']['access'] ?? '0') == "1"): ?>
                            <li><a class="logout" href="admin_panel/admin_panel.php" onclick="trackButtonClick('admin_panel')">Админ панель</a></li>
                        <?php endif; ?>
                        <li><a class='logout' id='exit' href="src/actions/logout.php" onclick="trackButtonClick('logout')">Выйти</a></li>
                    </ul>
                </li>
            <?php else: ?>
                <li class="register">
                    <a href="Side/register.php" onclick="trackButtonClick('login')">Вход</a>
                    <span> / </span>
                    <a href="Side/register.php" onclick="trackButtonClick('register')">Регистрация</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const header = document.querySelector("header");
        let lastScrollTop = 0;

        window.addEventListener("scroll", function () {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

            if (scrollTop > lastScrollTop) {
                header.classList.add("hide-header");
            } else {
                header.classList.remove("hide-header");
            }
            lastScrollTop = scrollTop;
        });

        const menuItems = document.querySelectorAll("nav ul li a");
        menuItems.forEach(item => {
            item.addEventListener("click", function (e) {
                e.stopPropagation(); 
                header.classList.remove("hide-header"); 
            });
        });
    });
</script>
<script>
    function trackButtonClick(action) {
        if (typeof gtag === 'function') {
            gtag('event', action, {
                'event_category': 'Button Clicks',
                'event_label': action,
                'value': 1
            });
        }
    }
</script>