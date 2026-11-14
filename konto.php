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

    require_once "polaczenie.php";
    try {
        $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
    }
    catch (Exception $e) {
        echo "Błąd serwera";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twoje konto</title>
    <link rel="stylesheet" href="assets/css/style_filmy.css">
</head>
<body>
    <header>
        <h1>Wypożyczalnia filmów DVD oraz muzyki CD<a class="konto" href="konto.php">Konto</a></h1>
    </header>
    <nav>
            <a href="index.php">Strona główna</a>
            <a href="filmy.php">Filmy</a>
            <a href="muzyka.php">Muzyka</a>
            <a href="kontakt.php">Kontakt</a>
    </nav>
    <main>
        <?php if (!empty($_SESSION['konto_komunikat'])): ?>
            <div class="sukces"><?= htmlspecialchars($_SESSION['konto_komunikat']) ?></div>
            <?php unset($_SESSION['konto_komunikat']); ?>
        <?php endif; ?>

        <?php if($czy_admin) : ?>
            <h2>Panel administratora</h2>
        <?php endif; ?>
        
        <h2>Witaj, <?= htmlspecialchars($_SESSION['nazwa']) ?></h2>

        <?php if($czy_admin): ?>
            <p>
                <a class="przycisk" href="dodawanie_filmow.php">Dodaj film</a>
                <a class="przycisk" href="dodawanie_muzyka.php">Dodaj muzykę</a>
            </p>
        <?php endif; ?>

        <p>
            <a class="przycisk" href="filmy.php">Przeglądaj filmy</a>
            <a class="przycisk" href="muzyka.php">Przeglądaj muzykę</a>
        </p>

        <h2>Twoje wypożyczenia - filmy:</h2>
        <?php
            $id = $_SESSION['id'];
            $q = $polaczenie -> query("SELECT f.*, t.nazwa AS nazwa_typu, w.* FROM filmy f LEFT JOIN typy_plyt t ON f.typ_plyty_id = t.id JOIN wypozyczenia_filmy w ON w.film_id = f.id WHERE w.uzytkownicy_id = '$id'");
            $typ = 'film';
            if ($q && $q -> num_rows > 0) {
                while ($wiersz = $q -> fetch_assoc()) {
                    ?>

                    <div class="film-page konto-rental">
                        <?php if (!empty($wiersz['okladka'])): ?>
                            <img class="film-page-cover" src="<?= htmlspecialchars($wiersz['okladka']) ?>" alt="Okładka filmu <?= htmlspecialchars($wiersz['tytul']) ?>">
                        <?php else: ?>
                            <div class="film-page-cover film-cover--empty"><span>Brak okładki</span></div>
                        <?php endif; ?>

                        <div class="film-page-info">
                            <h2><?= htmlspecialchars($wiersz['tytul']) ?></h2>

                            <?php
                                if (!empty($wiersz['tytul_oryginalny']) && $wiersz['tytul_oryginalny'] !== $wiersz['tytul']) {
                                    pole('Tytuł oryginalny', $wiersz['tytul_oryginalny']);
                                }

                                pole('Gatunek', $wiersz['gatunek']);
                                pole('Rok', $wiersz['rok']);
                                pole('Ocena', $wiersz['ocena'] !== null ? number_format((float) $wiersz['ocena'], 1, ',', '') : null);
                                pole('Reżyser', $wiersz['rezyser']);
                                pole('Czas trwania', $wiersz['czas_trwania'] !== null ? $wiersz['czas_trwania'] . ' min' : null);
                                pole('Język', $wiersz['jezyk']);
                                pole('Typ płyty', $wiersz['nazwa_typu']);
                                pole('Data wypożyczenia', $wiersz['data_wypozyczenia']);
                                pole('Termin zwrotu', $wiersz['data_zwrotu']);
                                pole('Cena', $wiersz['cena'] !== null ? number_format((float) $wiersz['cena'], 2, ',', ' ') . ' zł' : null);
                                pole('Opis', $wiersz['opis']);
                            ?>
                            <p>
                                <a class="przycisk" href="zwrot.php?id=<?= (int) $wiersz['film_id'] ?>&amp;typ=<?= $typ ?>">Zwróć film</a>
                                <a class="przycisk" href="przedluz_termin.php?id=<?= (int) $wiersz['film_id'] ?>&amp;typ=<?= $typ ?>">Przedłuż termin zwrotu</a>
                            </p>
                        </div>
                    </div>
                    <hr>
        <?php
                }
            }
            else {
                echo "<h3>Nie masz aktualnie wypożyczonych żadnych filmów</h3>";
            }
        ?>

        <br><br><br><h2>Twoje wypozyczenia - muzyka:</h2>
        <?php
            $q_muzyka = $polaczenie -> query("SELECT m.*, a.wykonawca, a.okladka, w.data_wypozyczenia, w.data_zwrotu, w.muzyka_id 
            FROM muzyka m LEFT JOIN albumy a ON m.album_id = a.id 
            JOIN wypozyczenia_muzyka w ON w.muzyka_id = m.id WHERE w.uzytkownicy_id = '$id'");
            $typ = 'muzyka';
            if ($q_muzyka && $q_muzyka -> num_rows > 0) {
                while ($wiersz_m = $q_muzyka -> fetch_assoc()) {
                    ?>
                    <div class="film-page konto-rental">
                        <?php if (!empty($wiersz_m['okladka'])): ?>
                            <img class="film-page-cover" src="<?= htmlspecialchars($wiersz_m['okladka']) ?>" alt="Okładka albumu <?= htmlspecialchars($wiersz_m['tytul']) ?>">
                        <?php else: ?>
                            <div class="film-page-cover film-cover--empty"><span>Brak okładki</span></div>
                        <?php endif; ?>
                        
                        <div class="film-page-info">
                            <h2><?= htmlspecialchars($wiersz_m['tytul']) ?></h2>

                            <?php
                                if (!empty($wiersz_m['wykonawca'])) {
                                    pole('Wykonawca', $wiersz_m['wykonawca']);
                                }
                                pole('Gatunek', $wiersz_m['gatunek']);
                                pole('Rok', $wiersz_m['rok']);
                                pole('Data wypożyczenia', $wiersz_m['data_wypozyczenia']);
                                pole('Termin zwrotu', $wiersz_m['data_zwrotu']);
                                pole('Cena', $wiersz_m['cena'] !== null ? number_format((float) $wiersz_m['cena'], 2, ',', ' ') . ' zł' : null);
                            ?>
                            <p>
                                <a class="przycisk" href="zwrot.php?id=<?= (int) $wiersz_m['muzyka_id'] ?>&amp;typ=<?= $typ ?>">Zwróć muzykę</a>
                                <a class="przycisk" href="przedluz_termin.php?id=<?= (int) $wiersz_m['muzyka_id'] ?>&amp;typ=<?= $typ ?>">Przedłuż termin zwrotu</a>
                            </p>
                        </div>
                    </div>
        <?php
                }
            }
            else {
                echo "<h3>Nie masz wypożyczonych żadnych albumów muzycznych</h3>";
            }
        ?>
        
        <p>
            <a class="przycisk" href="wylogowanie.php">Wyloguj się</a>
        </p>
        
    </main>

    <footer>
        <p>Autorzy: Przemysław Gałęziowski i Filip Klein</p>
    </footer>
</body>
</html>