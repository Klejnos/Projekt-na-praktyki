<?php
    require_once "czy_zalogowany.php";

    $czy_admin = true;
    if (!isset($_SESSION['czy_admin']) || (int) $_SESSION['czy_admin'] !== 1) {
        $czy_admin = false;
    }

    require_once "polaczenie.php";
    try {
        $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
        $id_u = $_SESSION['id'];
        $q_u = $polaczenie -> prepare("SELECT portfel FROM uzytkownicy WHERE id = ?");
        $q_u -> bind_param ("i", $id_u);
        $q_u -> execute();
        $wynik = $q_u -> get_result();
        if ($wynik -> num_rows !== 1) {
            throw new Exception("Nie znaleziono użytkownika");
        }
        $wiersz_u = $wynik -> fetch_assoc();
        $portfel = (float) $wiersz_u['portfel'];
        $q_u -> close();
    }
    catch (Exception $e) {
        echo "Błąd serwera";
    }

    function pole(string $etykieta, $wartosc): void
    {
        if ($wartosc !== null && $wartosc !== '') {
            echo '<p><b>' . $etykieta . ':</b> ' . htmlspecialchars((string) $wartosc) . '</p>';
        }
    }
?>


<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twoje konto</title>
    <link rel="icon" type="image/x-icon" href="assets/img/icon.ico"> 
    <link rel="stylesheet" href="assets/css/konto.css">
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
                <a href="konto.php" class="top-btn active">Konto</a>
                <a href="wylogowanie.php" class="top-btn">Wyloguj się</a>
            </div>
        </div>
        <nav class="mainnav">
            <a href="index.php">Strona główna</a> 
            <a href="filmy.php">Filmy</a>
            <a href="muzyka.php">Muzyka</a>
            <a href="utwory.php">Utwory</a>
            <a href="kontakt.php">Kontakt</a>
        </nav>
    </header>

    <main>
        <?php if (!empty($_SESSION['konto_komunikat'])): ?>
            <div class="sukces"><?= htmlspecialchars($_SESSION['konto_komunikat']) ?></div>
            <?php unset($_SESSION['konto_komunikat']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['e_konto_komunikat'])): ?>
            <div class="error"><?= htmlspecialchars($_SESSION['e_konto_komunikat']) ?></div>
            <?php unset($_SESSION['e_konto_komunikat']); ?>
        <?php endif; ?>

        <div class="account-header">
            <?php if($czy_admin) : ?>
                <span class="admin-badge">Panel administratora</span>
            <?php endif; ?>
            
            <h2>Witaj, <?= htmlspecialchars($_SESSION['nazwa']) ?></h2>

            <div class="account-actions">
                <?php if($czy_admin): ?>
                    <a class="przycisk btn-primary" href="dodawanie_filmow.php">Dodaj film</a>
                    <a class="przycisk btn-primary" href="dodawanie_muzyka.php">Dodaj muzykę</a>
                    <a class="przycisk btn-primary" href="dodawanie_utwor.php">Dodaj utwór</a>
                <?php endif; ?>
                <div>
                    <a class="przycisk" href="filmy.php">Przeglądaj filmy</a>
                    <a class="przycisk" href="muzyka.php">Przeglądaj muzykę</a>
                    <a class="przycisk" href="utwory.php">Przeglądaj utwory</a>
                </div>
            </div>
        </div>

        <div class="account-header">
            <div>
                <h1>Twój portfel wynosi aktualnie: <span style="color: green;"><?= $portfel ?></span> zł</h1>
                <h4>Wpłać pieniądze, aby móc wypożyczać filmy</h4>
                <a class="przycisk btn-primary" href="doladowanie.php?k=10">10 zł</a>
                <a class="przycisk btn-primary" href="doladowanie.php?k=20">20 zł</a>
                <a class="przycisk btn-primary" href="doladowanie.php?k=35">35 zł</a>
                <a class="przycisk btn-primary" href="doladowanie.php?k=50">50 zł</a>
            </div>
        </div>

        <section class="rentals-section">
            <h2>Twoje wypożyczenia - filmy:</h2>
            <?php
                $id = $_SESSION['id'];
                $q = $polaczenie -> query("SELECT f.*, t.nazwa AS nazwa_typu, w.* FROM filmy f LEFT JOIN typy_plyt t ON f.typ_plyty_id = t.id JOIN wypozyczenia_filmy w ON w.film_id = f.id WHERE w.uzytkownicy_id = '$id'");
                $typ = 'film';
                if ($q && $q -> num_rows > 0) {
                    while ($wiersz = $q -> fetch_assoc()) {
                        ?>
                        <div class="film-page">
                            <?php if (!empty($wiersz['okladka'])): ?>
                                <img class="film-page-cover" src="<?= htmlspecialchars($wiersz['okladka']) ?>" alt="Okładka filmu <?= htmlspecialchars($wiersz['tytul']) ?>">
                            <?php else: ?>
                                <div class="film-page-cover film-cover--empty"><span>Brak okładki</span></div>
                            <?php endif; ?>

                            <div class="film-page-info">
                                <h3><?= htmlspecialchars($wiersz['tytul']) ?></h3>

                                <div class="film-meta-list">
                                    <?php
                                        if (!empty($wiersz['tytul_oryginalny']) && $wiersz['tytul_oryginalny'] !== $wiersz['tytul']) {
                                            pole('Tytuł oryginalny', $wiersz['tytul_oryginalny']);
                                        }

                                        pole('Gatunek', $wiersz['gatunek']);
                                        pole('Rok', $wiersz['rok']);
                                        pole('Ocena', $wiersz['ocena'] !== null ? number_format((float) $wiersz['ocena'], 1, ',', '') : null);
                                        pole('Reżyser', $wiersz['rezyser']);
                                        pole('Czas trwania', $wiersz['czas_trwania'] !== null ? $wiersz['czas_trwania'] . ' min' : null);
                                        pole('Język', $wiersz['jezyk']);
                                        pole('Typ płyty', $wiersz['nazwa_typu']);
                                        pole('Data wypożyczenia', $wiersz['data_wypozyczenia']);
                                        pole('Termin zwrotu', $wiersz['data_zwrotu']);
                                        pole('Cena', $wiersz['cena'] !== null ? number_format((float) $wiersz['cena'], 2, ',', ' ') . ' zł' : null);
                                        pole('Opis', $wiersz['opis']);
                                    ?>
                                </div>

                                <div class="rental-buttons">
                                    <a class="przycisk" href="zwrot.php?id=<?= (int) $wiersz['film_id'] ?>&amp;typ=<?= $typ ?>">Zwróć film</a>
                                    <a class="przycisk" href="przedluz_termin.php?id=<?= (int) $wiersz['film_id'] ?>&amp;typ=<?= $typ ?>">Przedłuż termin zwrotu</a>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                }
                else {
                    echo "<p class='empty-state'>Nie masz aktualnie wypożyczonych żadnych filmów</p>";
                }
            ?>
        </section>

        <section class="rentals-section">
            <h2>Twoje wypożyczenia - albumy:</h2>
            <?php
                $q_muzyka = $polaczenie -> query("SELECT m.*, a.wykonawca, a.okladka, w.data_wypozyczenia, w.data_zwrotu, w.muzyka_id 
                FROM muzyka m LEFT JOIN albumy a ON m.album_id = a.id 
                JOIN wypozyczenia_muzyka w ON w.muzyka_id = m.id WHERE w.uzytkownicy_id = '$id'");
                $typ = 'muzyka';
                if ($q_muzyka && $q_muzyka -> num_rows > 0) {
                    while ($wiersz_m = $q_muzyka -> fetch_assoc()) {
                        ?>
                        <div class="film-page">
                            <?php if (!empty($wiersz_m['okladka'])): ?>
                                <img class="film-page-cover" src="<?= htmlspecialchars($wiersz_m['okladka']) ?>" alt="Okładka albumu <?= htmlspecialchars($wiersz_m['tytul']) ?>">
                            <?php else: ?>
                                <div class="film-page-cover film-cover--empty"><span>Brak okładki</span></div>
                            <?php endif; ?>
                            
                            <div class="film-page-info">
                                <h3><?= htmlspecialchars($wiersz_m['tytul']) ?></h3>

                                <div class="film-meta-list">
                                    <?php
                                        if (!empty($wiersz_m['wykonawca'])) {
                                            pole('Wykonawca', $wiersz_m['wykonawca']);
                                        }
                                        pole('Gatunek', $wiersz_m['gatunek']);
                                        pole('Rok', $wiersz_m['rok']);
                                        pole('Data wypożyczenia', $wiersz_m['data_wypozyczenia']);
                                        pole('Termin zwrotu', $wiersz_m['data_zwrotu']);
                                        pole('Cena', $wiersz_m['cena'] !== null ? number_format((float) $wiersz_m['cena'], 2, ',', ' ') . ' zł' : null);
                                    ?>
                                </div>

                                <div class="rental-buttons">
                                    <a class="przycisk" href="zwrot.php?id=<?= (int) $wiersz_m['muzyka_id'] ?>&amp;typ=<?= $typ ?>">Zwróć muzykę</a>
                                    <a class="przycisk" href="przedluz_termin.php?id=<?= (int) $wiersz_m['muzyka_id'] ?>&amp;typ=<?= $typ ?>">Przedłuż termin zwrotu</a>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                }
                else {
                    echo "<p class='empty-state'>Nie masz wypożyczonych żadnych albumów muzycznych</p>";
                }
            ?>
        </section>

        <section class="rentals-section">
            <h2>Twoje wypożyczenia - utwory:</h2>
            <?php
                $q_utwor = $polaczenie -> query("SELECT u.*, a.wykonawca, a.gatunek, a.rok, a.okladka, w.data_wypozyczenia, w.data_zwrotu, w.utwor_id 
                FROM utwory u LEFT JOIN albumy a ON u.album_id = a.id 
                JOIN wypozyczenia_utwory w ON w.utwor_id = u.id WHERE w.uzytkownicy_id = '$id'");
                $typ = 'utwor';
                if ($q_utwor && $q_utwor -> num_rows > 0) {
                    while ($wiersz_u = $q_utwor -> fetch_assoc()) {
                        ?>
                        <div class="film-page">
                            <?php if (!empty($wiersz_u['okladka'])): ?>
                                <img class="film-page-cover" src="<?= htmlspecialchars($wiersz_u['okladka']) ?>" alt="Okładka albumu <?= htmlspecialchars($wiersz_u['tytul']) ?>">
                            <?php else: ?>
                                <div class="film-page-cover film-cover--empty"><span>Brak okładki</span></div>
                            <?php endif; ?>
                            
                            <div class="film-page-info">
                                <h3><?= htmlspecialchars($wiersz_u['tytul']) ?></h3>

                                <div class="film-meta-list">
                                    <?php
                                        if (!empty($wiersz_u['wykonawca'])) {
                                            pole('Wykonawca', $wiersz_u['wykonawca']);
                                        }
                                        pole('Gatunek', $wiersz_u['gatunek']);
                                        pole('Rok', $wiersz_u['rok']);
                                        pole('Data wypożyczenia', $wiersz_u['data_wypozyczenia']);
                                        pole('Termin zwrotu', $wiersz_u['data_zwrotu']);
                                        pole('Cena', $wiersz_u['cena'] !== null ? number_format((float) $wiersz_u['cena'], 2, ',', ' ') . ' zł' : null);
                                    ?>
                                </div>

                                <div class="rental-buttons">
                                    <a class="przycisk" href="zwrot.php?id=<?= (int) $wiersz_u['utwor_id'] ?>&amp;typ=<?= $typ ?>">Zwróć utwór</a>
                                    <a class="przycisk" href="przedluz_termin.php?id=<?= (int) $wiersz_u['utwor_id'] ?>&amp;typ=<?= $typ ?>">Przedłuż termin zwrotu</a>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                }
                else {
                    echo "<p class='empty-state'>Nie masz wypożyczonych żadnych albumów muzycznych</p>";
                }
            ?>
        </section>
    </main>

    <footer class="site-footer">
        <div class="footer-inner">
            <p>2026 - Przemysław Gałęziowski i Filip Klein</p>
        </div>
    </footer>
</body>
</html>