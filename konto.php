<?php
    session_start();
    if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] == false) {
        header('Location: logowanie.php');
        exit();
    }

    $czy_admin = true;
    if (!isset($_SESSION['czy_admin']) || (int) $_SESSION['czy_admin'] !== 1) {
        $czy_admin = false;
    }
?>


<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/style.css">
    <title>Twoje konto</title>
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
        <?php if($czy_admin) : ?>
            <h2>Panel administratora</h2>
        <?php endif; ?>
        
        <h2>Witaj, <?= htmlspecialchars($_SESSION['nazwa']) ?></h2>

        <?php if($czy_admin): ?>
            <button><a href="dodawanie_filmow.php">Dodaj film</a></button>
            <button><a href="dodawanie_muzyka.php">Dodaj muzykę</a></button>
        <?php endif; ?>

        <button><a href="filmy.php">Przeglądaj filmy</a></button>
        <button><a href="muzyka.php">Przeglądaj muzykę</a></button>
        
        <button><a href="wylogowanie.php">Wyloguj się</a></button>
        
    </main>

    <footer>
        <p>Autorzy: Przemysław Gałęziowski i Filip Klein</p>
    </footer>
</body>
</html>