<?php
    require_once "czy_zalogowany.php";

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
        case 'ocena-najnizsza':
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
    $typy = [];
    $statystyki = ['total' => 0, 'available' => 0];

    try {
        $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
        if ($polaczenie->connect_errno) {
            throw new Exception();
        }

        $polaczenie->set_charset("utf8mb4");

        $wynik = $polaczenie->query("SELECT id, nazwa FROM typy_plyt");
        if ($wynik) {
            while ($row = $wynik->fetch_assoc()) {
                $typy[(int) $row['id']] = $row['nazwa'];
            }
            $wynik->free();
        }

        $wynik = $polaczenie->query("SELECT COUNT(*) AS total, COALESCE(SUM(ilosc_dostepnych > 0), 0) AS available FROM filmy");
        if ($wynik) {
            $statystyki = $wynik->fetch_assoc();
            $wynik->free();
        }

        $sql = "SELECT id, tytul, tytul_oryginalny, gatunek, rok, ocena, cena,
                       ilosc_dostepnych, okladka, typ_plyty_id, czy_ulubiony
                FROM filmy";

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

        $stmt->close();
        $polaczenie->close();
    } catch (Exception $e) {
        // Strona pokaże stan pusty, jeśli baza jest niedostępna.
    }

    $czyAdmin = isset($_SESSION['czy_admin']) && (int) $_SESSION['czy_admin'] === 1;
    $active = 'filmy';
    $pageTitle = 'Filmy';
    ?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wypożyczalnia filmów</title>
    <link rel="stylesheet" href="assets/css/style_filmy.css">
    <link rel="icon" type="image/x-icon" href="assets/img/icon.ico">
</head>
<body>
<header class="site-header">
    <div class="header-top">
        <a href="index.php" class="brand">
            <img src="assets/img/icon.ico" alt="plyta" style="width: 38px; height: 38px; border-radius: 6px; user-select: none;">
            <div class="brand-text">
                <strong>Wypożyczalnia</strong>
                <small>Filmów i muzyki</small>
            </div>
        </a>
        <div class="header-actions">
            <a href="konto.php" class="top-btn">Konto</a>
            <a href="wylogowanie.php" class="top-btn">Wyloguj się</a>
        </div>
    </div>
    <nav class="mainnav">
        <a href="index.php">Strona główna</a>
        <a href="filmy.php" class="active">Filmy</a>
        <a href="muzyka.php">Muzyka</a>
        <a href="kontakt.php">Kontakt</a>
    </nav>
</header>
<main class="page">

    <section class="catalog-head">
        <div>
            <h1>Filmy</h1>
            <p class="catalog-meta">
                <?= (int) $statystyki['total'] ?> tytułów w katalogu · <?= (int) $statystyki['available'] ?> dostępne od ręki
            </p>
            <?php if ($czyAdmin): ?>
                <p class="admin-note">Widok administratora — serduszka i ikony edycji są ukryte dla klientów.</p>
            <?php endif; ?>
        </div>

        <?php if ($czyAdmin): ?>
            <a class="btn btn-primary" href="dodawanie_filmow.php">+ Dodaj film</a>
        <?php endif; ?>
    </section>

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

    <form method="GET" action="filmy.php" class="catalog-filters">
        <div class="search-field">
            <input type="text" id="wyszukiwarka" name="q" placeholder="Szukaj tytułu, reżysera..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
        </div>

        <select id="sortowanie" name="sort">
            <option value="default" <?= $sort === 'default' ? 'selected' : '' ?>>Sortuj: najnowsze</option>
            <option value="data-dodania" <?= $sort === 'data-dodania' ? 'selected' : '' ?>>Data dodania</option>
            <option value="nazwa" <?= $sort === 'nazwa' ? 'selected' : '' ?>>Nazwa: A-Z</option>
            <option value="ocena-najlepeij" <?= $sort === 'ocena-najlepeij' ? 'selected' : '' ?>>Ocena: od najlepszej</option>
            <option value="ocena-najgorzej" <?= $sort === 'ocena-najgorzej' ? 'selected' : '' ?>>Ocena: od najgorszej</option>
            <option value="rok-najnowsze" <?= $sort === 'rok-najnowsze' || $sort === 'rok' ? 'selected' : '' ?>>Rok: od najnowszego</option>
            <option value="rok-najstarsze" <?= $sort === 'rok-najstarsze' ? 'selected' : '' ?>>Rok: od najstarszego</option>
            <option value="cena-rosnaco" <?= $sort === 'cena-rosnaco' ? 'selected' : '' ?>>Cena: od najniższej</option>
            <option value="cena-malejaco" <?= $sort === 'cena-malejaco' ? 'selected' : '' ?>>Cena: od najwyższej</option>
        </select>

        <button class="btn btn-secondary" type="submit">Szukaj</button>
    </form>

    <?php if (count($filmy) > 0): ?>
        <div class="catalog-grid">
            <?php foreach ($filmy as $film): ?>
                <?php
                    $typ = $typy[(int) ($film['typ_plyty_id'] ?? 0)] ?? 'DVD';
                    $dostepne = $film['ilosc_dostepnych'] !== null ? (int) $film['ilosc_dostepnych'] : 0;
                    $ocena = $film['ocena'] !== null ? number_format((float) $film['ocena'], 1, ',', '') : '—';
                    $cena = $film['cena'] !== null ? number_format((float) $film['cena'], 2, ',', ' ') . ' zł' : '—';
                    $url = 'film.php?id=' . (int) $film['id'];
                ?>
                <article class="catalog-card">
                    <div class="catalog-poster">
                        <a href="<?= $url ?>">
                            <?php if (!empty($film['okladka'])): ?>
                                <img src="<?= htmlspecialchars($film['okladka']) ?>" alt="Okładka filmu <?= htmlspecialchars($film['tytul']) ?>">
                            <?php else: ?>
                                <div class="poster-fallback"></div>
                            <?php endif; ?>
                        </a>

                        <span class="media-badge"><?= htmlspecialchars($typ) ?></span>

                        <?php if ($czyAdmin): ?>
                            <a class="heart-btn <?= !empty($film['czy_ulubiony']) ? 'active' : '' ?>"
                               href="ulubione_film.php?id=<?= (int) $film['id'] ?>&amp;powrot=filmy"
                               title="<?= !empty($film['czy_ulubiony']) ? 'Usuń z polecanych' : 'Dodaj do polecanych' ?>">
                                <?= !empty($film['czy_ulubiony']) ? '♥' : '♡' ?>
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="catalog-body">
                        <h3 class="catalog-title">
                            <a href="<?= $url ?>"><?= htmlspecialchars($film['tytul']) ?></a>
                        </h3>

                        <?php if (!empty($film['tytul_oryginalny']) && $film['tytul_oryginalny'] !== $film['tytul']): ?>
                            <p class="catalog-original"><?= htmlspecialchars($film['tytul_oryginalny']) ?></p>
                        <?php endif; ?>

                        <p class="catalog-meta">
                            <span class="rating">★ <?= $ocena ?></span>
                            <span><?= htmlspecialchars($film['gatunek'] ?: 'Film') ?></span>
                            <?php if (!empty($film['rok'])): ?>
                                <span>· <?= (int) $film['rok'] ?></span>
                            <?php endif; ?>
                        </p>

                        <div class="catalog-bottom">
                            <span class="availability <?= $dostepne > 0 ? '' : 'out' ?>">
                                <?= $dostepne > 0 ? 'Dostępne: ' . $dostepne : 'Brak na stanie' ?>
                            </span>
                            <span class="price"><?= $cena ?></span>
                        </div>

                        <div class="catalog-actions">
                            <a class="btn btn-primary" href="wypozycz.php?typ=film&amp;id=<?= (int) $film['id'] ?>">Wypożycz</a>

                            <?php if ($czyAdmin): ?>
                                <a class="icon-btn" href="edytuj_film.php?id=<?= (int) $film['id'] ?>" title="Edytuj film">✎</a>
                                <a class="icon-btn" href="usun_film.php?id=<?= (int) $film['id'] ?>" title="Usuń film" onclick="return confirm('Czy na pewno usunąć ten film?');">🗑</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="empty-state">Brak filmów spełniających kryteria.</p>
    <?php endif; ?>

    <script src="assets/js/script_filmy.js"></script>
</main>

<footer class="site-footer">
    <div class="footer-inner">
        <p>2026 - Przemysław Gałęziowski i Filip Klein</p>
    </div>
</footer>

</body>
</html>
