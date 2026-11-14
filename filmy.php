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
            $orderBy = 'cena IS NULL ASC, cena ASC';
            break;
        case 'cena-malejaco':
            $orderBy = 'cena IS NULL ASC, cena DESC';
            break;
        case 'ocena-najlepeij':
        case 'ocena-najwyzsza':
            $orderBy = 'ocena IS NULL ASC, ocena DESC';
            break;
        case 'ocena-najgorzej':
        case 'ocena-najnizszas':
            $orderBy = 'ocena IS NULL ASC, ocena ASC';
            break;
        case 'rok-najnowsze':
        case 'rok':
            $orderBy = 'rok IS NULL ASC, rok DESC, id DESC';
            break;
        case 'rok-najstarsze':
            $orderBy = 'rok IS NULL ASC, rok ASC, id DESC';
            break;
        case 'nazwa':
            $orderBy = 'tytul ASC';
            break;
        case 'data-dodania':
            $orderBy = 'id DESC';
            break;
        default:
            $sort = 'default';
            $orderBy = 'id DESC';
            break;
    }

    $filmy = [];

    try {
        $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
        if ($polaczenie->connect_errno) {
            throw new Exception();
        }

        $polaczenie->set_charset("utf8mb4");

        $sql = "SELECT id, tytul, tytul_oryginalny, gatunek, rok, ocena, cena, okladka, czy_ulubiony FROM filmy";
        
        if ($search !== '') {
            $sql .= " WHERE tytul LIKE ? OR tytul_oryginalny LIKE ?";
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
                $filmy[] = $row;
            }
            $wynik->free();
        }

        if (isset($stmt)) {
            $stmt->close();
        }
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
    <title>Filmy | Wypożyczalnia</title>
</head>
<body>
    <?php
        $komunikaty = [
            'dodano_film' => 'Film został pomyślnie dodany do bazy danych.',
            'edytowano_film' => 'Film został zaktualizowany.',
            'usunieto_film' => 'Film został usunięty.',
        ];

        foreach ($komunikaty as $klucz => $tekst) {
            if (!empty($_SESSION[$klucz])) {
                echo '<div class="sukces">' . $tekst . '</div>';
                unset($_SESSION[$klucz]);
            }
        }

        if (!empty($_SESSION['ulubione_film_komunikat'])) {
            echo '<div class="sukces">' . htmlspecialchars($_SESSION['ulubione_film_komunikat']) . '</div>';
            unset($_SESSION['ulubione_film_komunikat']);
        }
    ?>

    <header>
        <h1>Wypożyczalnia filmów DVD oraz muzyki CD<a class="przycisk" href="dodawanie_filmow.php">Dodaj film</a><a class="konto" href="<?= $kontoLink ?>">Konto</a></h1>
    </header>
    <nav>
            <a href="index.php">Strona główna</a> 
            <a href="filmy.php">Filmy</a>
            <a href="muzyka.php">Muzyka</a>
            <a href="kontakt.php">Kontakt</a>
    </nav>
    <main>
        <form method="GET" action="filmy.php" class="filters">
            <div class="filters-title">
                <span>Filmy dostępne w naszej wypożyczalni:</span>
            </div>

            <div class="filters-controls">
                <input type="text" id="wyszukiwarka" name="q" placeholder="Szukaj tytułu..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                <button type="submit" id="przycisk-szukaj">Szukaj</button>

                <select id="sortowanie" name="sort">
                    <option value="default" <?= $sort === 'default' ? 'selected' : '' ?>>Sortuj według</option>
                    <option value="data-dodania" <?= $sort === 'data-dodania' ? 'selected' : '' ?>>Data dodania</option>
                    <option value="nazwa" <?= $sort === 'nazwa' ? 'selected' : '' ?>>Nazwa: A-Z</option>
                    <option value="ocena-najlepeij" <?= $sort === 'ocena-najlepeij' ? 'selected' : '' ?>>Ocena: od najlepszej</option>
                    <option value="ocena-najgorzej" <?= $sort === 'ocena-najgorzej' ? 'selected' : '' ?>>Ocena: od najgorszej</option>
                    <option value="rok-najnowsze" <?= $sort === 'rok-najnowsze' || $sort === 'rok' ? 'selected' : '' ?>>Rok wydania: od najnowszego</option>
                    <option value="rok-najstarsze" <?= $sort === 'rok-najstarsze' ? 'selected' : '' ?>>Rok wydania: od najstarszego</option>
                    <option value="cena-rosnaco" <?= $sort === 'cena-rosnaco' ? 'selected' : '' ?>>Cena: od najniższej</option>
                    <option value="cena-malejaco" <?= $sort === 'cena-malejaco' ? 'selected' : '' ?>>Cena: od najwyższej</option>
                </select>
            </div>
        </form>

        <p class="biblioteka">
            <span>Biblioteka filmów:</span>
        </p>

        <?php if (count($filmy) > 0): ?>
            <div class="film-grid">
                <?php foreach ($filmy as $film): ?>
                    <div class="film-card-wrap">
                        <a class="film-card" href="film.php?id=<?= (int) $film['id'] ?>">
                            <?php if (!empty($film['okladka'])): ?>
                                <img class="film-cover" src="<?= htmlspecialchars($film['okladka']) ?>" alt="Okładka filmu <?= htmlspecialchars($film['tytul']) ?>">
                            <?php else: ?>
                                <div class="film-cover film-cover--empty"><span>Brak okładki</span></div>
                            <?php endif; ?>

                            <div class="film-info">
                                <b><?= htmlspecialchars($film['tytul']) ?></b>

                                <?php if (!empty($film['tytul_oryginalny']) && $film['tytul_oryginalny'] !== $film['tytul']): ?>
                                    <span class="film-original"><?= htmlspecialchars($film['tytul_oryginalny']) ?></span>
                                <?php endif; ?>

                                <?php
                                    $podtytul = [];

                                    if ($film['gatunek']) {
                                        $podtytul[] = htmlspecialchars($film['gatunek']);
                                    }

                                    if ($film['rok']) {
                                        $podtytul[] = (int) $film['rok'];
                                    }

                                    if ($podtytul) {
                                        echo '<span>' . implode(' · ', $podtytul) . '</span>';
                                    }
                                ?>

                                <?php
                                    $szczegoly = [];

                                    if ($film['ocena'] !== null) {
                                        $szczegoly[] = 'Ocena: ' . number_format((float) $film['ocena'], 1, ',', '');
                                    }

                                    if ($film['cena'] !== null) {
                                        $szczegoly[] = 'Cena: ' . number_format((float) $film['cena'], 2, ',', ' ') . ' zł';
                                    }

                                    if ($szczegoly) {
                                        echo '<span>' . implode(' | ', $szczegoly) . '</span>';
                                    }
                                ?>
                            </div>
                        </a>

                        <div class="admin-actions">
                            <a class="przycisk" href="wypozycz.php?typ=film&amp;id=<?= (int) $film['id'] ?>">Wypożycz</a>

                            <?php if ($czyAdmin): ?>
                                <a class="przycisk serduszko <?= !empty($film['czy_ulubiony']) ? 'aktywne' : '' ?>"
                                   href="ulubione_film.php?id=<?= (int) $film['id'] ?>&amp;powrot=filmy"
                                   title="<?= !empty($film['czy_ulubiony']) ? 'Usuń z polecanych' : 'Dodaj do polecanych' ?>">
                                    <?= !empty($film['czy_ulubiony']) ? '♥' : '♡' ?>
                                </a>
                            <?php endif; ?>

                            <a class="przycisk" href="edytuj_film.php?id=<?= (int) $film['id'] ?>">Edytuj</a>
                            <a class="przycisk" href="usun_film.php?id=<?= (int) $film['id'] ?>" onclick="return confirm('Czy na pewno usunąć ten film?');">Usuń</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="biblioteka"><span>Brak filmów w bazie danych.</span></p>
        <?php endif; ?>
    </main>

    <footer>
        <p>Autorzy: Przemysław Gałęziowski i Filip Klein</p>
    </footer>

    <script src="assets/js/script_filmy.js"></script>
</body>
</html>