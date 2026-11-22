<?php
// film.php - strona szczegółów filmu.
// Pokazuje dane, cenę, przycisk wypożyczenia i akcje administratora.


    require_once "czy_zalogowany.php";

    require_once "polaczenie.php";

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $film = null;

    try {
        $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);

        if (!$polaczenie->connect_errno) {
            $polaczenie->set_charset("utf8mb4");

            // Pobranie filmu wraz z nazwą typu płyty.
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

    $czyAdmin = isset($_SESSION['czy_admin']) && (int) $_SESSION['czy_admin'] === 1;

    // Dane przygotowane do wyświetlenia w szczegółach filmu.
    $dostepne = $film['ilosc_dostepnych'] !== null ? (int) $film['ilosc_dostepnych'] : 0;
    $ocena = $film['ocena'] !== null ? number_format((float) $film['ocena'], 1, ',', '') : '-';
    $cena = $film['cena'] !== null ? number_format((float) $film['cena'], 2, ',', ' ') . ' zł' : '-';
    $czas = $film['czas_trwania'] !== null ? (int) $film['czas_trwania'] . ' min' : '-';
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
        <a href="filmy.php" class="active">Filmy</a>
        <a href="muzyka.php">Muzyka</a>
        <a href="utwory.php">Utwory</a>
        <a href="kontakt.php">Kontakt</a>
    </nav>
</header>
<main class="page">

    <div class="detail-breadcrumb">
        <a href="filmy.php">Filmy</a> / <?= htmlspecialchars($film['gatunek'] ?: 'Film') ?>
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

        if (!empty($_SESSION['ulubione_film_komunikat'])) {
            echo '<div class="sukces">' . htmlspecialchars($_SESSION['ulubione_film_komunikat']) . '</div>';
            unset($_SESSION['ulubione_film_komunikat']);
        }
    ?>

    <div class="detail-layout">
        <div class="detail-poster">
            <?php if (!empty($film['okladka'])): ?>
                <img src="<?= htmlspecialchars($film['okladka']) ?>" alt="Okładka filmu <?= htmlspecialchars($film['tytul']) ?>">
            <?php else: ?>
                <div class="poster-fallback"></div>
            <?php endif; ?>
        </div>

        <div class="detail-info">
            <h1><?= htmlspecialchars($film['tytul']) ?></h1>

            <?php if (!empty($film['tytul_oryginalny']) && $film['tytul_oryginalny'] !== $film['tytul']): ?>
                <p class="detail-original"><?= htmlspecialchars($film['tytul_oryginalny']) ?></p>
            <?php endif; ?>

            <div class="detail-meta-grid">
                <div class="meta-item">
                    <span>Gatunek</span>
                    <strong><?= htmlspecialchars($film['gatunek'] ?: '-') ?></strong>
                </div>
                <div class="meta-item">
                    <span>Rok</span>
                    <strong><?= $film['rok'] ? (int) $film['rok'] : '-' ?></strong>
                </div>
                <div class="meta-item">
                    <span>Ocena</span>
                    <strong><span class="rating-star">★</span> <?= $ocena ?></strong>
                </div>
                <div class="meta-item">
                    <span>Reżyser</span>
                    <strong><?= htmlspecialchars($film['rezyser'] ?: '-') ?></strong>
                </div>
                <div class="meta-item">
                    <span>Czas trwania</span>
                    <strong><?= $czas ?></strong>
                </div>
                <div class="meta-item">
                    <span>Nośnik</span>
                    <strong><?= htmlspecialchars($film['typ'] ?: '-') ?></strong>
                </div>
                <div class="meta-item">
                    <span>Język</span>
                    <strong><?= htmlspecialchars($film['jezyk'] ?: '-') ?></strong>
                </div>
                <div class="meta-item">
                    <span>Dostępność</span>
                    <strong><?= $dostepnosc ?></strong>
                </div>
            </div>

            <div class="detail-price-row">
                <div class="detail-price">
                    <?= $cena ?>
                    <small>za 10 dni wypożyczenia</small>
                </div>

                <div class="detail-actions">
                    <a class="btn btn-primary" href="wypozycz.php?typ=film&amp;id=<?= (int) $film['id'] ?>">Wypożycz teraz</a>

                    <?php if ($czyAdmin): ?>
                        <a class="btn btn-secondary" href="ulubione_film.php?id=<?= (int) $film['id'] ?>&amp;powrot=film">
                            <span style="color: <?= !empty($film['czy_ulubiony']) ? '#ff6b81' : 'inherit' ?>;">
                                <?= !empty($film['czy_ulubiony']) ? '♥' : '♡' ?>
                            </span>
                            <?= !empty($film['czy_ulubiony']) ? 'Usuń z ulubionych' : 'Dodaj do ulubionych' ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="detail-description">
                <h2>Opis</h2>
                <p><?= nl2br(htmlspecialchars($film['opis'] ?: 'Brak opisu.')) ?></p>
            </div>

            <div class="detail-admin-actions">
                <?php if ($czyAdmin): ?>
                    <a class="btn btn-secondary" href="edytuj_film.php?id=<?= (int) $film['id'] ?>">Edytuj film</a>
                    <a class="btn btn-danger" href="usun_film.php?id=<?= (int) $film['id'] ?>" onclick="return confirm('Czy na pewno usunąć ten film?');">Usuń</a>
                <?php endif; ?>
                <a class="btn btn-secondary" href="filmy.php">Wróć do katalogu</a>
            </div>
        </div>
    </div>

</main>
<footer class="site-footer">
    <div class="footer-inner">
        <p>2026 - Przemysław Gałęziowski oraz Filip Klein</p>
    </div>
</footer>
</body>
</html>