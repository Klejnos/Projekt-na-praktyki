<?php
    session_start();
    if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] == false) {
        header('Location: logowanie.php');
        exit();
    }

    require_once "polaczenie.php";

    try {
        $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
        if ($polaczenie -> connect_errno) {
            throw new Exception(mysqli_connect_errno());
        }
        else {
            $typ = $_GET['typ'] ?? '';
            $id = (int)($_GET['id'] ?? 0);
            $uzytkownik_id = $_SESSION['id'];

            if ($id == 0) {
                throw new Exception("Nieprawidłowe ID");
            }

            if ($typ == 'film') {
                $q = "UPDATE wypozyczenia_filmy SET data_zwrotu = DATE_ADD(data_zwrotu, INTERVAL 10 DAY) 
                WHERE uzytkownicy_id = '$uzytkownik_id' AND film_id = '$id'";

                if ($polaczenie -> query($q)) {
                    $_SESSION['konto_komunikat'] = 'Termin zwrotu filmu został przedłużony o 10 dni.';
                    header('Location: konto.php');
                    exit();
                }
                else {
                    throw new Exception($polaczenie -> error);
                }
            }

            elseif ($typ == 'muzyka') {
                $q = "UPDATE wypozyczenia_muzyka SET data_zwrotu = DATE_ADD(data_zwrotu, INTERVAL 15 DAY) 
                WHERE uzytkownicy_id = '$uzytkownik_id' AND muzyka_id = '$id'";

                if ($polaczenie -> query($q)) {
                    $_SESSION['konto_komunikat'] = 'Termin zwrotu muzyki został przedłużony o 15 dni.';
                    header('Location: konto.php');
                    exit();
                }
                else {
                    throw new Exception($polaczenie -> error);
                }
            }

            else {
                header('Location: index.php');
            }
        }

        $polaczenie -> close();
    }

    catch(Exception $e) {
        echo "Błąd serwera";
    }
?>