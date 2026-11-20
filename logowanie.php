<?php
    session_start();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once "polaczenie.php";
        
        try {
            $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
            if ($polaczenie->connect_errno) {
                throw new Exception(mysqli_connect_errno());
            }

            $polaczenie -> set_charset("utf8mb4");

            $nazwa_lub_email = $_POST['nazwa_lub_email'] ?? '';
            $haslo = $_POST['haslo'] ?? '';

            $stmt = $polaczenie->prepare("SELECT id, nazwa, haslo, czy_admin, portfel FROM uzytkownicy WHERE nazwa = ? OR email = ?");
            $stmt->bind_param("ss", $nazwa_lub_email, $nazwa_lub_email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $wiersz = $result->fetch_assoc();
                if (password_verify($haslo, $wiersz['haslo'])) {
                    $_SESSION['zalogowany'] = true;
                    $_SESSION['id'] = $wiersz['id'];
                    $_SESSION['nazwa'] = $wiersz['nazwa'];
                    $_SESSION['czy_admin'] = $wiersz['czy_admin'];
                    $_SESSION['portfel'] = $wiersz['portfel'];

                    $stmt->close();
                    $polaczenie->close();

                    header('Location: index.php');
                    exit();
                }
            }
            
            $_SESSION['blad'] = '<span style="color: red">Nieprawidłowy login lub hasło</span>';
            $stmt->close();
            $polaczenie->close();
            header('Location: logowanie.php');
            exit();

        } catch(Exception $e) {
            if (isset($polaczenie) && $polaczenie instanceof mysqli && !$polaczenie->connect_errno) {
                $polaczenie->close();
            }
            $_SESSION['blad'] = '<span style="color: red">Błąd serwera</span>';
            header('Location: logowanie.php');
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
        <div class="header-top">
            <a href="index.php" class="brand">
                <img src="assets/img/icon.ico" alt="plyta">
                <div class="brand-text">
                    <strong>Wypożyczalnia</strong>
                    <small>Filmów i muzyki</small>
                </div>
            </a>
            <div class="header-actions">
                <a href="konto.php" class="top-btn">Konto</a>
            </div>
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
                <label for="nazwa_lub_email">Nazwa użytkownika lub adres e-mail:</label><br>
                <input type="text" name="nazwa_lub_email" id="nazwa"><br>

                <label for="haslo">Hasło:</label><br>
                <div class="password-container">
                    <input type="password" name="haslo" id="haslo">
                    <button type="button" id="przycisk_pokazHaslo">
                        <img id="img_pokaz_haslo" src="assets/img/lock.png" alt="Pokaż hasło" draggable="false">
                    </button>
                </div><br>

                <button type="submit">Zaloguj się</button>
            </form>

            <div class="register-link">Nie masz konta? <a href="rejestracja.php">Zarejestruj się</a></div>

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
        <p>2026 - Przemysław Gałęziowski oraz Filip Klein</p>
    </footer>
</body>
</html>