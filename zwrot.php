<?php
    require_once "czy_zalogowany.php";

    require_once "polaczenie.php";

    try {
        $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
        if ($polaczenie -> connect_errno) {
            throw new Exception(mysqli_connect_errno());
        }

        $polaczenie -> set_charset("utf8mb4");
        
        $typ = $_GET['typ'] ?? '';
        $id = (int)($_GET['id'] ?? 0);
        $uzytkownik_id = $_SESSION['id'];

        if ($id <= 0) {
            throw new Exception("Nieprawidłowe ID");
        }

        $polaczenie -> begin_transaction();

        if ($typ == 'film') {
            $q_d_f = $polaczenie -> prepare("DELETE FROM wypozyczenia_filmy WHERE film_id = ? AND uzytkownicy_id = ?");
            $q_d_f -> bind_param("ii", $id, $uzytkownik_id);
            $q_d_f -> execute();

            if ($q_d_f -> affected_rows > 0) {
                $q_d_f -> close();

                $q_u_f = $polaczenie -> prepare("UPDATE filmy SET ilosc_dostepnych = ilosc_dostepnych + 1 WHERE id = ?");
                $q_u_f -> bind_param("i", $id);
                $q_u_f -> execute();
                $q_u_f -> close();

                $polaczenie -> commit();
                $_SESSION['konto_komunikat'] = 'Film został zwrócony.';
            }
            else {
                $q_d_f -> close();
                $polaczenie -> rollback();
                $_SESSION['e_konto_komunikat'] = 'Nie znaleziono takiego aktywnego wypożyczenia filmu.';
            }

            header('Location: konto.php');
            exit();
        }

        elseif ($typ == 'muzyka') {
            $q_d_m = $polaczenie -> prepare("DELETE FROM wypozyczenia_muzyka WHERE film_id = ? AND uzytkownicy_id = ?");
            $q_d_m -> bind_param("ii", $id, $uzytkownik_id);
            $q_d_m -> execute();

            if ($q_d_m -> affected_rows > 0) {
                $q_d_m -> close();

                $q_u_m = $polaczenie -> prepare("UPDATE muzyka SET ilosc_dostepnych = ilosc_dostepnych + 1 WHERE id = ?");
                $q_u_m -> bind_param("i", $id);
                $q_u_m -> execute();
                $q_u_m -> close();

                $polaczenie -> commit();
                $_SESSION['konto_komunikat'] = 'Muzyka została zwrócona.';
            }
            else {
                $q_d_m -> close();
                $polaczenie -> rollback();
                $_SESSION['e_konto_komunikat'] = 'Nie znaleziono takiego aktywnego wypożyczenia albumu.';
            }

            header('Location: konto.php');
            exit();
        }

        else {
            $polaczenie -> rollback();
            header('Location: index.php');
            exit();
        }
        
        $polaczenie -> close();
    }

    catch(Exception $e) {
        if (isset($polaczenie) && $polaczenie -> ping()) {
            $polaczenie -> rollback();
            $polaczenie -> close();
        }
        $_SESSION['e_konto_komunikat'] = 'Błąd serwera podczas zwrotu.';
        header('Location: konto.php');
        exit();
    }
?>