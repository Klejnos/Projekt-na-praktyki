<?php
    session_start();
    if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] == false) {
        header('Location: logowanie.html');
        exit();
    }
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wypożyczalnia filmów DVD oraz muzyki CD</title>
</head>
<body>
    <header>
        <h1>Wypożyczalnia filmów DVD oraz muzyki CD</h1>
    </header>
    <nav>
            <a href="index.html">Strona główna</a> 
            <a href="filmy.html">Filmy</a>
            <a href="muzyka.html">Muzyka</a>
            <a href="kontakt.html">Kontakt</a>
    </nav>
    <main>
        <h2>Kontakt</h2>
        <p>Jeśli masz pytania lub potrzebujesz pomocy, skontaktuj się z nami:</p>
        <ul>
            <a href="sms:+48574152486">Wyślij SMS na numer +48 574 152 486</a><br>
            <a href="mailto:kontakt@twojadomena.pl">Email: kontakt@twojadomena.pl</a><br>
            <a href="https://www.google.com/maps/search/?api=1&query=ul.+Rajska+4,+80-850+Gdańsk" target="_blank" rel="noopener noreferrer"> ul. Rajska 4, 80-850 Gdańsk
</a>
        </ul>
    </main>
    <footer>
        <p>Autorzy: Przemysław Gałęziowski i Filip Klein</p>
</body>
</html>