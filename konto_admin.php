<?php
    session_start();
    if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] == false) {
        header('Location: logowanie.php');
        exit();
    }

    if (!isset($_SESSION[$czy_admin]) || $_SESSION[$czy_admin] == 0) {
        header('Location: konto_uzytkownik.php');
        exit();
    }
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twoje konto</title>
</head>
<body>
    <a href="index.php">Powrót</a>
    <a href="dodawanie_filmow.php">Dodaj film</a>
    <a href="dodawanie_muzyka.php">Dodaj muzykę</a>
    <!-- lista wypozyczen -->
</body>
</html>
