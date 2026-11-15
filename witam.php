<?php
    session_start();
    if (!isset($_SESSION['udana_rejestracja'])) {
        header('Location: logowanie.php');
        exit();
    }
    else {
        unset($_SESSION['udana_rejestracja']);
    }
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wypożyczalnia - witamy</title>
    <link rel="stylesheet" href="assets/css/witam.css">
    <link rel="icon" type="image/x-icon" href="assets/img/icon.ico"> 
</head>

<body>
    <main>
    <div class="witaj">
        <img src="assets/img/icon.ico" alt="plyta">
        <h1>Dziękujemy za zarejestrowanie się :D</h1>
        <a href="index.php">Sprawdź nasze filmy</a> 
    </div>
</main>
</body>
</html>