<?php
// witam.php — potwierdzenie rejestracji.
// Aktywuje konto tokenem z maila lub pokazuje podziękowanie po rejestracji.


    session_start();

    // Wejście tylko po rejestracji albo z linkiem aktywacyjnym.
    if (!isset($_SESSION['udana_rejestracja']) && !isset($_GET['token'])) {
        header('Location: index.php');
        exit();
    }

    require_once "polaczenie.php";
    $komunikat = "";
    $pokaz_dziekujemy = false;

    // Wariant 1: użytkownik właśnie założył konto.
    if (isset($_SESSION['udana_rejestracja'])) {
        $pokaz_dziekujemy = true;
        unset($_SESSION['udana_rejestracja']);
    }

    // Wariant 2: kliknięcie linku aktywacyjnego z tokenem.
    elseif (isset($_GET['token'])) {
        $token = $_GET['token'];

        try {
            $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
            if ($polaczenie -> connect_errno) {
                throw new Exception($polaczenie -> connect_errno);
            }

            // Token musi istnieć i konto nie może być jeszcze aktywne.
            $q_s = $polaczenie -> prepare("SELECT id FROM uzytkownicy WHERE token = ? AND czy_potwierdzony = 0");
            $q_s -> bind_param("s", $token);
            $q_s -> execute();
            $result = $q_s -> get_result();

            if ($result -> num_rows == 1) {
                // Aktywacja konta i unieważnienie tokenu.
                $q_u = $polaczenie -> prepare("UPDATE uzytkownicy SET czy_potwierdzony = 1, token = NULL WHERE token = ?");
                $q_u -> bind_param("s", $token);

                if ($q_u -> execute()) {
                    $komunikat = "Konto zostało pomyślnie aktywowane. Możesz się teraz zalogować.";
                }
                else {
                    $komunikat = "Wystąpił błąd podczas aktywacji konta.";
                }
                $q_u -> close();
            }

            else {
                $komunikat = "Nieprawidłowy link aktywacyjny lub konto zostało już aktywowane.";
            }

            $q_s -> close();
            $polaczenie -> close();
        }

        catch (Exception $e) {
            $komunikat = "Błąd serwera";
        }
    }
    else {
        header('Location: index.php');
        exit();
    }
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wypożyczalnia - witamy</title>
    <link rel="stylesheet" href="assets/css/witam.css">
    <link rel="icon" type="image/x-icon" href="assets/img/icon.ico"> 
</head>

<body>
    <main>
    <div class="witaj">
        <img src="assets/img/icon.ico" alt="plyta">
        <?php if ($pokaz_dziekujemy): ?>
            <h1>Dziękujemy za zarejestrowanie się :D</h1>
            <p>Na podany adres e-mail wysłano link aktywacyjny. Potwiedź swoje konto, aby móc się zalogować.</p>
            <a href="logowanie.php">Zaloguj się</a>
        <?php else: ?>
            <h1>Aktywacja konta</h1>
            <p><b><?= $komunikat ?></b></p>
            <br>
            <a href="logowanie.php">Zaloguj się</a>
        <?php endif; ?>
    </div>
    </main>
</body>
</html>