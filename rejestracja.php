<?php
    session_start();
    if (isset($_POST['nazwa'])) {
        $ok = true;
        $nazwa = $_POST['nazwa'];
        $nazwa = htmlentities($nazwa, ENT_QUOTES , "UTF-8");

        if ((strlen($nazwa) < 3) || (strlen($nazwa) > 20)) {
            $ok = false;
            $_SESSION['e_nazwa'] = "Nazwa musi posiadać od 3 do 20 znaków";
        }

        if (ctype_alnum($nazwa) == false) {
            $ok = false;
            $_SESSION['e_nazwa'] = "Nazwa może składać się tylko z liter i cyfr (bez polskich znaków)";
        }

        $haslo1 = $_POST['haslo1'] ?? ''; // sprawdza czy zmienna istnieje i czy nie posiada wartości null
        $haslo2 = $_POST['haslo2'] ?? '';
        $haslo1 = htmlentities($haslo1, ENT_QUOTES , "UTF-8");
        $haslo2 = htmlentities($haslo2, ENT_QUOTES , "UTF-8");

        if ((strlen($haslo1) < 8) || (strlen($haslo1) > 20)) {
            $ok = false;
            $_SESSION['e_haslo'] = "Hasło musi posiadać od 8 do 20 znaków";
        }

        if ($haslo1 != $haslo2) {
            $ok = false;
            $_SESSION['e_haslo'] = "Hasła muszą być identyczne";
        }

        $haslo_hash = password_hash($haslo1, PASSWORD_DEFAULT);

        $czy_admin = 1;
        if (!isset($_POST['czy_admin'])) {
            $czy_admin = 0;
        }

        require_once "polaczenie.php";

        try {
            $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
            if ($polaczenie -> connect_errno) {
                throw new Exception(mysqli_connect_errno());
            }
            else {
                $result = $polaczenie -> query("select id from uzytkownicy where nazwa = '$nazwa'");
                if (!$result) throw new Exception($polaczenie -> error);

                $ile_nickow = $result -> num_rows;
                if ($ile_nickow > 0) {
                    $ok = false;
                    $_SESSION['e_nazwa'] = "Istnieje już konto z taką nazwą";
                }

                if ($ok) {
                    if ($polaczenie -> query("INSERT INTO uzytkownicy VALUES (null, '$nazwa', '$haslo_hash', '$czy_admin')")) {
                        $_SESSION['udana_rejestracja'] = true;
                        header('Location: witam.php');
                        exit();
                    }
                    else {
                        throw new Exception($polaczenie -> error);
                    }
                }
                $result -> free();
                $polaczenie -> close();
            }
        }
        catch(Exception $e) {
            echo "Błąd serwera";
        }

    }
?>


<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zarejestruj się</title>
</head>
<body>
    <script src="assets/js/pokaz_haslo.js"></script>
    <form method="post">
        <label for="nazwa">Nazwa:</label><br>
        <input type="text" name="nazwa" placeholder="Bez polskich znaków">

        <?php
            if (isset($_SESSION['e_nazwa'])) {
                echo '<div class="error">'.$_SESSION['e_nazwa']."</div>";
                unset($_SESSION['e_nazwa']);
            }
        ?><br>

        <label for="haslo1">Haslo:</label><br>
        <input type="password" id="haslo1" name="haslo1" placeholder="8 - 20 znaków"><br>

        <label for="haslo2">Powtórz haslo:</label><br>
        <input type="password" id="haslo2" name="haslo2">
        <button type="button" id="przycisk_pokazHaslo">Pokaż hasło</button>

        <?php
            if (isset($_SESSION['e_haslo'])) {
                echo '<div class="error">'.$_SESSION['e_haslo']."</div>";
                unset($_SESSION['e_haslo']);
            }
        ?><br>

        <label>
            <input name="czy_admin" type="checkbox">Zarejestruj jako admin
        </label>

        <br><br><button type="submit">Zarejestruj się</button>
    </form>
</body>
</html>