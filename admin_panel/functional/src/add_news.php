<?php 
    session_start();
    require_once '../../../src/config/connect.php';

    if(isset($_POST['name_n'])){
        $name = $_POST['name_n'];
        if(empty($name)){
            unset($name);
            $_SESSION['error'] = 'Вы не заполнили все поля';
            header("Location: ../add_book.php");
            exit;
        }
    }
    if(isset($_POST['autor'])){
        $autor = $_POST['autor'];
        if(empty($autor)){
            unset($autor);
            $_SESSION['error'] = 'Вы не заполнили все поля';
            header("Location: ../add_book.php");
            exit;
        }
    }
    if(isset($_POST['description_n'])){
        $description = $_POST['description_n'];
        if(empty($description)){
            unset($description);
            $_SESSION['error'] = 'Вы не заполнили все поля';
            header("Location: ../add_book.php");
            exit;
        }
    }
    $allowed_types = ["png", "jpg", "jpeg"];

    $file_extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
    if(empty($_FILES['file']['name'])){
            $_SESSION['error'] = 'Загрузите файл';
            header("Location: ../add_book.php");
            exit;
        }
    if(!in_array($file_extension, $allowed_types)){
        $_SESSION['error'] = 'Можно загрузить файлы только в формате: png, jpg';
        header("Location: ../add_book.php");
        exit;
        }
    
    $filename = uniqid() . "." . $file_extension;

    move_uploaded_file($_FILES['file']['tmp_name'], '../../../images/news/' . $filename);

    $resultSelect = $connect->prepare("INSERT INTO `news` (`id_n`, `autor`, `name_n`, `description_n`, `img_n`) VALUES (NULL, ?, ?, ?, ?)");
    $resultSelect->bind_param('ssss', $name, $autor, $description, $filename);
    $resultSelect->execute();
    header("Location: ../add_news.php");

        