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
    <title>Osadnicy - gra przeglądarkowa</title>
</head>
<body>
    <p>Dziękujemy za zarejestrowanie się</p>
    <a href="logowanie.php">Zaloguj się</a> 
</body>
</html>