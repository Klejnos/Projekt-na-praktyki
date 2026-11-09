<?php
    session_start();
    if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] == false) {
        header('Location: logowanie.php');
        exit();
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        require_once "polaczenie.php";
        try {
            $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
            if ($polaczenie -> connect_errno) {
                throw new Exception(mysqli_connect_errno());
            }
            else {
                $tytul = $polaczenie -> real_escape_string($_POST['tytul'] ?? '');
                $tytul_oryginalny = $polaczenie -> real_escape_string($_POST['tytul_oryginalny'] ?? '');
                $gatunek = $polaczenie -> real_escape_string($_POST['gatunek'] ?? '');
                $rok = (($_POST['rok'] ?? '') !== '') ? (int)$_POST['rok'] : "NULL";
                $ocena = (($_POST['ocena'] ?? '') !== '') ? (float)$_POST['ocena'] : "NULL";
                $rezyser = $polaczenie -> real_escape_string($_POST['rezyser'] ?? '');
                $czas_trwania = (($_POST['czas_trwania'] ?? '') !== '') ? (int)$_POST['czas_trwania'] : "NULL";
                $jezyk = $polaczenie -> real_escape_string($_POST['jezyk'] ?? '');
                $ilosc_dostepnych = (($_POST['ilosc_dostepnych'] ?? '') !== '') ? (int)$_POST['ilosc_dostepnych'] : "NULL";
                $budzet = (($_POST['budzet'] ?? '') !== '') ? (float)$_POST['budzet'] : "NULL";
                $produkcja = $polaczenie -> real_escape_string($_POST['produkcja'] ?? '');
                $nagrody = $polaczenie -> real_escape_string($_POST['nagrody'] ?? '');
                $cena = (($_POST['cena'] ?? '') !== '') ? (float)$_POST['cena'] : "NULL";
                $opis = $polaczenie -> real_escape_string($_POST['opis'] ?? '');
                $okladka = $polaczenie -> real_escape_string($_POST['okladka'] ?? '');
                $tmdb_id = (($_POST['tmdb_id'] ?? '') !== '') ? (int)$_POST['tmdb_id'] : "NULL";
                $imdb_id = $polaczenie -> real_escape_string($_POST['imdb_id'] ?? '');
                $typ_plyty = (($_POST['typ_plyty'] ?? '') !== '') ? (int)$_POST['typ_plyty'] : "NULL";

                $q = "insert into filmy (tytul, tytul_oryginalny, gatunek, rok, ocena, rezyser, czas_trwania, jezyk, ilosc_dostepnych, budzet, produkcja, nagrody, cena, opis, okladka, tmdb_id, imdb_id, typ_plyty_id) values ('$tytul', '$tytul_oryginalny', '$gatunek', $rok, $ocena, '$rezyser', $czas_trwania, '$jezyk', $ilosc_dostepnych, $budzet, '$produkcja', '$nagrody', $cena, '$opis', '$okladka', $tmdb_id, '$imdb_id', $typ_plyty)";

                if ($polaczenie -> query($q)) {
                    header("Location: filmy.php");
                }
                else {
                    throw new Exception(mysqli_connect_errno());
                }

                $polaczenie -> close();
            }
        }
        catch(Exception $e) {
            echo "Błąd serwera".$e;
        }
    }
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wypożyczalnia filmów DVD oraz muzyki CD</title>
</head>
<body>
    <header>
        <h1>Wypożyczalnia filmów DVD oraz muzyki CD</h1>
    </header>
    <nav>
            <a href="index.php">Strona główna</a> 
            <a href="filmy.php">Filmy</a>
            <a href="muzyka.php">Muzyka</a>
            <a href="kontakt.php">Kontakt</a>
    </nav>
    <main>
        <h1>Dodawanie filmu</h1>
            <form method="POST">
                <label for="tytul">tytuł:</label>
                <input type="text" name="tytul" id="tytul" autocomplete="off"><br>

                <input type="hidden" name="tytul_oryginalny" id="tytul_oryginalny" value="">
                <input type="hidden" name="tmdb_id" id="tmdb_id" value="">
                <input type="hidden" name="imdb_id" id="imdb_id" value="">

                <div id="podpowiedzi"></div>
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
                                $qu = $polaczenie -> query("select id, nazwa from typy_plyt");
                                if ($qu) {
                                    while ($row = $qu -> fetch_assoc()) {
                                        echo '<option value="'.$row['id'].'">'.$row['nazwa'].'</option>';
                                    }
                                }

                                $qu -> free();
                                $polaczenie -> close();
                            }
                        }
                        catch (Exception $e) {
                            echo 'Błąd serwera';
                        }
                    ?>
                </select><br><br>
            
                <button type="submit">Wyślij formularz</button>
                <button type="reset">Wyczyść</button>
            </form>
        </main>
    <footer>
        <p>Autorzy: Przemysław Gałęziowski i Filip Mały</p>
    </footer>
    <script src="assets/js/script_api.js"></script>
</body>
</html>