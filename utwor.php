<?php
    require_once "czy_zalogowany.php";
    require_once "polaczenie.php";

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $utwor = null;

    try {
        $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);

        if (!$polaczenie->connect_errno) {
            $polaczenie->set_charset("utf8mb4");

            $stmt = $polaczenie->prepare(
                "SELECT u.id, u.album_id, u.tytul, u.czas,
                        a.tytul AS album_tytul, a.wykonawca, a.rok AS album_rok,
                        a.gatunek AS album_gatunek, a.okladka, a.discogs_uri,
                        u.cena, u.ilosc_dostepnych, u.czy_ulubiony
                 FROM utwory u
                 INNER JOIN albumy a ON a.id = u.album_id
                 WHERE u.id = ?
                 LIMIT 1"
            );

            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $wynik = $stmt->get_result();

                if ($wynik && $wynik->num_rows === 1) {
                    $utwor = $wynik->fetch_assoc();
                }

                $stmt->close();
            }

            $polaczenie->close();
        }
    } catch (Exception $e) {
        // Poniżej przekierowanie, jeśli utworu nie udało się pobrać.
    }

    if (!$utwor) {
        header('Location: utwory.php');
        exit();
    }

    $czyAdmin = isset($_SESSION['czy_admin']) && (int) $_SESSION['czy_admin'] === 1;
    $active = 'utwory';
    $pageTitle = $utwor['tytul'];
    $cena = $utwor['cena'] !== null
        ? number_format((float) $utwor['cena'], 2, ',', ' ') . ' zł'
        : '—';
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — Wypożyczalnia</title>
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

    <div class="detail-breadcrumb">
        <a href="utwory.php">Utwory</a> /
        <a href="album.php?id=<?= (int) $utwor['album_id'] ?>"><?= htmlspecialchars($utwor['album_tytul']) ?></a> /
        <?= htmlspecialchars($utwor['tytul']) ?>
    </div>

    <?php
        if (!empty($_SESSION['rent_sukces'])) {
            echo '<div class="sukces">' . htmlspecialchars($_SESSION['rent_sukces']) . '</div>';
            unset($_SESSION['rent_sukces']);
        }

        if (!empty($_SESSION['wypozycz_utwor_sukces'])) {
            echo '<div class="sukces">' . htmlspecialchars($_SESSION['wypozycz_utwor_sukces']) . '</div>';
            unset($_SESSION['wypozycz_utwor_sukces']);
        }

        if (!empty($_SESSION['rent_blad'])) {
            echo '<div class="error">' . htmlspecialchars($_SESSION['rent_blad']) . '</div>';
            unset($_SESSION['rent_blad']);
        }

        if (!empty($_SESSION['wypozycz_utwor_blad'])) {
            echo '<div class="error">' . htmlspecialchars($_SESSION['wypozycz_utwor_blad']) . '</div>';
            unset($_SESSION['wypozycz_utwor_blad']);
        }

        if (!empty($_SESSION['ulubione_komunikat'])) {
            echo '<div class="sukces">' . htmlspecialchars($_SESSION['ulubione_komunikat']) . '</div>';
            unset($_SESSION['ulubione_komunikat']);
        }

        if (!empty($_SESSION['ulubione_utwor_komunikat'])) {
            echo '<div class="sukces">' . htmlspecialchars($_SESSION['ulubione_utwor_komunikat']) . '</div>';
            unset($_SESSION['ulubione_utwor_komunikat']);
        }
    ?>

    <div class="detail-layout">
        <div class="detail-poster">
            <?php if (!empty($utwor['okladka'])): ?>
                <img src="<?= htmlspecialchars($utwor['okladka']) ?>" alt="Okładka <?= htmlspecialchars($utwor['tytul']) ?>">
            <?php else: ?>
                <div class="poster-fallback"></div>
            <?php endif; ?>
        </div>

        <div class="detail-info">
            <h1><?= htmlspecialchars($utwor['tytul']) ?></h1>

            <p class="detail-original"><?= htmlspecialchars($utwor['wykonawca'] ?: 'Nieznany wykonawca') ?></p>

            <div class="detail-meta-grid">
                <div class="meta-item">
                    <span>Album</span>
                    <strong><?= htmlspecialchars($utwor['album_tytul']) ?></strong>
                </div>
                <div class="meta-item">
                    <span>Wykonawca</span>
                    <strong><?= htmlspecialchars($utwor['wykonawca'] ?: '-') ?></strong>
                </div>
                <div class="meta-item">
                    <span>Czas trwania</span>
                    <strong><?= htmlspecialchars($utwor['czas'] ?: '-') ?></strong>
                </div>
                <div class="meta-item">
                    <span>Rok wydania</span>
                    <strong><?= !empty($utwor['album_rok']) ? (int) $utwor['album_rok'] : '-' ?></strong>
                </div>
                <div class="meta-item">
                    <span>Gatunek</span>
                    <strong><?= htmlspecialchars($utwor['album_gatunek'] ?: '-') ?></strong>
                </div>
            </div>

            <div class="detail-price-row">
                <div class="detail-price">
                    <?= $cena ?>
                    <small>za 15 dni wypożyczenia</small>
                </div>

                <div class="detail-actions">
                    <a class="btn btn-primary" href="wypozycz.php?typ=utwor&amp;id=<?= (int) $utwor['id'] ?>">Wypożycz teraz</a>

                    <?php if ($czyAdmin): ?>
                        <a class="btn btn-secondary" href="ulubione_utwor.php?id=<?= (int) $utwor['id'] ?>&amp;powrot=utwor">
                            <span style="color: <?= !empty($utwor['czy_ulubiony']) ? '#ff6b81' : 'inherit' ?>;">
                                <?= !empty($utwor['czy_ulubiony']) ? '♥' : '♡' ?>
                            </span>
                            <?= !empty($utwor['czy_ulubiony']) ? 'Usuń z ulubionych' : 'Dodaj do ulubionych' ?>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($utwor['discogs_uri'])): ?>
                        <a class="btn btn-secondary" href="<?= htmlspecialchars($utwor['discogs_uri'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Discogs</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="detail-admin-actions">
                <?php if ($czyAdmin): ?>
                    <a class="btn btn-secondary" href="edytuj_utwor.php?id=<?= (int) $utwor['id'] ?>">Edytuj utwór</a>
                    <a class="btn btn-danger" href="usun_utwor.php?id=<?= (int) $utwor['id'] ?>" onclick="return confirm('Czy na pewno usunąć ten utwór?');">Usuń</a>
                <?php endif; ?>
                <a class="btn btn-secondary" href="utwory.php">Wróć do utworów</a>
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