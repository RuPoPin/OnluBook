<?php 
  session_start();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Страница регистрации</title>
  <link rel="stylesheet" href="Style/style_reg.css">
</head>
<body>
    <main>
        <div class="register-form-container">
            <h1 class="form-title">
                Вход
            </h1>
            <div class="form-fields">
                <form action="../src/actions/singup.php" id="add-form"  method="post" >
                    <p>Логин</p>
                    <div class="input_box">
                      <input type="text" placeholder="Введите свой логин" name="login">
                    </div>
                    <p>Пароль</p>
                    <div class="input_box">
                      <input type="password" placeholder="Введите пароль" name="password">
                    </div>
                    <button type="submit">Войти</button>
                        <?php 
                          if ($_SESSION) {
                              echo '<p class="msg">' . $_SESSION['messange'] . '</p>';
                          }
                          unset($_SESSION['messange']);
                        ?>
                    <p class=reg>У вас нет аккаунта? - <a href="Регистрация.php">Зарегистрируйтесь</a>!</p>
                </form>
                <script src="Style/script_reg.js"></script>
            </div>
        </div>
    </main>
</body>
</html>