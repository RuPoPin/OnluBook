<?php
session_start();
if (isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация / Регистрация</title>
    <link rel="stylesheet" href="Style/style_reg.css?<? echo time()?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"> 
    
</head>
<body>
    <!-- Блок для отображения ошибок -->
    <?php if (isset($_SESSION['errors'])): ?>
        <div class="errors">
            <?php foreach ($_SESSION['errors'] as $error): ?>
                <p><?= $error ?></p>
            <?php endforeach; ?>
        </div>
        <?php unset($_SESSION['errors']); ?>
    <?php endif; ?>

    <!-- Блок для отображения успешных сообщений -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="success">
            <p><?= $_SESSION['success_message'] ?></p>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <div class="auth-container">
        <div class="toggle-forms">
            <button class="toggle-btn active" id="login-toggle">Вход</button>
            <button class="toggle-btn" id="register-toggle">Регистрация</button>
        </div>
        
        <div class="forms-container" id="forms-container">
            <!-- Форма входа -->
            <div class="form" id="login-form">
                <h2>Вход</h2>
                <form action="../src/actions/singup.php" method="POST">
                    <div>
                        <label for="login-email">Login:</label>
                        <input type="text" id="login-email" name="login" required 
                               value="<?= $_SESSION['old']['login'] ?? '' ?>">
                    </div>
                    <div>
                        <label for="login-password">Пароль:</label>
                        <input type="password" id="login-password" name="password" required>
                    </div>
                    <button type="submit">Войти</button>
                </form>
            </div>
            
            <!-- Форма регистрации -->
            <div class="form" id="register-form">
                <h2>Регистрация</h2>
                <form action="../src/actions/register.php" method="POST">
                    <div>
                        <label for="register-name">Имя:</label>
                        <input type="text" id="register-name" name="login" required
                               value="<?= $_SESSION['old']['login'] ?? '' ?>">
                    </div>
                    <div>
                        <label for="register-email">Email:</label>
                        <input type="email" id="register-email" name="email" required
                               value="<?= $_SESSION['old']['email'] ?? '' ?>">
                    </div>
                    <div>
                        <label for="register-password">Пароль:</label>
                        <input type="password" id="register-password" name="password" required>
                    </div>
                    <div>
                        <label for="register-confirm-password">Подтвердите пароль:</label>
                        <input type="password" id="register-confirm-password" name="conf_password" required>
                    </div>
                    <button type="submit">Зарегистрироваться</button>
                </form>
            </div>
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginToggle = document.getElementById('login-toggle');
            const registerToggle = document.getElementById('register-toggle');
            const formsContainer = document.getElementById('forms-container');
            
            // Переключение между формами
            loginToggle.addEventListener('click', function() {
                formsContainer.style.transform = 'translateX(0)';
                loginToggle.classList.add('active');
                registerToggle.classList.remove('active');
            });
            
            registerToggle.addEventListener('click', function() {
                formsContainer.style.transform = 'translateX(-50%)';
                registerToggle.classList.add('active');
                loginToggle.classList.remove('active');
            });
            
            // Обработка свайпов (для мобильных устройств)
            let touchStartX = 0;
            let touchEndX = 0;
            
            formsContainer.addEventListener('touchstart', function(e) {
                touchStartX = e.changedTouches[0].screenX;
            }, false);
            
            formsContainer.addEventListener('touchend', function(e) {
                touchEndX = e.changedTouches[0].screenX;
                handleSwipe();
            }, false);
            
            function handleSwipe() {
                if (touchEndX < touchStartX - 50) {
                    // Свайп влево - показываем регистрацию
                    formsContainer.style.transform = 'translateX(-50%)';
                    registerToggle.classList.add('active');
                    loginToggle.classList.remove('active');
                }
                
                if (touchEndX > touchStartX + 50) {
                    // Свайп вправо - показываем вход
                    formsContainer.style.transform = 'translateX(0)';
                    loginToggle.classList.add('active');
                    registerToggle.classList.remove('active');
                }
            }
        });
    </script>
    <?php if (isset($_SESSION['errors'])): ?>
        <div class="errors">
            <?php foreach ($_SESSION['errors'] as $error): ?>
                <p><?= $error ?></p>
            <?php endforeach; ?>
        </div>
        <?php unset($_SESSION['errors']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="success">
            <p><?= $_SESSION['success_message'] ?></p>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
</body>
</html>