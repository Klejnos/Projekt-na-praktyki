<?php
// edytuj_film.php — edycja filmu (tylko administrator).
// Zmienia dane filmu, okładkę, typ płyty i cenę wypożyczenia.


    require_once "czy_zalogowany.php";

    // Edycja filmów jest dostępna tylko dla administratora.
    if (!isset($_SESSION['czy_admin']) || (int) $_SESSION['czy_admin'] !== 1) {
        header('Location: index.php');
        exit();
    }

    require_once "polaczenie.php";

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $film = null;
    $blad = '';
    $typyPlyt = [];

    try {
        $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
        if ($polaczenie->connect_errno) {
            throw new Exception();
        }

        $polaczenie->set_charset("utf8mb4");

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Odczyt nowych danych filmu z formularza.
            $tytul = trim($_POST['tytul'] ?? '');
            $tytulOryginalny = trim($_POST['tytul_oryginalny'] ?? '');
            $gatunek = trim($_POST['gatunek'] ?? '');
            $rok = (($_POST['rok'] ?? '') !== '') ? (int) $_POST['rok'] : null;
            $ocena = (($_POST['ocena'] ?? '') !== '') ? (float) $_POST['ocena'] : null;
            $rezyser = trim($_POST['rezyser'] ?? '');
            $czasTrwania = (($_POST['czas_trwania'] ?? '') !== '') ? (int) $_POST['czas_trwania'] : null;
            $jezyk = trim($_POST['jezyk'] ?? '');
            $ilosc = (($_POST['ilosc_dostepnych'] ?? '') !== '') ? (int) $_POST['ilosc_dostepnych'] : null;
            $budzet = (($_POST['budzet'] ?? '') !== '') ? (float) $_POST['budzet'] : null;
            $produkcja = trim($_POST['produkcja'] ?? '');
            $nagrody = trim($_POST['nagrody'] ?? '');
            $cena = (($_POST['cena'] ?? '') !== '') ? (float) $_POST['cena'] : null;
            $opis = trim($_POST['opis'] ?? '');
            $okladka = trim($_POST['okladka'] ?? '');
            $typPlyty = (($_POST['typ_plyty'] ?? '') !== '') ? (int) $_POST['typ_plyty'] : null;

            if ($tytul === '') {
                $blad = 'Tytuł filmu jest wymagany.';
            } else {
                $stmt = $polaczenie->prepare(
                    "UPDATE filmy SET
                        tytul = ?, tytul_oryginalny = ?, gatunek = ?, rok = ?, ocena = ?,
                        rezyser = ?, czas_trwania = ?, jezyk = ?, ilosc_dostepnych = ?,
                        budzet = ?, produkcja = ?, nagrody = ?, cena = ?, opis = ?,
                        okladka = ?, typ_plyty_id = ?
                     WHERE id = ?"
                );

                if ($stmt) {
                    $stmt->bind_param(
                        "sssidsisidssdssii",
                        $tytul,
                        $tytulOryginalny,
                        $gatunek,
                        $rok,
                        $ocena,
                        $rezyser,
                        $czasTrwania,
                        $jezyk,
                        $ilosc,
                        $budzet,
                        $produkcja,
                        $nagrody,
                        $cena,
                        $opis,
                        $okladka,
                        $typPlyty,
                        $id
                    );

                    if ($stmt->execute()) {
                        $stmt->close();
                        $polaczenie->close();
                        $_SESSION['edytowano_film'] = true;
                        header("Location: filmy.php");
                        exit();
                    }

                    $blad = 'Nie udało się zapisać zmian: ' . $stmt->error;
                    $stmt->close();
                } else {
                    $blad = 'Nie udało się przygotować zapytania: ' . $polaczenie->error;
                }
            }
        }

        // Pobranie aktualnych danych filmu do formularza.
        $stmt = $polaczenie->prepare("SELECT * FROM filmy WHERE id = ? LIMIT 1");

        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $wynik = $stmt->get_result();

            if ($wynik && $wynik->num_rows === 1) {
                $film = $wynik->fetch_assoc();
            }

            $stmt->close();
        }

        // Lista typów płyt do pola wyboru.
        $wynik = $polaczenie->query("SELECT id, nazwa FROM typy_plyt ORDER BY nazwa ASC");

        if ($wynik) {
            while ($row = $wynik->fetch_assoc()) {
                $typyPlyt[] = $row;
            }
            $wynik->free();
        }

        $polaczenie->close();
    } catch (Exception $e) {
        $blad = 'Błąd serwera. Spróbuj ponownie później.';
    }

    if (!$film) {
        header('Location: filmy.php');
        exit();
    }
?>

<?php
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

        <h2>Edycja filmu</h2>

        <?php if ($blad !== ''): ?>
            <div class="error"><?= htmlspecialchars($blad) ?></div>
        <?php endif; ?>

        <form method="POST" action="edytuj_film.php?id=<?= (int) $id ?>">
            <label for="tytul">Tytuł:</label>
            <input type="text" name="tytul" id="tytul" value="<?= htmlspecialchars($film['tytul']) ?>"><br>

            <label for="tytul_oryginalny">Tytuł oryginalny:</label>
            <input type="text" name="tytul_oryginalny" id="tytul_oryginalny" value="<?= htmlspecialchars($film['tytul_oryginalny'] ?? '') ?>"><br>

            <label for="gatunek">Gatunek:</label>
            <input type="text" name="gatunek" id="gatunek" value="<?= htmlspecialchars($film['gatunek'] ?? '') ?>"><br>

            <label for="rok">Rok produkcji:</label>
            <input type="text" name="rok" id="rok" value="<?= htmlspecialchars($film['rok'] ?? '') ?>"><br>

            <label for="ocena">Ocena:</label>
            <input type="text" name="ocena" id="ocena" value="<?= htmlspecialchars($film['ocena'] ?? '') ?>"><br>

            <label for="rezyser">Reżyser:</label>
            <input type="text" name="rezyser" id="rezyser" value="<?= htmlspecialchars($film['rezyser'] ?? '') ?>"><br>

            <label for="czas_trwania">Czas trwania (minuty):</label>
            <input type="text" name="czas_trwania" id="czas_trwania" value="<?= htmlspecialchars($film['czas_trwania'] ?? '') ?>"><br>

            <label for="jezyk">Język:</label>
            <input type="text" name="jezyk" id="jezyk" value="<?= htmlspecialchars($film['jezyk'] ?? '') ?>"><br>

            <label for="ilosc_dostepnych">Ilość dostępnych:</label>
            <input type="text" name="ilosc_dostepnych" id="ilosc_dostepnych" value="<?= htmlspecialchars($film['ilosc_dostepnych'] ?? '') ?>"><br>

            <label for="budzet">Budżet:</label>
            <input type="text" name="budzet" id="budzet" value="<?= htmlspecialchars($film['budzet'] ?? '') ?>"><br>

            <label for="produkcja">Produkcja:</label>
            <input type="text" name="produkcja" id="produkcja" value="<?= htmlspecialchars($film['produkcja'] ?? '') ?>"><br>

            <label for="nagrody">Nagrody:</label>
            <input type="text" name="nagrody" id="nagrody" value="<?= htmlspecialchars($film['nagrody'] ?? '') ?>"><br>

            <label for="cena">Cena za wypożyczenie:</label>
            <input type="text" name="cena" id="cena" value="<?= htmlspecialchars($film['cena'] ?? '') ?>"><br>

            <label for="opis">Opis:</label>
            <textarea name="opis" id="opis" rows="6" cols="50"><?= htmlspecialchars($film['opis'] ?? '') ?></textarea><br>

            <label for="okladka">Okładka (adres URL):</label>
            <input type="text" name="okladka" id="okladka" value="<?= htmlspecialchars($film['okladka'] ?? '') ?>"><br>

            <?php if (!empty($film['okladka'])): ?>
                <img src="<?= htmlspecialchars($film['okladka']) ?>" alt="Okładka filmu" style="max-width: 150px; border: 2px solid white; margin: 10px 0;"><br>
            <?php endif; ?>

            <label for="typ_plyty">Typ płyty:</label>
            <select name="typ_plyty" id="typ_plyty">
                <option value="">Wybierz typ</option>
                <?php foreach ($typyPlyt as $typ): ?>
                    <option value="<?= (int) $typ['id'] ?>" <?= ((int) ($film['typ_plyty_id'] ?? 0) === (int) $typ['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($typ['nazwa']) ?>
                    </option>
                <?php endforeach; ?>
            </select><br><br>

            <button type="submit">Zapisz zmiany</button>
            <a class="przycisk" href="filmy.php">Anuluj</a>
        </form>
</main>
<footer class="site-footer">
    <div class="footer-inner">
        <p>2026 - Przemysław Gałęziowski i Filip Klein</p>
    </div>
</footer>
</body>
</html>
