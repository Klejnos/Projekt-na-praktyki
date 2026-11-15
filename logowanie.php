<?php
    session_start();
    if (isset($_SESSION['zalogowany']) && $_SESSION['zalogowany'] == true) {
        header('Location: index.php');
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once "polaczenie.php";
        
        try {
            $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
            if ($polaczenie -> connect_errno) {
                throw new Exception(mysqli_connect_errno());
            }
            else{
                $nazwa = $_POST['nazwa'];
                $haslo = $_POST['haslo'];

                $q = sprintf("SELECT * FROM uzytkownicy WHERE nazwa = '%s'", mysqli_real_escape_string($polaczenie, $nazwa));

                if ($result = $polaczenie -> query($q)) {
                    if ($result -> num_rows == 1) {
                        $wiersz = $result -> fetch_assoc();
                        if (password_verify($haslo, $wiersz['haslo'])) {
                            $_SESSION['zalogowany'] = true;
                            $_SESSION['id'] = $wiersz['id'];
                            $_SESSION['nazwa'] = $wiersz['nazwa'];
                            $_SESSION['czy_admin'] = $wiersz['czy_admin'];

                            header('Location: index.php');
                            exit();
                        }
                    }
                    
                    $_SESSION['blad'] = '<span style="color: red">Nieprawidłowy login lub hasło</span>';
                    $result -> free();
                    $polaczenie -> close();
                    header('Location: logowanie.php');
                    exit();
                }
            }
        }

        catch(Exception $e) {
            $_SESSION['blad'] = '<span style="color: red">Błąd serwera</span>';
            exit();
        }
    }
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie</title>
    <link rel="stylesheet" href="assets/css/logowanie.css">
    <link rel="icon" type="image/x-icon" href="assets/img/icon.ico"> 
</head>

<body>
    <header>
        <div id="header-left">
            <img src="assets/img/icon.ico" alt="plyta">
            <h1>Wypożyczalnia filmów i muzyki</h1>
        </div>
        <div id="header-right">
            <a href="konto.php">Konto</a>
            <a href="ulubione.php">Ulubione</a>
        </div>
    </header>

    <nav>
        <a href="index.php">Strona główna</a> 
        <a href="filmy.php">Filmy</a>
        <a href="muzyka.php">Muzyka</a>
        <a href="kontakt.php">Kontakt</a>
    </nav>

    <main>
        <div class="login-box">
            <h2>Zaloguj się</h2>
            <form method="post">
                <label for="nazwa">Nazwa użytkownika:</label><br>
                <input type="text" name="nazwa" id="nazwa" placeholder="np. szrek2137"><br>

                <label for="haslo">Hasło:</label><br>
                <div class="password-container">
                    <input type="password" name="haslo" id="haslo" placeholder="polecane to 1234">
                    <button type="button" id="przycisk_pokazHaslo">
                        <img id="img_pokaz_haslo" src="assets/img/lock.png" alt="Pokaż hasło" draggable="false">
                    </button>
                </div><br>

                <button type="submit">Zaloguj się</button>
            </form>

            <div class="register-link">
                Nie masz konta? <a href="rejestracja.php">Zarejestruj się</a>
            </div>

            <?php
                if (isset($_SESSION['blad'])) {
                    echo '<div class="blad-box">' . $_SESSION['blad'] . '</div>';
                    unset($_SESSION['blad']);
                }
            ?>
        </div>
        <script src="assets/js/pokaz_haslo.js"></script>
    </main>

    <footer>
        <p>2026</p>
        <p>Przemysław Gałęziowski oraz Filip Klein</p>
    </footer>
</body>
</html>