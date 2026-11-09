<?php
    session_start();
    if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] == false) {
        header('Location: logowanie.html');
        exit();
    }

    if (isset($_SESSION[$czy_admin]) || $_SESSION[$czy_admin] == 1) {
        header('Location: konto_admin.php');
        exit();
    }
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twoje konto</title>
</head>
<body>
    <a href="index.php">Powrót</a>
    <!-- lista wypozyczen -->
</body>
</html>