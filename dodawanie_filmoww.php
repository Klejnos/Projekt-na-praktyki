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
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wypożyczalnia filmów DVD oraz muzyki CD</title>
</head>
<body>
    <header>
        <h1>Wypożyczalnia filmów DVD oraz muzyki CD</h1>
    </header>
    <nav>
            <a href="index.php">Strona główna</a> 
            <a href="filmy.php">Filmy</a>
            <a href="muzyka.php">Muzyka</a>
            <a href="kontakt.php">Kontakt</a>
    </nav>
    <main>
        <h1>Dodawanie filmu</h1>
            <form action="dodawanie_filmow.php" method="POST">
        <p>
            <label for="tytul">tytuł:</label><br>
            <input type="text" id="tytul" autocomplete="off">
            <div id="podpowiedzi"></div>
            <div id="podgladOkladki">
            <img id="okladka" src="" alt="Okładka filmu">
</div>
        </p>
        <p>
            <label>Gatunek:</label><br>
            <input type="text" id="gatunek">
        </p>
        <p>
            <label for="rok">Rok produkcji:</label><br>
            <input type="text" id="rok" name="rok">
        </p>
        <p>
            <label for="ocena">Ocena:</label><br>
            <input type="text" id="ocena" name="ocena">
        </p>
        <p>
            <label for="rezyser">Reżyser:</label><br>
            <input type="text" id="rezyser" name="rezyser">
        </p>
        <p>
            <label for="czas_trwania">Czas trwania (minuty):</label><br>
            <input type="text" id="czas_trwania" name="czas_trwania">
        </p>
        <p>
            <label for="jezyk">Język:</label><br>
            <input type="text" id="jezyk" name="jezyk">
        </p>
        <p>
            <label for="ilosc_dostepnych">Ilość dostępnych:</label><br>
            <input type="text" id="ilosc_dostepnych" name="ilosc_dostepnych">
        </p>
        <p>
            <label for="budzet">Budżet (PLN):</label><br>
            <input type="text" id="budzet" name="budzet">
        </p>
        <p>
            <label for="produkcja">Produkcja:</label><br>
            <input type="text" id="produkcja" name="produkcja">
        </p>
        <p>
            <label for="nagrody">Nagrody:</label><br>
            <input type="text" id="nagrody" name="nagrody">
        </p>      
        <p>
            <label for="cena">Cena za wypożyczenie (PLN):</label><br>
            <input type="text" id="cena" name="cena">
        </p>
        <p>
            <label for="opis">Opis:</label><br>
            <textarea id="opis" name="opis" rows="5" cols="40" placeholder="Wpisz opis filmu..."></textarea>
        </p>
        <p>
            <button type="submit">Wyślij formularz</button>
            <button type="reset">Wyczyść</button>
        </p>
    </form>
        </main>
<script src="assets/js/script_api.js"></script>
    <footer>
        
        <p>Autorzy: Przemysław Gałęziowski i Filip Mały</p>
</body>
</html>
