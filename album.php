<?php
    session_start();
    if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] == false) {
        header('Location: logowanie.php');
        exit();
    }

    require_once "polaczenie.php";

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $album = null;

    try {
        $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);

        if (!$polaczenie->connect_errno) {
            $polaczenie->set_charset("utf8mb4");

            $stmt = $polaczenie->prepare(
                "SELECT m.*, a.wykonawca, a.okladka, a.discogs_uri, t.nazwa AS typ
                 FROM muzyka m
                 LEFT JOIN albumy a ON m.album_id = a.id
                 LEFT JOIN typy_plyt t ON a.typ_plyty_id = t.id
                 WHERE m.id = ?
                 LIMIT 1"
            );

            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $wynik = $stmt->get_result();

                if ($wynik && $wynik->num_rows === 1) {
                    $album = $wynik->fetch_assoc();
                }

                $stmt->close();
            }

            $polaczenie->close();
        }
    } catch (Exception $e) {
        // Poniżej przekierowanie, jeśli albumu nie udało się pobrać.
    }

    if (!$album) {
        header('Location: muzyka.php');
        exit();
    }

    function pole(string $etykieta, $wartosc): void
    {
        if ($wartosc !== null && $wartosc !== '') {
            echo '<p><b>' . $etykieta . ':</b> ' . htmlspecialchars((string) $wartosc) . '</p>';
        }
    }

    $czyAdmin = isset($_SESSION['czy_admin']) && (int) $_SESSION['czy_admin'] === 1;
    $kontoLink = 'konto.php';
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/style_filmy.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($album['tytul']) ?> | Wypożyczalnia</title>
</head>
<body>
    <header>
        <h1>Wypożyczalnia filmów DVD oraz muzyki CD<a class="przycisk" href="dodawanie_muzyka.php">Dodaj muzykę</a><a class="konto" href="<?= $kontoLink ?>">Konto</a></h1>
    </header>
    <nav>
            <a href="index.php">Strona główna</a>
            <a href="filmy.php">Filmy</a>
            <a href="muzyka.php">Muzyka</a>
            <a href="kontakt.php">Kontakt</a>
    </nav>
    <main>
        <?php
            if (!empty($_SESSION['rent_sukces'])) {
                echo '<div class="sukces">' . htmlspecialchars($_SESSION['rent_sukces']) . '</div>';
                unset($_SESSION['rent_sukces']);
            }

            if (!empty($_SESSION['rent_blad'])) {
                echo '<div class="error">' . htmlspecialchars($_SESSION['rent_blad']) . '</div>';
                unset($_SESSION['rent_blad']);
            }

            if (!empty($_SESSION['ulubione_komunikat'])) {
                echo '<div class="sukces">' . htmlspecialchars($_SESSION['ulubione_komunikat']) . '</div>';
                unset($_SESSION['ulubione_komunikat']);
            }
        ?>

        <p class="biblioteka">
            <span>Szczegóły albumu:</span>
        </p>

        <div class="film-page">
            <?php if (!empty($album['okladka'])): ?>
                <img class="film-page-cover" src="<?= htmlspecialchars($album['okladka']) ?>" alt="Okładka albumu <?= htmlspecialchars($album['tytul']) ?>">
            <?php else: ?>
                <div class="film-page-cover film-cover--empty"><span>Brak okładki</span></div>
            <?php endif; ?>

            <div class="film-page-info">
                <h2><?= htmlspecialchars($album['tytul']) ?></h2>

                <?php
                    pole('Wykonawca', $album['wykonawca']);
                    pole('Rok', $album['rok']);
                    pole('Gatunek', $album['gatunek']);
                    pole('Typ płyty', $album['typ']);
                    pole('Dostępne sztuki', $album['ilosc_dostepnych']);
                    pole('Cena', $album['cena'] !== null ? number_format((float) $album['cena'], 2, ',', ' ') . ' zł' : null);
                ?>

                <p>
                    <a class="przycisk" href="wypozycz.php?typ=muzyka&amp;id=<?= (int) $album['id'] ?>">Wypożycz</a>
                    <a class="przycisk" href="edytuj_muzyka.php?id=<?= (int) $album['id'] ?>">Edytuj</a>
                    <a class="przycisk" href="usun_muzyka.php?id=<?= (int) $album['id'] ?>" onclick="return confirm('Czy na pewno usunąć ten album?');">Usuń</a>

                    <?php if ($czyAdmin): ?>
                        <a class="przycisk serduszko <?= !empty($album['czy_ulubiony']) ? 'aktywne' : '' ?>"
                           href="ulubione_muzyka.php?id=<?= (int) $album['id'] ?>&amp;powrot=album"
                           title="<?= !empty($album['czy_ulubiony']) ? 'Usuń z polecanych' : 'Dodaj do polecanych' ?>">
                            <?= !empty($album['czy_ulubiony']) ? '♥' : '♡' ?>
                        </a>
                    <?php endif; ?>

                    <a class="przycisk" href="muzyka.php">Powrót do muzyki</a>
                </p>

                <?php if (!empty($album['discogs_uri'])): ?>
                    <p>
                        <a class="przycisk" href="<?= htmlspecialchars($album['discogs_uri'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                            Zobacz w Discogs
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer>
        <p>Autorzy: Przemysław Gałęziowski i Filip Klein</p>
    </footer>
</body>
</html>
