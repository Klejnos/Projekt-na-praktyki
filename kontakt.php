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
    <title>Kontakt | Wypożyczalnia</title>
</head>
<body>
    <header>
        <h1>Wypożyczalnia filmów DVD oraz muzyki CD<a class="konto" href="<?= (!empty($_SESSION['czy_admin'])) ? 'konto_admin.php' : 'konto_uzytkownik.php' ?>">Konto</a></h1>
    </header>
    <nav>
            <a href="index.php">Strona główna</a>
            <a href="filmy.php">Filmy</a>
            <a href="muzyka.php">Muzyka</a>
            <a href="kontakt.php">Kontakt</a>
    </nav>
    <main>
        <h2>Kontakt</h2>
        <p>Masz pytania dotyczące wypożyczeń, rezerwacji albo dostępnych tytułów? Skontaktuj się z nami — chętnie pomożemy.</p>

        <div class="kontakt-grid">
            <div class="kontakt-box">
                <h3>Telefon / SMS</h3>
                <p><a href="sms:+48574152486">+48 574 152 486</a></p>
                <p>Poniedziałek – piątek: 10:00 – 19:00</p>
            </div>

            <div class="kontakt-box">
                <h3>E-mail</h3>
                <p><a href="mailto:kontakt@twojadomena.pl">kontakt@twojadomena.pl</a></p>
                <p>Odpowiadamy zwykle w ciągu jednego dnia.</p>
            </div>

            <div class="kontakt-box">
                <h3>Adres</h3>
                <p>
                    <a href="https://www.google.com/maps/search/?api=1&amp;query=ul.+Rajska+4,+80-850+Gda%C5%84sk"
                       target="_blank" rel="noopener noreferrer">
                        ul. Rajska 4<br>80-850 Gdańsk
                    </a>
                </p>
                <p>Otwórz lokalizację w Google Maps</p>
            </div>

            <div class="kontakt-box">
                <h3>Godziny otwarcia</h3>
                <p>Poniedziałek – piątek: 10:00 – 19:00</p>
                <p>Sobota: 10:00 – 15:00</p>
                <p>Niedziela: nieczynne</p>
            </div>
        </div>

        <h3>Napisz do nas</h3>
        <p>Najszybszy kontakt to e-mail. Kliknij poniżej, aby otworzyć swoją pocztę.</p>
        <p><a class="przycisk" href="mailto:kontakt@twojadomena.pl?subject=Pytanie%20do%20wypo%C5%BCyczalni">Wyślij wiadomość e-mail</a></p>
    </main>
    <footer>
        <p>Autorzy: Przemysław Gałęziowski i Filip Klein</p>
    </footer>
</body>
</html>
