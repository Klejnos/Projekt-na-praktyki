<?php
    session_start();
    if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] == false) {
        header('Location: logowanie.php');
        exit();
    }

    if (!isset($_SESSION['czy_admin']) || (int) $_SESSION['czy_admin'] !== 1) {
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
    <title>Panel administratora | Wypożyczalnia</title>
</head>
<body>
    <header>
        <h1>Wypożyczalnia filmów DVD oraz muzyki CD<a class="konto" href="konto_admin.php">Konto</a></h1>
    </header>
    <nav>
            <a href="index.php">Strona główna</a>
            <a href="filmy.php">Filmy</a>
            <a href="muzyka.php">Muzyka</a>
            <a href="kontakt.php">Kontakt</a>
    </nav>
    <main>
        <h2>Panel administratora</h2>
        <p>Witaj, <?= htmlspecialchars($_SESSION['nazwa']) ?>!</p>

        <p>
            <a class="przycisk" href="dodawanie_filmow.php">Dodaj film</a>
            <a class="przycisk" href="dodawanie_muzyka.php">Dodaj muzykę</a>
        </p>

        <p>
            <a class="przycisk" href="filmy.php">Zarządzaj filmami</a>
            <a class="przycisk" href="muzyka.php">Zarządzaj muzyką</a>
        </p>

        <p>
            <a class="przycisk" href="wylogowanie.php">Wyloguj się</a>
        </p>
    </main>

    <footer>
        <p>Autorzy: Przemysław Gałęziowski i Filip Klein</p>
    </footer>
</body>
</html>
