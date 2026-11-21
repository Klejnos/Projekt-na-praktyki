<?php
    require_once "czy_zalogowany.php";

    require_once "polaczenie.php";

    $sort = $_GET['sort'] ?? 'default';
    $search = trim($_GET['q'] ?? '');
    $wybraneGatunki = isset($_GET['gatunki']) && is_array($_GET['gatunki']) ? array_values(array_unique(array_filter(array_map('trim', $_GET['gatunki']), fn($g) => $g !== ''))) : [];

    switch ($sort) {
        case 'cena-rosnaco':
            $orderBy = 'm.cena IS NULL ASC, m.cena ASC';
            break;
        case 'cena-malejaco':
            $orderBy = 'm.cena IS NULL ASC, m.cena DESC';
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
    $statystyki = ['total' => 0, 'available' => 0];
    $dostepneGatunki = [];

    try {
        $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
        if ($polaczenie->connect_errno) {
            throw new Exception();
        }

        $polaczenie->set_charset("utf8mb4");

        $wynik = $polaczenie->query(
            "SELECT m.gatunek AS m_gatunek, a.gatunek AS a_gatunek
             FROM muzyka m
             LEFT JOIN albumy a ON m.album_id = a.id"
        );
        if ($wynik) {
            $unikalneGatunki = [];
            while ($row = $wynik->fetch_assoc()) {
                foreach ([$row['m_gatunek'] ?? '', $row['a_gatunek'] ?? ''] as $zrodloGatunku) {
                    if ($zrodloGatunku === '') {
                        continue;
                    }
                    $czesci = preg_split('/\s*[,;\/|]\s*/u', (string) $zrodloGatunku);
                    foreach ($czesci as $czesc) {
                        $czesc = trim($czesc);
                        if ($czesc !== '') {
                            $klucz = mb_strtolower($czesc, 'UTF-8');
                            if (!isset($unikalneGatunki[$klucz])) {
                                $unikalneGatunki[$klucz] = $czesc;
                            }
                        }
                    }
                }
            }
            $wynik->free();
            natcasesort($unikalneGatunki);
            $dostepneGatunki = array_values($unikalneGatunki);
        }

        $wynik = $polaczenie->query("SELECT COUNT(*) AS total, COALESCE(SUM(ilosc_dostepnych > 0), 0) AS available FROM muzyka");
        if ($wynik) {
            $statystyki = $wynik->fetch_assoc();
            $wynik->free();
        }

        $sql = "SELECT m.id, m.tytul, m.gatunek, m.rok, m.ilosc_dostepnych, m.cena, m.czy_ulubiony,
                       a.wykonawca, a.okladka, a.discogs_uri, t.nazwa AS typ";

        $typyParametrow = '';
        $parametry = [];

        if ($search !== '') {
            $sql .= ", (SELECT u.tytul FROM utwory u
                        WHERE u.album_id = a.id AND u.tytul LIKE ?
                        ORDER BY u.id LIMIT 1) AS pasujacy_utwor";
            $param = '%' . $search . '%';
            $typyParametrow .= 's';
            $parametry[] = $param;
        }

        $sql .= " FROM muzyka m
                LEFT JOIN albumy a ON m.album_id = a.id
                LEFT JOIN typy_plyt t ON a.typ_plyty_id = t.id";

        $warunki = [];

        if ($search !== '') {
            $warunki[] = "(m.tytul LIKE ? OR a.wykonawca LIKE ? OR EXISTS (
                            SELECT 1 FROM utwory u
                            WHERE u.album_id = a.id AND u.tytul LIKE ?
                          ))";
            $param = '%' . $search . '%';
            $typyParametrow .= 'sss';
            $parametry[] = $param;
            $parametry[] = $param;
            $parametry[] = $param;
        }

        if (!empty($wybraneGatunki)) {
            $warunkiGatunkow = [];
            foreach ($wybraneGatunki as $gatunek) {
                $warunkiGatunkow[] = "(COALESCE(m.gatunek, '') LIKE ? OR COALESCE(a.gatunek, '') LIKE ?)";
                $typyParametrow .= 'ss';
                $gatunekLike = '%' . $gatunek . '%';
                $parametry[] = $gatunekLike;
                $parametry[] = $gatunekLike;
            }
            $warunki[] = "(" . implode(" AND ", $warunkiGatunkow) . ")";
        }

        if (!empty($warunki)) {
            $sql .= " WHERE " . implode(" AND ", $warunki);
        }

        $sql .= " ORDER BY $orderBy";

        $stmt = $polaczenie->prepare($sql);
        
        if ($stmt) {
            if ($typyParametrow !== '') {
                $stmt->bind_param($typyParametrow, ...$parametry);
            }

            $stmt->execute();
            $wynik = $stmt->get_result();

            if ($wynik) {
                while ($row = $wynik->fetch_assoc()) {
                    $albumy[] = $row;
                }
                $wynik->free();
            }

            $stmt->close();
        }

        $polaczenie->close();
    } catch (Exception $e) {
        // Strona pokaże stan pusty, jeśli baza jest niedostępna.
    }

    $czyAdmin = isset($_SESSION['czy_admin']) && (int) $_SESSION['czy_admin'] === 1;
    $active = 'muzyka';
    $pageTitle = 'Muzyka';
    ?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wypożyczalnia filmów</title>
    <link rel="stylesheet" href="assets/css/style_filmy.css">
    <link rel="stylesheet" href="assets/css/lista.css">
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
            <a class="top-btn" href="konto.php">Konto</a>
            <a class="top-btn" href="wylogowanie.php">Wyloguj się</a>
        </div>
    </div>
    <nav class="mainnav">
        <a href="index.php">Strona główna</a>
        <a href="filmy.php">Filmy</a>
        <a href="muzyka.php" class="active">Muzyka</a>
        <a href="utwory.php">Utwory</a>
        <a href="kontakt.php">Kontakt</a>
    </nav>
</header>
<main class="page">

    <section class="catalog-head">
        <div>
            <h1>Muzyka</h1>
            <p class="catalog-meta">
                <?= (int) $statystyki['total'] ?> albumów w katalogu - <?= (int) $statystyki['available'] ?> dostępnych od ręki
            </p>
            <?php if ($czyAdmin): ?>
                <p class="admin-note">Widok administratora - serduszka i ikony edycji są ukryte dla klientów.</p>
            <?php endif; ?>
        </div>

        <?php if ($czyAdmin): ?>
            <a class="btn btn-primary" href="dodawanie_muzyka.php">+ Dodaj muzykę</a>
        <?php endif; ?>
    </section>

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

    <form method="GET" action="muzyka.php" class="catalog-filters">
        <div class="search-field">
            <input type="text" id="wyszukiwarka" name="q" placeholder="Szukaj albumu, wykonawcy lub utworu..." value="<?= htmlspecialchars($search) ?>">
        </div>

        <div class="dropdown-filter" id="gatunkiDropdown">
            <button type="button" class="btn btn-secondary dropdown-toggle" id="gatunkiBtn" aria-expanded="false">
                <span id="gatunkiLabel">
                    <?= !empty($wybraneGatunki) ? 'Gatunki (' . count($wybraneGatunki) . ')' : 'Gatunki' ?>
                </span>
            </button>

            <div class="dropdown-menu" id="gatunkiMenu">
                <?php foreach ($dostepneGatunki as $gatunek): ?>
                    <?php $zaznaczony = in_array($gatunek, $wybraneGatunki, true); ?>
                    <label class="dropdown-item">
                        <input
                            type="checkbox"
                            name="gatunki[]"
                            value="<?= htmlspecialchars($gatunek) ?>"
                            <?= $zaznaczony ? 'checked' : '' ?>
                        >
                        <span><?= htmlspecialchars($gatunek) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <select id="sortowanie" name="sort">
            <option value="default" <?= $sort === 'default' ? 'selected' : '' ?>>Sortuj: najnowsze</option>
            <option value="data-dodania" <?= $sort === 'data-dodania' ? 'selected' : '' ?>>Data dodania</option>
            <option value="nazwa" <?= $sort === 'nazwa' ? 'selected' : '' ?>>Nazwa: A-Z</option>
            <option value="rok-najnowsze" <?= $sort === 'rok-najnowsze' || $sort === 'rok' ? 'selected' : '' ?>>Rok: od najnowszego</option>
            <option value="rok-najstarsze" <?= $sort === 'rok-najstarsze' ? 'selected' : '' ?>>Rok: od najstarszego</option>
            <option value="cena-rosnaco" <?= $sort === 'cena-rosnaco' ? 'selected' : '' ?>>Cena: od najniższej</option>
            <option value="cena-malejaco" <?= $sort === 'cena-malejaco' ? 'selected' : '' ?>>Cena: od najwyższej</option>
        </select>

        <button class="btn btn-secondary" type="submit">Szukaj</button>
    </form>

    <?php if (count($albumy) > 0): ?>
        <div class="catalog-grid">
            <?php foreach ($albumy as $album): ?>
                <?php
                    $typ = $album['typ'] ?: 'CD';
                    $dostepne = $album['ilosc_dostepnych'] !== null ? (int) $album['ilosc_dostepnych'] : 0;
                    $cena = $album['cena'] !== null ? number_format((float) $album['cena'], 2, ',', ' ') . ' zł' : '—';
                    $url = 'album.php?id=' . (int) $album['id'];
                ?>
                <article class="catalog-card">
                    <div class="catalog-poster">
                        <a href="<?= $url ?>">
                            <?php if (!empty($album['okladka'])): ?>
                                <img src="<?= htmlspecialchars($album['okladka']) ?>" alt="Okładka albumu <?= htmlspecialchars($album['tytul']) ?>">
                            <?php else: ?>
                                <div class="poster-fallback"></div>
                            <?php endif; ?>
                        </a>

                        <span class="media-badge"><?= htmlspecialchars($typ) ?></span>

                        <?php if ($czyAdmin): ?>
                            <a class="heart-btn <?= !empty($album['czy_ulubiony']) ? 'active' : '' ?>"
                               href="ulubione_muzyka.php?id=<?= (int) $album['id'] ?>&amp;powrot=muzyka"
                               title="<?= !empty($album['czy_ulubiony']) ? 'Usuń z polecanych' : 'Dodaj do polecanych' ?>">
                                <?= !empty($album['czy_ulubiony']) ? '♥' : '♡' ?>
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="catalog-body">
                        <h3 class="catalog-title">
                            <a href="<?= $url ?>"><?= htmlspecialchars($album['tytul']) ?></a>
                        </h3>

                        <?php if (!empty($album['wykonawca'])): ?>
                            <p class="catalog-original"><?= htmlspecialchars($album['wykonawca']) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($album['pasujacy_utwor'])): ?>
                            <p class="catalog-track">♫ Zawiera: <?= htmlspecialchars($album['pasujacy_utwor']) ?></p>
                        <?php endif; ?>

                        <p class="catalog-meta">
                            <span><?= htmlspecialchars($album['gatunek'] ?: 'Muzyka') ?></span>
                            <?php if (!empty($album['rok'])): ?>
                                <span>· <?= (int) $album['rok'] ?></span>
                            <?php endif; ?>
                        </p>

                        <div class="catalog-bottom">
                            <span class="availability <?= $dostepne > 0 ? '' : 'out' ?>">
                                <?= $dostepne > 0 ? 'Dostępne: ' . $dostepne : 'Brak na stanie' ?>
                            </span>
                            <span class="price"><?= $cena ?></span>
                        </div>

                        <div class="catalog-actions">
                            <a class="btn btn-primary" href="wypozycz.php?typ=muzyka&amp;id=<?= (int) $album['id'] ?>">Wypożycz</a>

                            <?php if ($czyAdmin): ?>
                                <a class="icon-btn" href="edytuj_muzyka.php?id=<?= (int) $album['id'] ?>" title="Edytuj album">✎</a>
                                <a class="icon-btn" href="usun_muzyka.php?id=<?= (int) $album['id'] ?>" title="Usuń album" onclick="return confirm('Czy na pewno usunąć ten album?');">🗑</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="empty-state">Brak albumów spełniających kryteria.</p>
    <?php endif; ?>

    <script src="assets/js/script_filmy.js"></script>
    <script src="assets/js/lista_gatunki.js"></script>
</main>
<br><footer class="site-footer">
    <div class="footer-inner">
        <p>2026 - Przemysław Gałęziowski i Filip Klein</p>
    </div>
</footer>
</body>
</html>