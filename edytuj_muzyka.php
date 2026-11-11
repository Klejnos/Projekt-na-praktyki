<?php
    session_start();
    if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] == false) {
        header('Location: logowanie.php');
        exit();
    }

    require_once "polaczenie.php";

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $album = null;
    $albumId = null;
    $blad = '';
    $typyPlyt = [];

    try {
        $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
        if ($polaczenie->connect_errno) {
            throw new Exception();
        }

        $polaczenie->set_charset("utf8mb4");

        $stmt = $polaczenie->prepare("SELECT album_id FROM muzyka WHERE id = ? LIMIT 1");

        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $wynik = $stmt->get_result();

            if ($wynik && $wynik->num_rows === 1) {
                $albumId = $wynik->fetch_assoc()['album_id'];
            }

            $stmt->close();
        }

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $tytul = trim($_POST['tytul'] ?? '');
            $wykonawca = trim($_POST['wykonawca'] ?? '');
            $rok = (($_POST['rok'] ?? '') !== '') ? (int) $_POST['rok'] : null;
            $gatunek = trim($_POST['gatunek'] ?? '');
            $typPlyty = (($_POST['typ_plyty'] ?? '') !== '') ? (int) $_POST['typ_plyty'] : null;
            $ilosc = (($_POST['ilosc_dostepnych'] ?? '') !== '') ? (int) $_POST['ilosc_dostepnych'] : null;
            $cena = (($_POST['cena'] ?? '') !== '') ? (float) $_POST['cena'] : null;
            $okladka = trim($_POST['okladka'] ?? '');

            if ($tytul === '') {
                $blad = 'Tytuł albumu jest wymagany.';
            } else {
                if ($albumId !== null) {
                    $stmt = $polaczenie->prepare(
                        "UPDATE albumy SET tytul = ?, wykonawca = ?, rok = ?, gatunek = ?, okladka = ?, typ_plyty_id = ? WHERE id = ?"
                    );

                    if ($stmt) {
                        $stmt->bind_param("ssissii", $tytul, $wykonawca, $rok, $gatunek, $okladka, $typPlyty, $albumId);
                        $stmt->execute();
                        $stmt->close();
                    }
                } else {
                    $stmt = $polaczenie->prepare(
                        "INSERT INTO albumy (tytul, wykonawca, rok, gatunek, okladka, typ_plyty_id) VALUES (?, ?, ?, ?, ?, ?)"
                    );

                    if ($stmt) {
                        $stmt->bind_param("ssissi", $tytul, $wykonawca, $rok, $gatunek, $okladka, $typPlyty);

                        if ($stmt->execute()) {
                            $albumId = $stmt->insert_id;

                            $stmtUpdate = $polaczenie->prepare("UPDATE muzyka SET album_id = ? WHERE id = ?");

                            if ($stmtUpdate) {
                                $stmtUpdate->bind_param("ii", $albumId, $id);
                                $stmtUpdate->execute();
                                $stmtUpdate->close();
                            }
                        }

                        $stmt->close();
                    }
                }

                $stmt = $polaczenie->prepare(
                    "UPDATE muzyka SET tytul = ?, gatunek = ?, rok = ?, ilosc_dostepnych = ?, cena = ? WHERE id = ?"
                );

                if ($stmt) {
                    $stmt->bind_param("ssiidi", $tytul, $gatunek, $rok, $ilosc, $cena, $id);

                    if ($stmt->execute()) {
                        $stmt->close();
                        $polaczenie->close();
                        $_SESSION['edytowano_muzyke'] = true;
                        header("Location: muzyka.php");
                        exit();
                    }

                    $blad = 'Nie udało się zapisać zmian: ' . $stmt->error;
                    $stmt->close();
                } else {
                    $blad = 'Nie udało się przygotować zapytania: ' . $polaczenie->error;
                }
            }
        }

        $stmt = $polaczenie->prepare(
            "SELECT m.*, a.wykonawca, a.okladka, a.typ_plyty_id
             FROM muzyka m
             LEFT JOIN albumy a ON m.album_id = a.id
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

    if (!$album) {
        header('Location: muzyka.php');
        exit();
    }
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edycja muzyki | Wypożyczalnia</title>
</head>
<body>
    <header>
        <h1>Wypożyczalnia filmów DVD oraz muzyki CD<a class="konto" href="<?= (!empty($_SESSION['czy_admin'])) ? 'konto_admin.php' : 'konto_uzytkownik.php' ?>">Konto</a></h1>
    </header>
    <nav>
            <a href="index.php">Strona główna</a>
            <a href="filmy.php">Filmy</a>
            <a href="muzyka.php">Muzyka</a>
            <a href="kontakt.php">Kontakt</a>
    </nav>
    <main>
        <h2>Edycja albumu</h2>

        <?php if ($blad !== ''): ?>
            <div class="error"><?= htmlspecialchars($blad) ?></div>
        <?php endif; ?>

        <form method="POST" action="edytuj_muzyka.php?id=<?= (int) $id ?>">
            <label for="tytul">Tytuł albumu:</label>
            <input type="text" name="tytul" id="tytul" value="<?= htmlspecialchars($album['tytul']) ?>"><br>

            <label for="wykonawca">Wykonawca:</label>
            <input type="text" name="wykonawca" id="wykonawca" value="<?= htmlspecialchars($album['wykonawca'] ?? '') ?>"><br>

            <label for="rok">Rok:</label>
            <input type="text" name="rok" id="rok" value="<?= htmlspecialchars($album['rok'] ?? '') ?>"><br>

            <label for="gatunek">Gatunek:</label>
            <input type="text" name="gatunek" id="gatunek" value="<?= htmlspecialchars($album['gatunek'] ?? '') ?>"><br>

            <label for="typ_plyty">Typ płyty:</label>
            <select name="typ_plyty" id="typ_plyty">
                <option value="">Wybierz typ</option>
                <?php foreach ($typyPlyt as $typ): ?>
                    <option value="<?= (int) $typ['id'] ?>" <?= ((int) ($album['typ_plyty_id'] ?? 0) === (int) $typ['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($typ['nazwa']) ?>
                    </option>
                <?php endforeach; ?>
            </select><br><br>

            <label for="ilosc_dostepnych">Ilość dostępnych:</label>
            <input type="text" name="ilosc_dostepnych" id="ilosc_dostepnych" value="<?= htmlspecialchars($album['ilosc_dostepnych'] ?? '') ?>"><br>

            <label for="cena">Cena za wypożyczenie:</label>
            <input type="text" name="cena" id="cena" value="<?= htmlspecialchars($album['cena'] ?? '') ?>"><br>

            <label for="okladka">Okładka (adres URL):</label>
            <input type="text" name="okladka" id="okladka" value="<?= htmlspecialchars($album['okladka'] ?? '') ?>"><br>

            <?php if (!empty($album['okladka'])): ?>
                <img src="<?= htmlspecialchars($album['okladka']) ?>" alt="Okładka albumu" style="max-width: 150px; border: 2px solid white; margin: 10px 0;"><br>
            <?php endif; ?>

            <button type="submit">Zapisz zmiany</button>
            <a class="przycisk" href="muzyka.php">Anuluj</a>
        </form>
    </main>

    <footer>
        <p>Autorzy: Przemysław Gałęziowski i Filip Klein</p>
    </footer>
</body>
</html>
