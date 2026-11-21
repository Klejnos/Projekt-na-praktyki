<?php
// dodawanie_utwor.php — formularz dodawania utworu (tylko administrator).
// Podpowiedzi uzupełniają album i okładkę; istniejący album jest ponownie używany.


    require_once "czy_zalogowany.php";
    if (!isset($_SESSION['czy_admin']) || (int) $_SESSION['czy_admin'] !== 1) {
        header('Location: index.php');
        exit();
    }

    require_once "polaczenie.php";

    $blad = '';
    $typyPlyt = [];

    try {
        $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
        if ($polaczenie->connect_errno) {
            throw new Exception("Błąd połączenia z bazą danych.");
        }

        $polaczenie->set_charset("utf8mb4");

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            // Odczyt pól formularza i przygotowanie danych do zapisu.
            $tytul = trim($_POST['tytul'] ?? '');
            $numer = trim($_POST['numer'] ?? '');
            $czas = trim($_POST['czas'] ?? '');
            $albumTytul = trim($_POST['album_tytul'] ?? '');
            // album_id pochodzi z lokalnej podpowiedzi i pozwala użyć istniejącego albumu.
            $albumIdWyboru = (($_POST['album_id'] ?? '') !== '') ? (int) $_POST['album_id'] : null;
            $wykonawca = trim($_POST['wykonawca'] ?? '');
            $rok = (($_POST['rok'] ?? '') !== '') ? (int) $_POST['rok'] : null;
            $gatunek = trim($_POST['gatunek'] ?? '');
            $typPlyty = (($_POST['typ_plyty'] ?? '') !== '') ? (int) $_POST['typ_plyty'] : null;
            $ilosc = (($_POST['ilosc_dostepnych'] ?? '') !== '') ? (int) $_POST['ilosc_dostepnych'] : null;
            $cena = (($_POST['cena'] ?? '') !== '') ? (float) $_POST['cena'] : null;
            $discogsId = (($_POST['discogs_id'] ?? '') !== '') ? (int) $_POST['discogs_id'] : null;
            $okladka = trim($_POST['okladka'] ?? '');
            $discogsUri = trim($_POST['discogs_uri'] ?? '');

            $tytul = mb_substr($tytul, 0, 200, 'UTF-8');
            $numer = mb_substr($numer, 0, 20, 'UTF-8');
            $czas = mb_substr($czas, 0, 20, 'UTF-8');
            $albumTytul = mb_substr($albumTytul, 0, 150, 'UTF-8');
            $wykonawca = mb_substr($wykonawca, 0, 150, 'UTF-8');
            $gatunek = mb_substr($gatunek, 0, 100, 'UTF-8');
            $okladka = mb_substr($okladka, 0, 500, 'UTF-8');
            $discogsUri = mb_substr($discogsUri, 0, 255, 'UTF-8');

            if ($tytul === '') {
                $blad = 'Tytuł utworu jest wymagany.';
            } else {
                $polaczenie->begin_transaction();

                $albumId = null;

                // 1. Utwór wybrany z lokalnej podpowiedzi — użyj istniejącego albumu.
                if ($albumIdWyboru !== null && $albumIdWyboru > 0) {
                    $stmt = $polaczenie->prepare("SELECT id FROM albumy WHERE id = ? LIMIT 1");
                    if ($stmt) {
                        $stmt->bind_param("i", $albumIdWyboru);
                        $stmt->execute();
                        $wynik = $stmt->get_result();
                        if ($wynik && $wynik->num_rows === 1) {
                            $albumId = (int) $wynik->fetch_assoc()['id'];
                        }
                        $stmt->close();
                    }
                }

                // 2. W przeciwnym razie spróbuj dopasować album po discogs_id.
                if ($albumId === null && $discogsId !== null) {
                    $stmt = $polaczenie->prepare("SELECT id FROM albumy WHERE discogs_id = ? LIMIT 1");
                    if (!$stmt) {
                        throw new Exception("Błąd sprawdzania albumu po ID zewnętrznym.");
                    }
                    $stmt->bind_param("i", $discogsId);
                    $stmt->execute();
                    $wynik = $stmt->get_result();
                    if ($wynik && $wynik->num_rows === 1) {
                        $albumId = (int) $wynik->fetch_assoc()['id'];
                    }
                    $stmt->close();
                }

                $nazwaAlbumuDoZapisu = $albumTytul !== '' ? $albumTytul : ($wykonawca !== '' ? $wykonawca . ' — Single' : 'Single / Utwory');

                // 3. Jeśli album nadal nie istnieje, utwórz go z danych formularza.
                if ($albumId === null && ($albumTytul !== '' || $wykonawca !== '' || $okladka !== '' || $rok !== null || $gatunek !== '' || $typPlyty !== null)) {
                    $stmt = $polaczenie->prepare(
                        "INSERT INTO albumy
                            (tytul, wykonawca, rok, gatunek, okladka, discogs_id, discogs_uri, typ_plyty_id)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                    if (!$stmt) {
                        throw new Exception("Błąd przygotowania zapytania dla albumu.");
                    }
                    $stmt->bind_param(
                        "ssissisi",
                        $nazwaAlbumuDoZapisu,
                        $wykonawca,
                        $rok,
                        $gatunek,
                        $okladka,
                        $discogsId,
                        $discogsUri,
                        $typPlyty
                    );
                    if (!$stmt->execute()) {
                        $err = $stmt->error;
                        $stmt->close();
                        throw new Exception("Nie udało się utworzyć powiązanego albumu: " . $err);
                    }
                    $albumId = (int) $stmt->insert_id;
                    $stmt->close();
                }

                // 4. Ostateczność: wspólny album "Utwory".
                if ($albumId === null) {
                    $domyslnyTytul = 'Utwory';
                    $stmt = $polaczenie->prepare("SELECT id FROM albumy WHERE tytul = ? AND wykonawca IS NULL LIMIT 1");
                    if ($stmt) {
                        $stmt->bind_param("s", $domyslnyTytul);
                        $stmt->execute();
                        $wynik = $stmt->get_result();
                        if ($wynik && $wynik->num_rows === 1) {
                            $albumId = (int) $wynik->fetch_assoc()['id'];
                        }
                        $stmt->close();
                    }

                    if ($albumId === null) {
                        $stmt = $polaczenie->prepare("INSERT INTO albumy (tytul) VALUES (?)");
                        if (!$stmt) {
                            throw new Exception("Błąd tworzenia domyślnego albumu.");
                        }
                        $stmt->bind_param("s", $domyslnyTytul);
                        if (!$stmt->execute()) {
                            $err = $stmt->error;
                            $stmt->close();
                            throw new Exception("Nie udało się zapisać domyślnego albumu: " . $err);
                        }
                        $albumId = (int) $stmt->insert_id;
                        $stmt->close();
                    }
                }

                // Gdy utwór podpinamy do istniejącego albumu, można zaktualizować jego okładkę.
                if ($albumId !== null && $okladka !== '') {
                    $stmtOkladka = $polaczenie->prepare("UPDATE albumy SET okladka = ? WHERE id = ?");

                    if ($stmtOkladka) {
                        $stmtOkladka->bind_param("si", $okladka, $albumId);
                        $stmtOkladka->execute();
                        $stmtOkladka->close();
                    }
                }

                // 5. Zapis utworu. Cena jest własną ceną utworu, niezależną od albumu.
                $stmtUtwor = $polaczenie->prepare(
                    "INSERT INTO utwory (album_id, numer, tytul, czas, cena, ilosc_dostepnych)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                if (!$stmtUtwor) {
                    throw new Exception("Błąd przygotowania zapytania dodawania utworu.");
                }

                $stmtUtwor->bind_param("isssdi", $albumId, $numer, $tytul, $czas, $cena, $ilosc);
                if (!$stmtUtwor->execute()) {
                    $err = $stmtUtwor->error;
                    $stmtUtwor->close();
                    throw new Exception("Nie udało się dodać utworu: " . $err);
                }

                $nowyUtworId = (int) $stmtUtwor->insert_id;
                $stmtUtwor->close();

                $polaczenie->commit();
                $polaczenie->close();

                header("Location: utwor.php?id=" . $nowyUtworId);
                exit();
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
        if (isset($polaczenie) && $polaczenie instanceof mysqli && !$polaczenie->connect_errno) {
            $polaczenie->rollback();
            $polaczenie->close();
        }
        $blad = 'Błąd serwera. Spróbuj ponownie później.';
    }

    $pageTitle = 'Dodawanie utworu';
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

    <h1>Dodawanie utworu</h1>

    <?php if ($blad !== ''): ?>
        <div class="error"><?= htmlspecialchars($blad) ?></div>
    <?php endif; ?>

    <form method="POST">
        <!-- Podpowiedzi działają po wpisaniu tytułu; wybór uzupełnia też album i okładkę. -->
        <div class="search-box">
            <label for="tytul">Tytuł utworu *:</label>
            <input type="text" name="tytul" id="tytul" required autocomplete="off" placeholder="Zacznij wpisywać nazwę utworu...">
            <div id="podpowiedzi"></div>
        </div>

        <label for="numer">Numer ścieżki:</label>
        <input type="text" name="numer" id="numer" placeholder="Np. 1 lub A1"><br>

        <label for="czas">Czas trwania:</label>
        <input type="text" name="czas" id="czas" placeholder="Np. 04:54"><br>

        <label for="album_tytul">Album / wydawnictwo:</label>
        <input type="text" name="album_tytul" id="album_tytul" autocomplete="off" placeholder="Uzupełni się po wybraniu utworu lub wpisz ręcznie...">

        <!-- album_id = istniejący album, discogs_id = powiązanie z Discogs. -->
        <input type="hidden" name="album_id" id="album_id" value="">
        <input type="hidden" name="discogs_id" id="discogs_id" value="">
        <input type="hidden" name="discogs_uri" id="discogs_uri" value="">

        <!-- Okładkę można też wpisać ręcznie, gdy Discogs nie odpowiada. -->
        <label for="okladka_url">Okładka albumu (adres URL, opcjonalnie):</label>
        <input type="text" name="okladka" id="okladka_url" placeholder="https://..."><br>

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
        <input type="text" name="ilosc_dostepnych" id="ilosc_dostepnych" value="1"><br>

        <label for="cena">Cena utworu (niezależna od albumu):</label>
        <input type="text" name="cena" id="cena" placeholder="Np. 4.99"><br>

        <button type="submit">Dodaj utwór</button>
        <button type="reset">Wyczyść</button>
    </form>
    <script src="assets/js/script_utwor.js"></script>
</main>
<footer class="site-footer">
    <p>2026 - Przemysław Gałęziowski oraz Filip Klein</p>
</footer>
</body>
</html>