<?php
    session_start();
    if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] == false) {
        header('Location: logowanie.php');
        exit();
    }

    require_once "polaczenie.php";

    $polecaneFilmy = [];
    $polecaneAlbumy = [];

    try {
        $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);

        if (!$polaczenie->connect_errno) {
            $polaczenie->set_charset("utf8mb4");

            $wynik = $polaczenie->query(
                "SELECT id, tytul, tytul_oryginalny, gatunek, rok, ocena, cena, okladka
                 FROM filmy
                 WHERE czy_ulubiony = 1
                 ORDER BY id DESC
                 LIMIT 6"
            );

            if ($wynik) {
                while ($row = $wynik->fetch_assoc()) {
                    $polecaneFilmy[] = $row;
                }
                $wynik->free();
            }

            $wynik = $polaczenie->query(
                "SELECT m.id, m.tytul, m.rok, m.gatunek, m.cena, m.ilosc_dostepnych,
                        a.wykonawca, a.okladka
                 FROM muzyka m
                 LEFT JOIN albumy a ON m.album_id = a.id
                 WHERE m.czy_ulubiony = 1
                 ORDER BY m.id DESC
                 LIMIT 6"
            );

            if ($wynik) {
                while ($row = $wynik->fetch_assoc()) {
                    $polecaneAlbumy[] = $row;
                }
                $wynik->free();
            }

            $polaczenie->close();
        }
    } catch (Exception $e) {
        // Strona pokaże się bez sekcji polecanych.
    }

    $kontoLink = 'konto.php';
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/style_filmy.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wypożyczalnia filmów DVD oraz muzyki CD</title>
</head>
<body>
    <header>
        <h1>Wypożyczalnia filmów DVD oraz muzyki CD<a class="konto" href="<?= $kontoLink ?>">Konto</a></h1>
    </header>
    <nav>
        <a href="index.php">Strona główna</a> 
        <a href="filmy.php">Filmy</a>
        <a href="muzyka.php">Muzyka</a>
        <a href="kontakt.php">Kontakt</a>
    </nav>
    <main>
        <h2>Witamy w naszej wypożyczalni!</h2>
        <p>Oferujemy szeroki wybór filmów DVD, VHS, BLU-RAY oraz muzyki CD do wypożyczenia. Znajdziesz u nas najnowsze hity kinowe, klasyki filmowe oraz różnorodne gatunki muzyczne.</p>

        <?php if (count($polecaneFilmy) > 0): ?>
            <h2>Polecane filmy</h2>
            <p>Filmy oznaczone serduszkiem przez administratora.</p>

            <div class="film-grid">
                <?php foreach ($polecaneFilmy as $film): ?>
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

                                <span>
                                    <?= htmlspecialchars($film['gatunek'] ?: 'Film') ?><?= $film['rok'] ? ' · ' . (int) $film['rok'] : '' ?>
                                </span>

                                <span>
                                    Ocena: <?= $film['ocena'] !== null ? number_format((float) $film['ocena'], 1, ',', '') : '—' ?>
                                    <?= $film['cena'] !== null ? ' | Cena: ' . number_format((float) $film['cena'], 2, ',', ' ') . ' zł' : '' ?>
                                </span>
                            </div>
                        </a>

                        <div class="admin-actions">
                            <a class="przycisk" href="wypozycz.php?typ=film&amp;id=<?= (int) $film['id'] ?>">Wypożycz</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (count($polecaneAlbumy) > 0): ?>
            <h2>Polecane albumy</h2>
            <p>Albumy oznaczone serduszkiem przez administratora.</p>

            <div class="film-grid">
                <?php foreach ($polecaneAlbumy as $album): ?>
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
                                    Cena: <?= $album['cena'] !== null ? number_format((float) $album['cena'], 2, ',', ' ') . ' zł' : '—' ?>
                                </span>
                            </div>
                        </a>

                        <div class="admin-actions">
                            <a class="przycisk" href="wypozycz.php?typ=muzyka&amp;id=<?= (int) $album['id'] ?>">Wypożycz</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <p>Autorzy: Przemysław Gałęziowski i Filip Klein</p>
    </footer>
</body>
</html>
