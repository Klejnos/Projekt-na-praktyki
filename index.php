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
            <h2>Witamy w naszej wypożyczalni!</h2>
            <p>Oferujemy szeroki wybór filmów DVD, VHS, BLU-RAY oraz muzyki CD do wypożyczenia. Znajdziesz u nas najnowsze hity kinowe, klasyki filmowe oraz różnorodne gatunki muzyczne.</p>
            <p>Oto kilka najlepszych ofert:</p>
        </main>

    <footer>
        <p>Autorzy: Przemysław Gałęziowski i Filip Mały</p>
</body>
</html>