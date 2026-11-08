<?php
    session_start();
    if ((!isset($_POST['nazwa'])) || (!isset($_POST["haslo"]))) {
        header('Location: zaloguj.php');
        exit();
    }

    require_once "polaczenie.php";
    
    try {
        $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
        if ($polaczenie -> connect_errno) {
            throw new Exception(mysqli_connect_errno());
        }
        else {
            $nazwa = $_POST['nazwa'];
            $haslo = $_POST['haslo'];

            // wazne
            $nazwa = htmlentities($nazwa, ENT_QUOTES, "UTF-8");

            if ($result = $polaczenie -> query(
                sprintf("select * from uzytkownicy where nazwa = '%s'",
                mysqli_real_escape_string($polaczenie, $nazwa)))) {
                    if (!$result) throw new Exception($polaczenie -> error);
                    if ($result -> num_rows == 1) {
                        $wiersz = $result -> fetch_assoc();
                        if (password_verify($haslo, $wiersz['haslo'])) {
                            $_SESSION['zalogowany'] = true;
                            $_SESSION['id'] = $wiersz['id'];
                            $_SESSION['nazwa'] = $wiersz['nazwa'];
                            $_SESSION['czy_admin'] = $wiersz['czy_admin'];

                            unset($_SESSION['blad']);
                            $result -> free();
                            header('Location: index.php');
                        }
                        else {
                            $_SESSION['blad'] = '<span style="color: red">Nieprawidłowy login lub hasło</span>';
                            header('Location: logowanie.php');
                        }
                    }
                    else {
                        $_SESSION['blad'] = '<span style="color: red">Nieprawidłowy login lub hasło</span>';
                        header('Location: logowanie.php');
                    }
                }
        }
    }

    
    catch(Exception $e) {
        echo 'Błąd serwera';
    }
    
    $polaczenie -> close();
?>