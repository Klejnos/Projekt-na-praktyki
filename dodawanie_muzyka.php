<?php
    session_start();
    if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] == false) {
        header('Location: logowanie.php');
        exit();
    }

    require_once "polaczenie.php";

    $blad = '';
    $typyPlyt = [];

    try {
        $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
        if ($polaczenie->connect_errno) {
            throw new Exception();
        }

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $tytul = trim($_POST['tytul'] ?? '');
            $wykonawca = trim($_POST['wykonawca'] ?? '');
            $rok = (($_POST['rok'] ?? '') !== '') ? (int) $_POST['rok'] : null;
            $gatunek = trim($_POST['gatunek'] ?? '');
            $typPlyty = (($_POST['typ_plyty'] ?? '') !== '') ? (int) $_POST['typ_plyty'] : null;
            $ilosc = (($_POST['ilosc_dostepnych'] ?? '') !== '') ? (int) $_POST['ilosc_dostepnych'] : null;
            $cena = (($_POST['cena'] ?? '') !== '') ? (float) $_POST['cena'] : null;
            $discogsId = (($_POST['discogs_id'] ?? '') !== '') ? (int) $_POST['discogs_id'] : null;
            $okladka = trim($_POST['okladka'] ?? '');
            $discogsUri = trim($_POST['discogs_uri'] ?? '');

            $tytul = mb_substr($tytul, 0, 150, 'UTF-8');
            $wykonawca = mb_substr($wykonawca, 0, 150, 'UTF-8');
            $gatunek = mb_substr($gatunek, 0, 100, 'UTF-8');
            $okladka = mb_substr($okladka, 0, 500, 'UTF-8');
            $discogsUri = mb_substr($discogsUri, 0, 255, 'UTF-8');

            if ($tytul === '') {
                $blad = 'Tytuł albumu jest wymagany.';
            } else {
                $albumId = null;

                if ($discogsId !== null) {
                    $stmt = $polaczenie->prepare("SELECT id FROM albumy WHERE discogs_id = ? LIMIT 1");

                    if ($stmt) {
                        $stmt->bind_param("i", $discogsId);
                        $stmt->execute();
                        $wynik = $stmt->get_result();

                        if ($wynik && $wynik->num_rows === 1) {
                            $albumId = (int) $wynik->fetch_assoc()['id'];
                        }

                        $stmt->close();
                    }
                }

                if ($albumId === null) {
                    $stmt = $polaczenie->prepare(
                        "INSERT INTO albumy
                            (tytul, wykonawca, rok, gatunek, okladka, discogs_id, discogs_uri, typ_plyty_id)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                    );

                    if ($stmt) {
                        $stmt->bind_param(
                            "ssissisi",
                            $tytul,
                            $wykonawca,
                            $rok,
                            $gatunek,
                            $okladka,
                            $discogsId,
                            $discogsUri,
                            $typPlyty
                        );

                        if ($stmt->execute()) {
                            $albumId = $stmt->insert_id;
                        } else {
                            $blad = 'Nie udało się dodać albumu: ' . $stmt->error;
                        }

                        $stmt->close();
                    } else {
                        $blad = 'Nie udało się przygotować zapytania: ' . $polaczenie->error;
                    }
                }

                if ($blad === '' && $albumId !== null) {
                    $stmt = $polaczenie->prepare(
                        "INSERT INTO muzyka (tytul, gatunek, rok, ilosc_dostepnych, cena, album_id)
                         VALUES (?, ?, ?, ?, ?, ?)"
                    );

                    if ($stmt) {
                        $stmt->bind_param("ssiidi", $tytul, $gatunek, $rok, $ilosc, $cena, $albumId);

                        if ($stmt->execute()) {
                            $stmt->close();
                            $polaczenie->close();
                            $_SESSION['dodano_muzyke'] = true;
                            header("Location: muzyka.php");
                            exit();
                        }

                        $blad = 'Nie udało się dodać muzyki: ' . $stmt->error;
                        $stmt->close();
                    } else {
                        $blad = 'Nie udało się przygotować zapytania: ' . $polaczenie->error;
                    }
                }
            }
        }

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
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dodawanie muzyki | Wypożyczalnia</title>
</head>
<body>
    <header>
        <h1>Wypożyczalnia filmów DVD oraz muzyki CD<a class="konto" href="konto.php">Konto</a></h1>
    </header>
    <nav>
            <a href="index.php">Strona główna</a>
            <a href="filmy.php">Filmy</a>
            <a href="muzyka.php">Muzyka</a>
            <a href="kontakt.php">Kontakt</a>
    </nav>
    <main>
        <h1>Dodawanie muzyki</h1>

        <?php if ($blad !== ''): ?>
            <div class="error"><?= htmlspecialchars($blad) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="search-box">
                <label for="tytul">Album / wykonawca:</label>
                <input type="text" name="tytul" id="tytul" autocomplete="off" placeholder="Zacznij wpisywać nazwę albumu lub wykonawcę...">
                <div id="podpowiedzi"></div>
            </div>

            <input type="hidden" name="discogs_id" id="discogs_id" value="">
            <input type="hidden" name="okladka" id="okladka_url" value="">
            <input type="hidden" name="discogs_uri" id="discogs_uri" value="">
            <div id="podgladOkladki">
                <img id="okladka" src="" alt="Okładka albumu">
            </div>

            <label for="wykonawca">Wykonawca:</label>
            <input type="text" name="wykonawca" id="wykonawca"><br>

            <label for="rok">Rok:</label>
            <input type="text" name="rok" id="rok"><br>

            <label for="gatunek">Gatunek:</label>
            <input type="text" name="gatunek" id="gatunek"><br>

            <label for="typ_plyty">Typ płyty:</label>
            <select name="typ_plyty" id="typ_plyty">
                <option value="">Wybierz typ</option>
                <?php foreach ($typyPlyt as $typ): ?>
                    <option value="<?= (int) $typ['id'] ?>"><?= htmlspecialchars($typ['nazwa']) ?></option>
                <?php endforeach; ?>
            </select><br><br>

            <label for="ilosc_dostepnych">Ilość dostępnych:</label>
            <input type="text" name="ilosc_dostepnych" id="ilosc_dostepnych"><br>

            <label for="cena">Cena za wypożyczenie:</label>
            <input type="text" name="cena" id="cena"><br>

            <button type="submit">Dodaj muzykę</button>
            <button type="reset">Wyczyść</button>
        </form>
    </main>

    <footer>
        <p>Autorzy: Przemysław Gałęziowski i Filip Klein</p>
    </footer>

    <script src="assets/js/script_muzyka.js"></script>
</body>
</html>
