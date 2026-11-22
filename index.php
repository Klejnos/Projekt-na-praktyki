<?php
// index.php — strona główna.
// Wyświetla polecane filmy, albumy i utwory oraz skróty do katalogów.


    require_once "czy_zalogowany.php";

    require_once "polaczenie.php";

    $polecaneFilmy = [];
    $polecaneAlbumy = [];
    $polecaneUtwory = [];
    $typy = [];

    try {
        $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);

        if (!$polaczenie->connect_errno) {
            $polaczenie->set_charset("utf8mb4");

            // Mapa typów płyt (ID -> nazwa) używana na kartach.
            $wynik = $polaczenie->query("SELECT id, nazwa FROM typy_plyt");
            if ($wynik) {
                while ($row = $wynik->fetch_assoc()) {
                    $typy[(int) $row['id']] = $row['nazwa'];
                }
                $wynik->free();
            }

            // Polecane filmy oznaczone serduszkiem.
            $wynik = $polaczenie->query(
                "SELECT id, tytul, tytul_oryginalny, gatunek, rok, ocena, cena,
                        ilosc_dostepnych, okladka, typ_plyty_id
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

            // Polecane albumy muzyczne.
            $wynik = $polaczenie->query(
                "SELECT m.id, m.tytul, m.rok, m.gatunek, m.cena, m.ilosc_dostepnych,
                        a.wykonawca, a.okladka, t.nazwa AS typ
                 FROM muzyka m
                 LEFT JOIN albumy a ON m.album_id = a.id
                 LEFT JOIN typy_plyt t ON a.typ_plyty_id = t.id
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

            // Polecane utwory; cena pochodzi z utwory.cena.
            $wynik = $polaczenie->query(
                "SELECT u.id, u.tytul, a.rok, a.gatunek, u.cena, u.ilosc_dostepnych,
                        a.wykonawca, a.okladka, t.nazwa AS typ
                 FROM utwory u
                 LEFT JOIN albumy a ON u.album_id = a.id
                 LEFT JOIN typy_plyt t ON a.typ_plyty_id = t.id
                 WHERE u.czy_ulubiony = 1
                 ORDER BY u.id DESC
                 LIMIT 6"
            );

            if ($wynik) {
                while ($row = $wynik->fetch_assoc()) {
                    $polecaneUtwory[] = $row;
                }
                $wynik->free();
            }

            $polaczenie->close();
        }
    } catch (Exception $e) {
        // Strona pokaże się bez sekcji polecanych.
    }

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
        <a href="index.php" class="active">Strona główna</a>
        <a href="filmy.php">Filmy</a>
        <a href="muzyka.php">Muzyka</a>
        <a href="utwory.php">Utwory</a>
        <a href="kontakt.php">Kontakt</a>
    </nav>
</header>
<main class="page">

    <section class="catalog-head">
        <div>
            <h1>Witamy w wypożyczalni</h1>
            <p class="catalog-meta">Filmy oraz muzyka DVD, Blu-ray, VHS i CD</p>
        </div>
    </section>

    <?php if (count($polecaneFilmy) > 0): ?>
        <section class="catalog-head" style="margin-top: 34px;">
            <div>
                <h2>Polecane filmy</h2>
                <p class="catalog-meta">Filmy oznaczone serduszkiem przez administratora.</p>
            </div>
        </section>

        <div class="catalog-grid">
            <?php foreach ($polecaneFilmy as $film): ?>
                <?php
                    $typ = $typy[(int) ($film['typ_plyty_id'] ?? 0)] ?? 'DVD';
                    $dostepne = $film['ilosc_dostepnych'] !== null ? (int) $film['ilosc_dostepnych'] : 0;
                    $ocena = $film['ocena'] !== null ? number_format((float) $film['ocena'], 1, ',', '') : '-';
                    $cena = $film['cena'] !== null ? number_format((float) $film['cena'], 2, ',', ' ') . ' zł' : '-';
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
                    </div>

                    <div class="catalog-body">
                        <h3 class="catalog-title"><a href="<?= $url ?>"><?= htmlspecialchars($film['tytul']) ?></a></h3>

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
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (count($polecaneAlbumy) > 0): ?>
        <section class="catalog-head" style="margin-top: 34px;">
            <div>
                <h2>Polecane albumy</h2>
                <p class="catalog-meta">Albumy oznaczone serduszkiem przez administratora.</p>
            </div>
        </section>

        <div class="catalog-grid">
            <?php foreach ($polecaneAlbumy as $album): ?>
                <?php
                    $typ = $album['typ'] ?: 'CD';
                    $dostepne = $album['ilosc_dostepnych'] !== null ? (int) $album['ilosc_dostepnych'] : 0;
                    $cena = $album['cena'] !== null ? number_format((float) $album['cena'], 2, ',', ' ') . ' zł' : '-';
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
                    </div>

                    <div class="catalog-body">
                        <h3 class="catalog-title"><a href="<?= $url ?>"><?= htmlspecialchars($album['tytul']) ?></a></h3>

                        <?php if (!empty($album['wykonawca'])): ?>
                            <p class="catalog-original"><?= htmlspecialchars($album['wykonawca']) ?></p>
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
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (count($polecaneUtwory) > 0): ?>
        <section class="catalog-head" style="margin-top: 34px;">
            <div>
                <h2>Polecane Utwory</h2>
                <p class="catalog-meta">Utwory oznaczone serduszkiem przez administratora.</p>
            </div>
        </section>

        <div class="catalog-grid">
            <?php foreach ($polecaneUtwory as $utwor): ?>
                <?php
                    $typ = $utwor['typ'] ?: 'CD';
                    $dostepne = $utwor['ilosc_dostepnych'] !== null ? (int) $utwor['ilosc_dostepnych'] : 0;
                    $cena = $utwor['cena'] !== null ? number_format((float) $utwor['cena'], 2, ',', ' ') . ' zł' : '-';
                    $url = 'utwor.php?id=' . (int) $utwor['id'];
                ?>
                <article class="catalog-card">
                    <div class="catalog-poster">
                        <a href="<?= $url ?>">
                            <?php if (!empty($utwor['okladka'])): ?>
                                <img src="<?= htmlspecialchars($utwor['okladka']) ?>" alt="Okładka utworu <?= htmlspecialchars($utwor['tytul']) ?>">
                            <?php else: ?>
                                <div class="poster-fallback"></div>
                            <?php endif; ?>
                        </a>
                        <span class="media-badge"><?= htmlspecialchars($typ) ?></span>
                    </div>

                    <div class="catalog-body">
                        <h3 class="catalog-title"><a href="<?= $url ?>"><?= htmlspecialchars($utwor['tytul']) ?></a></h3>

                        <?php if (!empty($utwor['wykonawca'])): ?>
                            <p class="catalog-original"><?= htmlspecialchars($utwor['wykonawca']) ?></p>
                        <?php endif; ?>

                        <p class="catalog-meta">
                            <span><?= htmlspecialchars($utwor['gatunek'] ?: 'Muzyka') ?></span>
                            <?php if (!empty($utwor['rok'])): ?>
                                <span>· <?= (int) $utwor['rok'] ?></span>
                            <?php endif; ?>
                        </p>

                        <div class="catalog-bottom">
                            <span class="availability <?= $dostepne > 0 ? '' : 'out' ?>">
                                <?= $dostepne > 0 ? 'Dostępne: ' . $dostepne : 'Brak na stanie' ?>
                            </span>
                            <span class="price"><?= $cena ?></span>
                        </div>

                        <div class="catalog-actions">
                            <a class="btn btn-primary" href="wypozycz.php?typ=muzyka&amp;id=<?= (int) $utwor['id'] ?>">Wypożycz</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</main>
<br><footer class="site-footer">
    <div class="footer-inner">
        <p>2026 - Przemysław Gałęziowski i Filip Klein</p>
    </div>
</footer>
</body>
</html>