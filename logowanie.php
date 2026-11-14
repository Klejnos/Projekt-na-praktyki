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
</head>
<body>
    <script src="assets/js/pokaz_haslo.js"></script>
    <form method="post">
        <label for="nazwa">Nazwa:</label><br>
        <input type="text" name="nazwa"><br>
        <label for="haslo">Hasło:</label><br>
        <input type="password" name="haslo" id="haslo">
        <button type="button" id="przycisk_pokazHaslo">Pokaż hasło</button><br><br>
        <button type="submit">Zaloguj się</button>
    </form>

    <a href="rejestracja.php">Zarejestruj się</a>
<?php
    if (isset($_SESSION['blad'])) {
        echo $_SESSION['blad'];
        unset($_SESSION['blad']);
    }
?>
</body>
</html>