<?php
    require_once "czy_zalogowany.php";
    if (!isset($_SESSION['czy_admin']) || $_SESSION['czy_admin'] != 1) {
        header('Location: index.php');
        exit();
    }

    $blad = '';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        require_once "polaczenie.php";
        try {
            $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
            if ($polaczenie -> connect_errno) {
                throw new Exception(mysqli_connect_errno());
            }
            else {
                $tytul = trim($_POST['tytul'] ?? '');
                $tytul_oryginalny = trim($_POST['tytul_oryginalny'] ?? '');
                $gatunek = trim($_POST['gatunek'] ?? '');
                $rok = ($_POST['rok'] !== '') ? (int)$_POST['rok'] : null;
                $ocena = ($_POST['ocena'] !== '') ? (float)$_POST['ocena'] : null;
                $rezyser = trim($_POST['rezyser'] ?? '');
                $czas_trwania = ($_POST['czas_trwania'] !== '') ? (int)$_POST['czas_trwania'] : null;
                $jezyk = trim($_POST['jezyk'] ?? '');
                $ilosc_dostepnych = ($_POST['ilosc_dostepnych'] !== '') ? (int)$_POST['ilosc_dostepnych'] : null;
                $budzet = ($_POST['budzet'] !== '') ? (float)$_POST['budzet'] : null;
                $produkcja = trim($_POST['produkcja'] ?? '');
                $nagrody = trim($_POST['nagrody'] ?? '');
                $cena = ($_POST['cena'] !== '') ? (float)$_POST['cena'] : null;
                $opis = trim($_POST['opis'] ?? '');
                $okladka = trim($_POST['okladka'] ?? '');
                $tmdb_id = ($_POST['tmdb_id'] !== '') ? (int)$_POST['tmdb_id'] : null;
                $imdb_id = trim($_POST['imdb_id'] ?? '');
                $typ_plyty = ($_POST['typ_plyty'] !== '') ? (int)$_POST['typ_plyty'] : null;

                if ($tytul === '') {
                    $blad = "Tytuł filmu jest wymagany";
                }

                else {
                    $q_i = $polaczenie -> prepare("INSERT INTO filmy (tytul, tytul_oryginalny, gatunek, rok, ocena, rezyser, czas_trwania, jezyk, ilosc_dostepnych, budzet, produkcja, nagrody, cena, opis, okladka, tmdb_id, imdb_id, typ_plyty_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                    if ($q_i) {
                        $q_i -> bind_param("sssidiisidssdssisi", $tytul, $tytul_oryginalny, $gatunek, $rok, $ocena, $rezyser, $czas_trwania, $jezyk, $ilosc_dostepnych, $budzet, $produkcja, $nagrody, $cena, $opis, $okladka, $tmdb_id, $imdb_id, $typ_plyty);

                        if ($q_i -> execute()) {
                            $q_i -> close();
                            $polaczenie -> close();
                            $_SESSION['dodano_film'] = true;
                            header("Location: filmy.php");
                            exit();
                        }

                        else {
                            throw new Exception(mysqli_connect_errno());
                        }
                    }

                    else {
                        throw new Exception(mysqli_connect_errno());
                    }
                }

                $polaczenie -> close();
            }
        }

        catch(Exception $e) {
            echo "Błąd serwera";
        }
    }
?>

<?php
    $active = 'filmy';
    $pageTitle = 'Dodawanie filmu';
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
        <h1>Dodawanie filmu</h1>
        <?php if ($blad !== ''): ?>
            <div class="error"><?= htmlspecialchars($blad) ?></div>
        <?php endif; ?>
            <form method="POST">
                <div class="search-box">
                    <label for="tytul">Tytuł:</label>
                    <input type="text" name="tytul" id="tytul" autocomplete="off" placeholder="Zacznij wpisywać tytuł filmu...">
                    <div id="podpowiedzi"></div>
                </div>

                <input type="hidden" name="tytul_oryginalny" id="tytul_oryginalny" value="">
                <input type="hidden" name="tmdb_id" id="tmdb_id" value="">
                <input type="hidden" name="imdb_id" id="imdb_id" value="">
                <div id="podgladOkladki">
                <img id="okladka" src="" alt="Okładka filmu">
                <input type="hidden" name="okladka" id="okladka_url" value=""></div>
            
                <label>Gatunek:</label>
                <input type="text" name="gatunek" id="gatunek"><br>
            
                <label for="rok">Rok produkcji:</label>
                <input type="text" id="rok" name="rok"><br>
            
                <label for="ocena">Ocena:</label>
                <input type="text" id="ocena" name="ocena"><br>
            
                <label for="rezyser">Reżyser:</label>
                <input type="text" id="rezyser" name="rezyser"><br>
            
                <label for="czas_trwania">Czas trwania (minuty):</label>
                <input type="text" id="czas_trwania" name="czas_trwania"><br>
            
                <label for="jezyk">Język:</label>
                <input type="text" id="jezyk" name="jezyk"><br>
            
                <label for="ilosc_dostepnych">Ilość dostępnych:</label>
                <input type="text" id="ilosc_dostepnych" name="ilosc_dostepnych"><br>
            
                <label for="budzet">Budżet:</label>
                <input type="text" id="budzet" name="budzet"><br>
            
                <label for="produkcja">Produkcja:</label>
                <input type="text" id="produkcja" name="produkcja"><br>
            
                <label for="nagrody">Nagrody:</label>
                <input type="text" id="nagrody" name="nagrody"><br>
            
                <label for="cena">Cena za wypożyczenie:</label>
                <input type="text" id="cena" name="cena"><br>
            
                <label for="opis">Opis:</label>
                <textarea id="opis" name="opis" rows="5" cols="40" placeholder="Opis filmu"></textarea><br>

                <label for="typ_plyty">Wybierz typ płyty:</label>
                <select name="typ_plyty" id="typ_plyty">
                    <option value="">Wybierz typ</option>
                    <?php
                        require_once "polaczenie.php";
                        try {
                            $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
                            if ($polaczenie -> connect_errno) {
                                throw new Exception(mysqli_connect_errno());
                            }
                            else {
                                $qu = $polaczenie -> query("SELECT id, nazwa FROM typy_plyt");
                                if ($qu) {
                                    while ($wiersz = $qu -> fetch_assoc()) {
                                        echo '<option value="'.$wiersz['id'].'">'.$wiersz['nazwa'].'</option>';
                                    }
                                }

                                $qu -> free();
                                $polaczenie -> close();
                            }
                        }
                        catch (Exception $e) { }
                    ?>
                </select><br><br>
            
                <button type="submit">Wyślij formularz</button>
                <button type="reset">Wyczyść</button>
            </form>
    <script src="assets/js/script_api.js"></script>
</main>
<footer class="site-footer">
    <div class="footer-inner">
        <span>&copy; <?= date('Y') ?> Wypożyczalnia filmów — filmy DVD i muzyka CD</span>
        <span>Gdańsk, ul. Rajska 4 · 10:00–18:00</span>
    </div>
</footer>
</body>
</html>