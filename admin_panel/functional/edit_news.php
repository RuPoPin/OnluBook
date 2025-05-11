<?php
    session_start();
    require_once '../../src/config/connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css?<? echo time()?>">
    <title>Document</title>
</head>
<body>
    <h1>Изменение данных о книге</h1>
    <table>
        <tr>
            <td class="info" id="header">ID</td>
            <td class="info" id="header">Название</td>
            <td class="info" id="header">Автор</td>
            <td class="info" id="header">Содержание</td>
            <td class="info" id="header">Фотография</td>
            <td class="info" id="header">Изменить</td>
            <td class="info" id="header">Удалить</td>
        </tr>
    <?php 
        $resultSelect = $connect->prepare("SELECT * FROM `news`");
        $resultSelect -> execute();
        $resultSelect = $resultSelect -> get_result();
        $rows = mysqli_fetch_all($resultSelect);

        
        foreach($rows as $row){
            ?>
             
                
                <tr>
                    <td class="info"><?=$row[0]?></td>
                    <td class="info"><?=$row[1]?></td>
                    <td class="info"><?=$row[2]?></td>
                    <td class="info"><?=$row[3]?></td>
                    <td class="info"><?=$row[4]?></td>
                    <td class="info"><a href="src/news_update.php?id=<?=$row[0]?>">Изменить</a></td>
                    <td class="info"><a href="src/delete_news.php?id=<?=$row[0]?>">Удалить</a></td>
                </tr>
           
    
            <?php
            }
        ?> 
        </table>
</body>
</html>