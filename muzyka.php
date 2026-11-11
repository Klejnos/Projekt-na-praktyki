<?php
    session_start();
    if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] == false) {
        header('Location: logowanie.php');
        exit();
    }

    require_once "polaczenie.php";

    $sort = $_GET['sort'] ?? 'default';

    switch ($sort) {
        case 'cena-rosnaco':
            $orderBy = 'm.cena IS NULL ASC, m.cena ASC';
            break;
        case 'cena-malejaco':
            $orderBy = 'm.cena IS NULL ASC, m.cena DESC';
            break;
        case 'nazwa':
            $orderBy = 'm.tytul ASC';
            break;
        default:
            $sort = 'default';
            $orderBy = 'm.id DESC';
            break;
    }

    $albumy = [];

    try {
        $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
        if ($polaczenie->connect_errno) {
            throw new Exception();
        }

        $polaczenie->set_charset("utf8mb4");

        $wynik = $polaczenie->query(
            "SELECT m.id, m.tytul, m.gatunek, m.rok, m.ilosc_dostepnych, m.cena,
                    a.wykonawca, a.okladka, a.discogs_uri, t.nazwa AS typ
             FROM muzyka m
             LEFT JOIN albumy a ON m.album_id = a.id
             LEFT JOIN typy_plyt t ON a.typ_plyty_id = t.id
             ORDER BY $orderBy"
        );

        if ($wynik) {
            while ($row = $wynik->fetch_assoc()) {
                $albumy[] = $row;
            }
            $wynik->free();
        }

        $polaczenie->close();
    } catch (Exception $e) {
        // Strona pokaże stan pusty, jeśli baza jest niedostępna.
    }

    $czyAdmin = isset($_SESSION['czy_admin']) && (int) $_SESSION['czy_admin'] === 1;
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/style_filmy.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Muzyka | Wypożyczalnia</title>
</head>
<body>
    <header>
        <h1>Wypożyczalnia filmów DVD oraz muzyki CD <h2 class="konto">Konto</h2></h1>
        
    </header>
    <nav>
            <a href="index.php">Strona główna</a> 
            <a href="filmy.php">Filmy</a>
            <a href="muzyka.php">Muzyka</a>
            <a href="kontakt.php">Kontakt</a>
    </nav>
    <main>
        <p class="srodek"><span>Muzyka dostępna w naszej wypożyczalni:</span>
        <select id="sortowanie">
            <option value="default" <?= $sort === 'default' ? 'selected' : '' ?>>Sortuj według</option>
            <option value="cena-rosnaco" <?= $sort === 'cena-rosnaco' ? 'selected' : '' ?>>Cena: od najniższej</option>
            <option value="cena-malejaco" <?= $sort === 'cena-malejaco' ? 'selected' : '' ?>>Cena: od najwyższej</option>
            <option value="nazwa" <?= $sort === 'nazwa' ? 'selected' : '' ?>>Nazwa: A-Z</option>
        </select>
        </p>

        <p class="biblioteka">
            <span>Biblioteka albumów:</span>
            <?php if ($czyAdmin): ?>
                <a class="przycisk" href="dodawanie_muzyka.php">Dodaj album</a>
            <?php endif; ?>
        </p>

        <?php if (count($albumy) > 0): ?>
            <div class="film-grid">
                <?php foreach ($albumy as $album): ?>
                    <?php
                        $discogsUri = $album['discogs_uri'] ?? '';
                        $tag = $discogsUri !== '' ? 'a' : 'div';
                        $atrybuty = $discogsUri !== ''
                            ? ' href="' . htmlspecialchars($discogsUri, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer"'
                            : '';

                        echo '<' . $tag . ' class="film-card"' . $atrybuty . '>';
                    ?>

                    <?php if (!empty($album['okladka'])): ?>
                        <img class="film-cover" src="<?= htmlspecialchars($album['okladka']) ?>" alt="Okładka albumu <?= htmlspecialchars($album['tytul']) ?>">
                    <?php else: ?>
                        <div class="film-cover film-cover--empty"><span>Brak okładki</span></div>
                    <?php endif; ?>

                    <div class="film-info">
                        <b><?= htmlspecialchars($album['tytul']) ?></b>

                        <?php if (!empty($album['wykonawca'])): ?>
                            <span class="film-original"><?= htmlspecialchars($album['wykonawca']) ?></span>
                        <?php endif; ?>

                        <span>
                            <?= htmlspecialchars($album['gatunek'] ?: 'Muzyka') ?><?= $album['rok'] ? ' · ' . (int) $album['rok'] : '' ?>
                        </span>

                        <span>
                            <?= htmlspecialchars($album['typ'] ?: 'brak typu') ?><?= $album['ilosc_dostepnych'] !== null ? ' · dostępne: ' . (int) $album['ilosc_dostepnych'] : '' ?>
                        </span>

                        <span>
                            Cena: <?= $album['cena'] !== null ? number_format((float) $album['cena'], 2, ',', ' ') . ' zł' : '—' ?>
                        </span>
                    </div>

                    <?= '</' . $tag . '>' ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="biblioteka">
                <span>Brak albumów w bazie danych.</span>
            </p>
        <?php endif; ?>
    </main>
    <footer>
        <p>Autorzy: Przemysław Gałęziowski i Filip Klein</p>
    </footer>

    <script src="assets/js/script_filmy.js"></script>
</body>
</html>
