<?php
    require_once "czy_zalogowany.php";

    require_once "polaczenie.php";

    $sort = $_GET['sort'] ?? 'default';
    $search = trim($_GET['q'] ?? '');

    switch ($sort) {
        case 'tytul':
            $orderBy = 'u.tytul ASC, u.id ASC';
            break;
        case 'tytul-malejaco':
            $orderBy = 'u.tytul DESC, u.id ASC';
            break;
        case 'wykonawca':
            $orderBy = 'a.wykonawca ASC, u.id ASC';
            break;
        case 'album':
            $orderBy = 'a.tytul ASC, u.id ASC';
            break;
        case 'rok-najnowsze':
            $orderBy = 'a.rok IS NULL ASC, a.rok DESC, u.id ASC';
            break;
        case 'rok-najstarsze':
            $orderBy = 'a.rok IS NULL ASC, a.rok ASC, u.id ASC';
            break;
        case 'cena-rosnaco':
            $orderBy = 'm.cena IS NULL ASC, m.cena ASC, u.id ASC';
            break;
        case 'cena-malejaco':
            $orderBy = 'm.cena IS NULL ASC, m.cena DESC, u.id ASC';
            break;
        default:
            $sort = 'default';
            $orderBy = 'u.id ASC';
            break;
    }

    $utwory = [];
    $total = 0;
    $znaleziono = 0;

    try {
        $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
        if ($polaczenie->connect_errno) {
            throw new Exception();
        }

        $polaczenie->set_charset("utf8mb4");

        $wynik = $polaczenie->query("SELECT COUNT(*) AS total FROM utwory");
        if ($wynik) {
            $total = (int) $wynik->fetch_assoc()['total'];
            $wynik->free();
        }

        $where = '';
        if ($search !== '') {
            $where = " WHERE u.tytul LIKE ? OR a.tytul LIKE ? OR a.wykonawca LIKE ?";
        }

        $countSql = "SELECT COUNT(*) AS total
                     FROM utwory u
                     INNER JOIN albumy a ON a.id = u.album_id" . $where;
        $countStmt = $polaczenie->prepare($countSql);

        if ($countStmt) {
            if ($search !== '') {
                $param = '%' . $search . '%';
                $countStmt->bind_param("sss", $param, $param, $param);
            }

            $countStmt->execute();
            $countWynik = $countStmt->get_result();

            if ($countWynik) {
                $znaleziono = (int) $countWynik->fetch_assoc()['total'];
                $countWynik->free();
            }

            $countStmt->close();
        }

        $sql = "SELECT u.id, u.album_id, u.tytul, u.czas,
                       a.tytul AS album_tytul, a.wykonawca, a.rok, a.gatunek, a.okladka,
                       m.cena, m.ilosc_dostepnych
                FROM utwory u
                INNER JOIN albumy a ON a.id = u.album_id
                LEFT JOIN (
                    SELECT album_id, MIN(cena) AS cena, SUM(ilosc_dostepnych) AS ilosc_dostepnych
                    FROM muzyka
                    GROUP BY album_id
                ) m ON m.album_id = a.id" . $where . "
                ORDER BY " . $orderBy;

        $stmt = $polaczenie->prepare($sql);

        if ($stmt) {
            if ($search !== '') {
                $param = '%' . $search . '%';
                $stmt->bind_param("sss", $param, $param, $param);
            }

            $stmt->execute();
            $wynik = $stmt->get_result();

            if ($wynik) {
                while ($row = $wynik->fetch_assoc()) {
                    $utwory[] = $row;
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
    $active = 'utwory';
    $pageTitle = 'Utwory';
    $wyswietlono = count($utwory);
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
            <a class="top-btn" href="konto.php">Konto</a>
            <a class="top-btn" href="wylogowanie.php">Wyloguj się</a>
        </div>
    </div>
    <nav class="mainnav">
        <a href="index.php">Strona główna</a>
        <a href="filmy.php">Filmy</a>
        <a href="muzyka.php">Muzyka</a>
        <a href="utwory.php" class="active">Utwory</a>
        <a href="kontakt.php">Kontakt</a>
    </nav>
</header>
<main class="page">

<?php
        if (!empty($_SESSION['usunieto_utwor'])) {
            echo '<div class="sukces">Utwór został usunięty.</div>';
            unset($_SESSION['usunieto_utwor']);
        }
        
        if (!empty($_SESSION['wypozycz_utwor_blad'])) {
            echo '<div class="error">' . htmlspecialchars($_SESSION['wypozycz_utwor_blad']) . '</div>';
            unset($_SESSION['wypozycz_utwor_blad']);
        }
    ?>

    <section class="catalog-head">
        <div>
            <h1>Utwory</h1>
            <p class="catalog-meta">
                <?php if ($search !== ''): ?>
                    Znaleziono <?= $wyswietlono ?> z <?= $total ?> utworów
                <?php else: ?>
                    <?= $total ?> utworów w katalogu
                <?php endif; ?>
            </p>
            <?php if ($czyAdmin): ?>
                <p class="admin-note">Widok administratora - serduszka i ikony edycji są ukryte dla klientów.</p>
            <?php endif; ?>
        </div>
        <?php if ($czyAdmin): ?>
            <a class="btn btn-primary" href="dodawanie_utwor.php">+ Dodaj utwór</a>
        <?php endif; ?>
    </section>

    <form method="GET" action="utwory.php" class="catalog-filters">
        <div class="search-field">
            <input type="text" id="wyszukiwarka" name="q" placeholder="Szukaj utworu, wykonawcy lub albumu..." value="<?= htmlspecialchars($search) ?>">
        </div>

        <select id="sortowanie" name="sort">
            <option value="default" <?= $sort === 'default' ? 'selected' : '' ?>>Sortuj: domyślnie</option>
            <option value="tytul" <?= $sort === 'tytul' ? 'selected' : '' ?>>Tytuł: A-Z</option>
            <option value="tytul-malejaco" <?= $sort === 'tytul-malejaco' ? 'selected' : '' ?>>Tytuł: Z-A</option>
            <option value="wykonawca" <?= $sort === 'wykonawca' ? 'selected' : '' ?>>Wykonawca: A-Z</option>
            <option value="album" <?= $sort === 'album' ? 'selected' : '' ?>>Album: A-Z</option>
            <option value="rok-najnowsze" <?= $sort === 'rok-najnowsze' ? 'selected' : '' ?>>Rok: od najnowszego</option>
            <option value="rok-najstarsze" <?= $sort === 'rok-najstarsze' ? 'selected' : '' ?>>Rok: od najstarszego</option>
            <option value="cena-rosnaco" <?= $sort === 'cena-rosnaco' ? 'selected' : '' ?>>Cena: od najniższej</option>
            <option value="cena-malejaco" <?= $sort === 'cena-malejaco' ? 'selected' : '' ?>>Cena: od najwyższej</option>
        </select>

        <button class="btn btn-secondary" type="submit">Szukaj</button>
    </form>

    <?php if ($wyswietlono > 0): ?>
        <div class="tracks-list">
            <?php foreach ($utwory as $utwor): ?>
                <?php
                    $cena = $utwor['cena'] !== null
                        ? number_format((float) $utwor['cena'], 2, ',', ' ') . ' zł'
                        : '—';
                ?>
                <a class="track-row" href="utwor.php?id=<?= (int) $utwor['id'] ?>">
                    <span class="track-row-cover">
                        <?php if (!empty($utwor['okladka'])): ?>
                            <img src="<?= htmlspecialchars($utwor['okladka']) ?>" alt="Okładka albumu <?= htmlspecialchars($utwor['album_tytul']) ?>" loading="lazy">
                        <?php else: ?>
                            <span class="track-row-cover-fallback">♫</span>
                        <?php endif; ?>
                    </span>

                    <span class="track-row-main">
                        <span class="track-row-title"><?= htmlspecialchars($utwor['tytul']) ?></span>
                        <span class="track-row-meta">
                            <?= htmlspecialchars($utwor['wykonawca'] ?: 'Nieznany wykonawca') ?>
                            · <?= htmlspecialchars($utwor['album_tytul']) ?>
                            <?php if (!empty($utwor['rok'])): ?>
                                · <?= (int) $utwor['rok'] ?>
                            <?php endif; ?>
                        </span>
                    </span>

                    <span class="track-row-price" title="Cena albumu"><?= $cena ?></span>
                    <span class="track-row-time"><?= htmlspecialchars($utwor['czas'] ?: '—') ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php elseif ($search !== ''): ?>
        <p class="empty-state">Brak utworów pasujących do wyszukiwania.</p>
    <?php else: ?>
        <p class="empty-state">Brak utworów w katalogu.</p>
    <?php endif; ?>

</main>
<footer class="site-footer">
    <div class="footer-inner">
        <p>2026 - Przemysław Gałęziowski oraz Filip Klein</p>
    </div>
</footer>
<script src="assets/js/script_filmy.js"></script>
</body>
</html>