<?php
    require_once "czy_zalogowany.php";
    if (!isset($_SESSION['czy_admin']) || (int) $_SESSION['czy_admin'] !== 1) {
        header('Location: index.php');
        exit();
    }

    require_once "polaczenie.php";

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $utwor = null;
    $blad = '';
    $typyPlyt = [];

    if ($id <= 0) {
        header('Location: utwory.php');
        exit();
    }

    try {
        $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
        if ($polaczenie->connect_errno) {
            throw new Exception("Błąd połączenia z bazą danych.");
        }

        $polaczenie -> set_charset("utf8mb4");

        $stmt = $polaczenie -> prepare(
            "SELECT u.*, a.wykonawca, a.okladka, a.typ_plyty_id, a.rok AS album_rok, a.gatunek AS album_gatunek
             FROM utwory u
             LEFT JOIN albumy a ON u.album_id = a.id
             WHERE u.id = ?
             LIMIT 1"
        );

        if (!$stmt) {
            throw new Exception("Błąd zapytania pobierającego utwór.");
        }

        $stmt -> bind_param("i", $id);
        $stmt -> execute();
        $wynik = $stmt -> get_result();
        if ($wynik && $wynik -> num_rows === 1) {
            $utwor = $wynik -> fetch_assoc();
        }
        $stmt -> close();

        if (!$utwor) {
            $polaczenie -> close();
            header('Location: utwory.php');
            exit();
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $tytul = trim($_POST['tytul'] ?? '');
            $numer = trim($_POST['numer'] ?? '');
            $czas = trim($_POST['czas'] ?? '');
            $wykonawca = trim($_POST['wykonawca'] ?? '');
            $rok = (($_POST['rok'] ?? '') !== '') ? (int) $_POST['rok'] : null;
            $gatunek = trim($_POST['gatunek'] ?? '');
            $typPlyty = (($_POST['typ_plyty'] ?? '') !== '') ? (int) $_POST['typ_plyty'] : null;
            $ilosc = (($_POST['ilosc_dostepnych'] ?? '') !== '') ? (int) $_POST['ilosc_dostepnych'] : null;
            $cena = (($_POST['cena'] ?? '') !== '') ? (float) $_POST['cena'] : null;
            $okladka = trim($_POST['okladka'] ?? '');

            if ($tytul === '') {
                $blad = 'Tytuł utworu jest wymagany.';
            } else {
                $polaczenie -> begin_transaction();

                $stmtUpdUtwor = $polaczenie -> prepare(
                    "UPDATE utwory
                     SET tytul = ?, numer = ?, czas = ?, ilosc_dostepnych = ?, cena = ?
                     WHERE id = ?"
                );
                if (!$stmtUpdUtwor) {
                    throw new Exception("Błąd przygotowania aktualizacji utworu.");
                }
                $stmtUpdUtwor -> bind_param("sssidd", $tytul, $numer, $czas, $ilosc, $cena, $id);
                if (!$stmtUpdUtwor -> execute()) {
                    $err = $stmtUpdUtwor -> error;
                    $stmtUpdUtwor -> close();
                    throw new Exception("Nie udało się zaktualizować utworu: " . $err);
                }
                $stmtUpdUtwor -> close();

                $albumId = (int) ($utwor['album_id'] ?? 0);
                if ($albumId > 0) {
                    $stmtUpdAlbum = $polaczenie -> prepare(
                        "UPDATE albumy
                         SET wykonawca = ?, rok = ?, gatunek = ?, okladka = ?, typ_plyty_id = ?
                         WHERE id = ?"
                    );
                    if (!$stmtUpdAlbum) {
                        throw new Exception("Błąd przygotowania aktualizacji albumu.");
                    }
                    $stmtUpdAlbum -> bind_param("sissii", $wykonawca, $rok, $gatunek, $okladka, $typPlyty, $albumId);
                    if (!$stmtUpdAlbum -> execute()) {
                        $err = $stmtUpdAlbum -> error;
                        $stmtUpdAlbum -> close();
                        throw new Exception("Nie udało się zaktualizować albumu: " . $err);
                    }
                    $stmtUpdAlbum -> close();
                }

                $polaczenie -> commit();
                $polaczenie -> close();

                $_SESSION['edytowano_utwor'] = true;
                header("Location: utwor.php?id=" . $id);
                exit();
            }
        }

        $wynik = $polaczenie -> query("SELECT id, nazwa FROM typy_plyt ORDER BY nazwa ASC");
        if ($wynik) {
            while ($row = $wynik -> fetch_assoc()) {
                $typyPlyt[] = $row;
            }
            $wynik -> free();
        }

        $polaczenie -> close();
    } catch (Exception $e) {
        if (isset($polaczenie) && $polaczenie instanceof mysqli && !$polaczenie->connect_errno) {
            $polaczenie -> rollback();
            $polaczenie -> close();
        }
        $blad = 'Błąd serwera. Spróbuj ponownie później.';
    }

    $active = 'utwory';
    $pageTitle = 'Edycja utworu';
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

    <h2>Edycja utworu</h2>

    <?php if ($blad !== ''): ?>
        <div class="error"><?= htmlspecialchars($blad) ?></div>
    <?php endif; ?>

    <form method="POST" action="edytuj_utwor.php?id=<?= (int) $id ?>">
        <label for="tytul">Tytuł utworu:</label>
        <input type="text" name="tytul" id="tytul" value="<?= htmlspecialchars($utwor['tytul']) ?>"><br>

        <label for="numer">Numer ścieżki:</label>
        <input type="text" name="numer" id="numer" value="<?= htmlspecialchars($utwor['numer'] ?? '') ?>"><br>

        <label for="czas">Czas trwania:</label>
        <input type="text" name="czas" id="czas" value="<?= htmlspecialchars($utwor['czas'] ?? '') ?>"><br>

        <label for="wykonawca">Wykonawca:</label>
        <input type="text" name="wykonawca" id="wykonawca" value="<?= htmlspecialchars($utwor['wykonawca'] ?? '') ?>"><br>

        <label for="rok">Rok:</label>
        <input type="text" name="rok" id="rok" value="<?= htmlspecialchars($utwor['album_rok'] ?? '') ?>"><br>

        <label for="gatunek">Gatunek:</label>
        <input type="text" name="gatunek" id="gatunek" value="<?= htmlspecialchars($utwor['album_gatunek'] ?? '') ?>"><br>

        <label for="typ_plyty">Typ płyty:</label>
        <select name="typ_plyty" id="typ_plyty">
            <option value="">Wybierz typ</option>
            <?php foreach ($typyPlyt as $typ): ?>
                <option value="<?= (int) $typ['id'] ?>" <?= ((int) ($utwor['typ_plyty_id'] ?? 0) === (int) $typ['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($typ['nazwa']) ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <label for="ilosc_dostepnych">Ilość dostępnych:</label>
        <input type="text" name="ilosc_dostepnych" id="ilosc_dostepnych" value="<?= htmlspecialchars((string) ($utwor['ilosc_dostepnych'] ?? '')) ?>"><br>

        <label for="cena">Cena utworu (niezależna od albumu):</label>
        <input type="text" name="cena" id="cena" value="<?= htmlspecialchars((string) ($utwor['cena'] ?? '')) ?>"><br>

        <label for="okladka">Okładka (adres URL):</label>
        <input type="text" name="okladka" id="okladka" value="<?= htmlspecialchars($utwor['okladka'] ?? '') ?>"><br>

        <?php if (!empty($utwor['okladka'])): ?>
            <img src="<?= htmlspecialchars($utwor['okladka']) ?>" alt="Okładka utworu" style="max-width: 150px; border: 2px solid white; margin: 10px 0;"><br>
        <?php endif; ?>

        <button type="submit">Zapisz zmiany</button>
        <a class="przycisk" href="utwor.php?id=<?= (int) $id ?>">Anuluj</a>
    </form>
</main>
<footer class="site-footer">
    <div class="footer-inner">
        <p>2026 - Przemysław Gałęziowski i Filip Klein</p>
    </div>
</footer>
</body>
</html>