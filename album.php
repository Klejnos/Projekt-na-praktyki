<?php
    require_once "czy_zalogowany.php";

    require_once "polaczenie.php";

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $album = null;

    try {
        $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);

        if (!$polaczenie->connect_errno) {
            $polaczenie->set_charset("utf8mb4");

            $stmt = $polaczenie->prepare(
                "SELECT m.*, a.wykonawca, a.okladka, a.discogs_uri, a.rok AS album_rok, a.gatunek AS album_gatunek, t.nazwa AS typ
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

    $czyAdmin = isset($_SESSION['czy_admin']) && (int) $_SESSION['czy_admin'] === 1;
    $active = 'muzyka';
    $pageTitle = $album['tytul'];

    $dostepne = $album['ilosc_dostepnych'] !== null ? (int) $album['ilosc_dostepnych'] : 0;
    $cena = $album['cena'] !== null ? number_format((float) $album['cena'], 2, ',', ' ') . ' zł' : '—';
    $rok = $album['album_rok'] ?: $album['rok'];
    $gatunek = $album['album_gatunek'] ?: $album['gatunek'];
    $dostepnosc = $dostepne > 0 ? $dostepne . ' szt. na stanie' : 'Brak na stanie';

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
        <a href="muzyka.php" class="active">Muzyka</a>
        <a href="kontakt.php">Kontakt</a>
    </nav>
</header>
<main class="page">

    <div class="detail-breadcrumb">
        <a href="muzyka.php">Muzyka</a> / <?= htmlspecialchars($gatunek ?: 'Album') ?>
    </div>

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

    <div class="detail-layout">
        <div class="detail-poster">
            <?php if (!empty($album['okladka'])): ?>
                <img src="<?= htmlspecialchars($album['okladka']) ?>" alt="Okładka albumu <?= htmlspecialchars($album['tytul']) ?>">
            <?php else: ?>
                <div class="poster-fallback"></div>
            <?php endif; ?>
        </div>

        <div class="detail-info">
            <h1><?= htmlspecialchars($album['tytul']) ?></h1>

            <?php if (!empty($album['wykonawca'])): ?>
                <p class="detail-original"><?= htmlspecialchars($album['wykonawca']) ?></p>
            <?php endif; ?>

            <div class="detail-meta-grid">
                <div class="meta-item">
                    <span>Gatunek</span>
                    <strong><?= htmlspecialchars($gatunek ?: '—') ?></strong>
                </div>
                <div class="meta-item">
                    <span>Rok</span>
                    <strong><?= $rok ? (int) $rok : '—' ?></strong>
                </div>
                <div class="meta-item">
                    <span>Wykonawca</span>
                    <strong><?= htmlspecialchars($album['wykonawca'] ?: '—') ?></strong>
                </div>
                <div class="meta-item">
                    <span>Typ płyty</span>
                    <strong><?= htmlspecialchars($album['typ'] ?: '—') ?></strong>
                </div>
                <div class="meta-item">
                    <span>Dostępność</span>
                    <strong><?= $dostepnosc ?></strong>
                </div>
            </div>

            <div class="detail-price-row">
                <div class="detail-price">
                    <?= $cena ?>
                    <small>za 15 dni wypożyczenia</small>
                </div>

                <div class="detail-actions">
                    <a class="btn btn-primary" href="wypozycz.php?typ=muzyka&amp;id=<?= (int) $album['id'] ?>">Wypożycz teraz</a>

                    <?php if ($czyAdmin): ?>
                        <a class="btn btn-secondary" href="ulubione_muzyka.php?id=<?= (int) $album['id'] ?>&amp;powrot=album">
                            <span style="color: <?= !empty($album['czy_ulubiony']) ? '#ff6b81' : 'inherit' ?>;">
                                <?= !empty($album['czy_ulubiony']) ? '♥' : '♡' ?>
                            </span>
                            <?= !empty($album['czy_ulubiony']) ? 'Usuń z ulubionych' : 'Dodaj do ulubionych' ?>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($album['discogs_uri'])): ?>
                        <a class="btn btn-secondary" href="<?= htmlspecialchars($album['discogs_uri'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Discogs</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="detail-admin-actions">
                <?php if ($czyAdmin): ?>
                    <a class="btn btn-secondary" href="edytuj_muzyka.php?id=<?= (int) $album['id'] ?>">✎ Edytuj album</a>
                    <a class="btn btn-danger" href="usun_muzyka.php?id=<?= (int) $album['id'] ?>" onclick="return confirm('Czy na pewno usunąć ten album?');">🗑 Usuń</a>
                <?php endif; ?>
                <a class="btn btn-secondary" href="muzyka.php">← Wróć do katalogu</a>
            </div>
        </div>
    </div>

</main>
<footer class="site-footer">
    <div class="footer-inner">
        <span>&copy; <?= date('Y') ?> Wypożyczalnia filmów — filmy DVD i muzyka CD</span>
        <span>Gdańsk, ul. Rajska 4 · 10:00–18:00</span>
    </div>
</footer>
</body>
</html>
