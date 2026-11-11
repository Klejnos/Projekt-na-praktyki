<?php
    session_start();
    if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] == false) {
        header('Location: logowanie.php');
        exit();
    }

    require_once "polaczenie.php";

    $sort = $_GET['sort'] ?? 'default';

    switch ($sort) {
        case 'cena-rosnaco':
            $orderBy = 'cena IS NULL ASC, cena ASC';
            break;
        case 'cena-malejaco':
            $orderBy = 'cena IS NULL ASC, cena DESC';
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

        $wynik = $polaczenie->query(
            "SELECT id, tytul, tytul_oryginalny, gatunek, rok, ocena, cena, okladka
             FROM filmy
             ORDER BY $orderBy"
        );

        if ($wynik) {
            while ($row = $wynik->fetch_assoc()) {
                $filmy[] = $row;
            }
            $wynik->free();
        }

        $polaczenie->close();
    } catch (Exception $e) {
        // Strona pokaże stan pusty, jeśli baza jest niedostępna.
    }

    $czyAdmin = isset($_SESSION['czy_admin']) && (int) $_SESSION['czy_admin'] === 1;
    $kontoLink = $czyAdmin ? 'konto_admin.php' : 'konto_uzytkownik.php';
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
    ?>

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
        <p class="srodek"><span>Filmy dostępne w naszej wypożyczalni:</span>
        <select id="sortowanie">
            <option value="default" <?= $sort === 'default' ? 'selected' : '' ?>>Sortuj według</option>
            <option value="data-dodania" <?= $sort === 'data-dodania' ? 'selected' : '' ?>>Data dodania</option>
            <option value="cena-rosnaco" <?= $sort === 'cena-rosnaco' ? 'selected' : '' ?>>Cena: od najniższej</option>
            <option value="cena-malejaco" <?= $sort === 'cena-malejaco' ? 'selected' : '' ?>>Cena: od najwyższej</option>
            <option value="nazwa" <?= $sort === 'nazwa' ? 'selected' : '' ?>>Nazwa: A-Z</option>
        </select>
        </p>

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
