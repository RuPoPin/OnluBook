<?php 
    session_start();
    require_once '../../../src/config/connect.php';

    if(isset($_POST['name'])){
        $name = $_POST['name'];
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
    if(isset($_POST['description'])){
        $description = $_POST['description'];
        if(empty($description)){
            unset($description);
            $_SESSION['error'] = 'Вы не заполнили все поля';
            header("Location: ../add_book.php");
            exit;
        }
    }
    if(isset($_POST['genre'])){
        $genre = $_POST['genre'];
        if(empty($genre)){
            unset($genre);
            $_SESSION['error'] = 'Вы не заполнили все поля';
            header("Location: ../add_book.php");
            exit;
        }
    }
    if(isset($_POST['imprint'])){
        $imprint = $_POST['imprint'];
        if(empty($imprint)){
            unset($imprint);
            $_SESSION['error'] = 'Вы не заполнили все поля';
            header("Location: ../add_book.php");
            exit;
        }
    }
    if(isset($_POST['prise'])){
        $prise = $_POST['prise'];
        if(empty($prise)){
            unset($prise);
            $_SESSION['error'] = 'Вы не заполнили все поля';
            header("Location: ../add_book.php");
            exit;
        }
    }
    if(isset($_POST['quantity'])){
        $quantity = $_POST['quantity'];
        if(empty($quantity)){
            unset($quantity);
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

    move_uploaded_file($_FILES['file']['tmp_name'], '../../../images/book/' . $filename);

    $resultSelect = $connect->prepare("INSERT INTO `Product` (`id`, `name`, `autor`, `description`, `genre`, `imprint`, `prise`, `quantity`, `image`) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?)");
    $resultSelect->bind_param('sssssiis', $name, $autor, $description, $genre, $imprint, $prise, $quantity, $filename);
    $resultSelect->execute();
    header("Location: ../add_book.php");

        