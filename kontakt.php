<?php
    session_start();
    if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] == false) {
        header('Location: logowanie.php');
        exit();
    }
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontakt | Wypożyczalnia filmów</title>
    <link rel="stylesheet" href="assets/css/kontakt.css">
    <link rel="icon" type="image/x-icon" href="assets/img/icon.ico"> 
</head>
<body>
    <header class="site-header">
        <div class="header-top">
            <a href="index.php" class="brand">
                <img src="assets/img/icon.ico" alt="plyta" style="width: 38px; height: 38px; border-radius: 6px; user-select: none;">
                <div class="brand-text">
                    <strong>Wypożyczalnia</strong>
                    <small>Filmów i muzyki</small>
                </div>
            </a>
            <div class="header-actions">
                <a href="konto.php" class="top-btn">Konto</a>
            </div>
        </div>
        <nav class="mainnav">
            <a href="index.php">Strona główna</a>
            <a href="filmy.php">Filmy</a>
            <a href="muzyka.php">Muzyka</a>
            <a href="kontakt.php" class="active">Kontakt</a>
        </nav>
    </header>

    <main>
        <h2>Kontakt</h2>
        <p>Jeśli masz pytania lub potrzebujesz pomocy, skontaktuj się z nami:</p>

        <ul>
            <li><a href="sms:+48574152486">Wyślij SMS na numer +48 574 152 486</a></li>
            <li><a href="mailto:wypozyczalnia@spoko.pl">Email: wypozyczalnia@spoko.pl</a></li>
            <li><a href="https://www.google.com/maps/search/?api=1&amp;query=ul.+Rajska+4,+80-850+Gda%C5%84sk" target="_blank" rel="noopener noreferrer">ul. Rajska 4, 80-850 Gdańsk</a></li>
        </ul>
    </main>

    <footer class="site-footer">
        <div class="footer-inner">
            <p>2026 - Przemysław Gałęziowski i Filip Klein</p>
        </div>
    </footer>
</body>
</html>