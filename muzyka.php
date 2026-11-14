<?php
    session_start();
    if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] == false) {
        header('Location: logowanie.php');
        exit();
    }

    require_once "polaczenie.php";

    $sort = $_GET['sort'] ?? 'default';
    $search = trim($_GET['q'] ?? '');

    switch ($sort) {
        case 'cena-rosnaco':
            $orderBy = 'm.cena IS NULL ASC, m.cena ASC';
            break;
        case 'cena-malejaco':
            $orderBy = 'm.cena IS NULL ASC, m.cena DESC';
            break;
        case 'ocena-najlepeij':
        case 'ocena-najwyzsza':
            $orderBy = 'm.id DESC'; // Jeśli w tabeli muzyka nie ma oceny, można zmienić na odpowiednie pole
            break;
        case 'ocena-najgorzej':
            $orderBy = 'm.id ASC';
            break;
        case 'rok-najnowsze':
        case 'rok':
            $orderBy = 'm.rok IS NULL ASC, m.rok DESC, m.id DESC';
            break;
        case 'rok-najstarsze':
            $orderBy = 'm.rok IS NULL ASC, m.rok ASC, m.id DESC';
            break;
        case 'nazwa':
            $orderBy = 'm.tytul ASC';
            break;
        case 'data-dodania':
            $orderBy = 'm.id DESC';
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

        $sql = "SELECT m.id, m.tytul, m.gatunek, m.rok, m.ilosc_dostepnych, m.cena, m.czy_ulubiony,
                       a.wykonawca, a.okladka, a.discogs_uri, t.nazwa AS typ
                FROM muzyka m
                LEFT JOIN albumy a ON m.album_id = a.id
                LEFT JOIN typy_plyt t ON a.typ_plyty_id = t.id";

        if ($search !== '') {
            $sql .= " WHERE m.tytul LIKE ? OR a.wykonawca LIKE ?";
        }

        $sql .= " ORDER BY $orderBy";

        $stmt = $polaczenie->prepare($sql);

        if ($search !== '') {
            $param = '%' . $search . '%';
            $stmt->bind_param("ss", $param, $param);
        }

        $stmt->execute();
        $wynik = $stmt->get_result();

        if ($wynik) {
            while ($row = $wynik->fetch_assoc()) {
                $albumy[] = $row;
            }
            $wynik->free();
        }

        if (isset($stmt)) {
            $stmt->close();
        }

        $typ = "muzyka";
        $polaczenie->close();
    } catch (Exception $e) {
        // Strona pokaże stan pusty, jeśli baza jest niedostępna.
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
    <title>Muzyka | Wypożyczalnia</title>
</head>
<body>
    <?php
        $komunikaty = [
            'dodano_muzyke' => 'Muzyka została pomyślnie dodana do bazy danych.',
            'edytowano_muzyke' => 'Album został zaktualizowany.',
            'usunieto_muzyke' => 'Album został usunięty.',
        ];

        foreach ($komunikaty as $klucz => $tekst) {
            if (!empty($_SESSION[$klucz])) {
                echo '<div class="sukces">' . $tekst . '</div>';
                unset($_SESSION[$klucz]);
            }
        }

        if (!empty($_SESSION['ulubione_komunikat'])) {
            echo '<div class="sukces">' . htmlspecialchars($_SESSION['ulubione_komunikat']) . '</div>';
            unset($_SESSION['ulubione_komunikat']);
        }
    ?>

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
        <form method="GET" action="muzyka.php" class="filters">
            <div class="filters-title">
                <span>Muzyka dostępna w naszej wypożyczalni:</span>
            </div>

            <div class="filters-controls">
                <input type="text" id="wyszukiwarka" name="q" placeholder="Szukaj tytułu lub wykonawcy..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                <button type="submit" id="przycisk-szukaj">Szukaj</button>

                <select id="sortowanie" name="sort">
                    <option value="default" <?= $sort === 'default' ? 'selected' : '' ?>>Sortuj według</option>
                    <option value="data-dodania" <?= $sort === 'data-dodania' ? 'selected' : '' ?>>Data dodania</option>
                    <option value="nazwa" <?= $sort === 'nazwa' ? 'selected' : '' ?>>Nazwa: A-Z</option>
                    <option value="rok-najnowsze" <?= $sort === 'rok-najnowsze' || $sort === 'rok' ? 'selected' : '' ?>>Rok wydania: od najnowszego</option>
                    <option value="rok-najstarsze" <?= $sort === 'rok-najstarsze' ? 'selected' : '' ?>>Rok wydania: od najstarszego</option>
                    <option value="cena-rosnaco" <?= $sort === 'cena-rosnaco' ? 'selected' : '' ?>>Cena: od najniższej</option>
                    <option value="cena-malejaco" <?= $sort === 'cena-malejaco' ? 'selected' : '' ?>>Cena: od najwyższej</option>
                </select>
            </div>
        </form>

        <p class="biblioteka">
            <span>Biblioteka albumów:</span>
        </p>

        <?php if (count($albumy) > 0): ?>
            <div class="film-grid">
                <?php foreach ($albumy as $album): ?>
                    <div class="film-card-wrap">
                        <a class="film-card" href="album.php?id=<?= (int) $album['id'] ?>">
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
                        </a>

                        <div class="admin-actions">
                            <a class="przycisk" href="wypozycz.php?typ=muzyka&amp;id=<?= (int) $album['id'] ?>">Wypożycz</a>

                            <?php if ($czyAdmin): ?>
                                <a class="przycisk serduszko <?= !empty($album['czy_ulubiony']) ? 'aktywne' : '' ?>"
                                   href="ulubione_muzyka.php?id=<?= (int) $album['id'] ?>&amp;powrot=muzyka"
                                   title="<?= !empty($album['czy_ulubiony']) ? 'Usuń z polecanych' : 'Dodaj do polecanych' ?>">
                                    <?= !empty($album['czy_ulubiony']) ? '♥' : '♡' ?>
                                </a>
                            <?php endif; ?>

                            <a class="przycisk" href="edytuj_muzyka.php?id=<?= (int) $album['id'] ?>">Edytuj</a>
                            <a class="przycisk" href="usun_muzyka.php?id=<?= (int) $album['id'] ?>" onclick="return confirm('Czy na pewno usunąć ten album?');">Usuń</a>
                        </div>
                    </div>
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