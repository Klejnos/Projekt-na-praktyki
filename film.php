<?php
    session_start();
    if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] == false) {
        header('Location: logowanie.php');
        exit();
    }

    require_once "polaczenie.php";

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $film = null;

    try {
        $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);

        if (!$polaczenie->connect_errno) {
            $polaczenie->set_charset("utf8mb4");

            $wynik = $polaczenie->query(
                "SELECT f.*, t.nazwa AS typ
                 FROM filmy f
                 LEFT JOIN typy_plyt t ON f.typ_plyty_id = t.id
                 WHERE f.id = $id"
            );

            if ($wynik && $wynik->num_rows === 1) {
                $film = $wynik->fetch_assoc();
                $wynik->free();
            }

            $polaczenie->close();
        }
    } catch (Exception $e) {
        // Poniżej przekierowanie, jeśli filmu nie udało się pobrać.
    }

    if (!$film) {
        header('Location: filmy.php');
        exit();
    }

    function pole(string $etykieta, $wartosc): void
    {
        if ($wartosc !== null && $wartosc !== '') {
            echo '<p><b>' . $etykieta . ':</b> ' . htmlspecialchars((string) $wartosc) . '</p>';
        }
    }
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/style_filmy.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($film['tytul']) ?> | Wypożyczalnia</title>
</head>
<body>
    <header>
        <h1>Wypożyczalnia filmów DVD oraz muzyki CD<a class="przycisk" href="dodawanie_filmow.php">Dodaj film</a><h2 class="konto">Konto</h2></h1>
    </header>
    <nav>
            <a href="index.php">Strona główna</a>
            <a href="filmy.php">Filmy</a>
            <a href="muzyka.php">Muzyka</a>
            <a href="kontakt.php">Kontakt</a>
    </nav>
    <main>
        <p class="biblioteka">
            <span>Szczegóły filmu:</span>
        </p>

        <div class="film-page">
            <?php if (!empty($film['okladka'])): ?>
                <img class="film-page-cover" src="<?= htmlspecialchars($film['okladka']) ?>" alt="Okładka filmu <?= htmlspecialchars($film['tytul']) ?>">
            <?php else: ?>
                <div class="film-page-cover film-cover--empty"><span>Brak okładki</span></div>
            <?php endif; ?>

            <div class="film-page-info">
                <h2><?= htmlspecialchars($film['tytul']) ?></h2>

                <?php
                    pole('Gatunek', $film['gatunek']);
                    pole('Rok', $film['rok']);
                    pole('Ocena', $film['ocena'] !== null ? number_format((float) $film['ocena'], 1, ',', '') : null);
                    pole('Reżyser', $film['rezyser']);
                    pole('Czas trwania', $film['czas_trwania'] !== null ? $film['czas_trwania'] . ' min' : null);
                    pole('Język', $film['jezyk']);
                    pole('Typ płyty', $film['typ']);
                    pole('Dostępne sztuki', $film['ilosc_dostepnych']);
                    pole('Cena', $film['cena'] !== null ? number_format((float) $film['cena'], 2, ',', ' ') . ' zł' : null);
                    pole('Produkcja', $film['produkcja']);
                    pole('Budżet', $film['budzet'] !== null ? number_format((float) $film['budzet'], 2, ',', ' ') . ' zł' : null);
                    pole('Nagrody', $film['nagrody']);
                    pole('Opis', $film['opis']);
                ?>

                <p><a class="przycisk" href="filmy.php">Powrót do filmów</a></p>
            </div>
        </div>
    </main>

    <footer>
        <p>Autorzy: Przemysław Gałęziowski i Filip Klein</p>
    </footer>
</body>
</html>
