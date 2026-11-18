<?php
    require_once "czy_zalogowany.php";

    $typ = $_GET['typ'] ?? '';
    $id = (int) ($_GET['id'] ?? 0);
    $uzytkownik_id = $_SESSION['id'];

    if ($id <= 0 || !in_array($typ, ['film', 'muzyka'], true)) {
        header('Location: index.php');
        exit();
    }

    require_once "polaczenie.php";

    try {
        $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
        if ($polaczenie->connect_errno) {
            throw new Exception("Błąd połączenia z bazą");
        }

        $polaczenie -> begin_transaction();

        $q_u = $polaczenie -> prepare("SELECT portfel FROM uzytkownicy WHERE id = ? FOR UPDATE");
        $q_u -> bind_param("i", $uzytkownik_id);
        $q_u -> execute();
        $wynik_u = $q_u -> get_result();
        if ($wynik_u -> num_rows !== 1) {
            throw new Exception("Nie znaleziono użytkownika");
        }
        $wiersz_u = $wynik_u -> fetch_assoc();
        $portfel = (float) $wiersz_u['portfel'];
        $q_u -> close();

        $cena = 0;
        $tabela_wypożyczeń = "";
        $tabela_typ = "";
        $kolumna_id = "";
        $dni = 0;

        if ($typ == 'film') {
            $tabela_wypożyczeń = "wypozyczenia_filmy";
            $tabela_typ = "filmy";
            $kolumna_id = "film_id";
            $dni = 10;
        } else {
            $tabela_wypożyczeń = "wypozyczenia_muzyka";
            $tabela_typ = "muzyka";
            $kolumna_id = "muzyka_id";
            $dni = 15;
        }

        $q_w = $polaczenie -> prepare("SELECT * FROM $tabela_wypożyczeń WHERE $kolumna_id = ? AND uzytkownicy_id = ? AND czy_byl_przedluzany = 0");
        $q_w -> bind_param("ii", $id, $uzytkownik_id);
        $q_w -> execute();
        $wynik_w = $q_w -> get_result();

        if ($wynik_w -> num_rows !== 1) {
            $q_w -> close();
            $polaczenie -> rollback();
            $_SESSION['e_konto_komunikat'] = 'Nie można przedłużyć terminu oddania tego elementu.';
            header('Location: konto.php');
            exit();
        }
        $q_w -> close();

        $q_t = $polaczenie->prepare("SELECT cena FROM $tabela_typ WHERE id = ?");
        $q_t -> bind_param("i", $id);
        $q_t -> execute();
        $wynik_t = $q_t -> get_result();
        if ($wynik_t -> num_rows !== 1) {
            $q_t -> close();
            throw new Exception("Nie znaleziono produktu");
        }
        $wiersz_t = $wynik_t -> fetch_assoc();
        $cena = (float) $wiersz_t['cena'];
        $q_t -> close();

        if ($portfel < $cena) {
            $polaczenie -> rollback();
            $_SESSION['e_konto_komunikat'] = 'Brak wystarczających środków w portfelu aby przedłużyć wypożyczenie.';
            header('Location: konto.php');
            exit();
        }

        $q_up_w = $polaczenie->prepare("UPDATE $tabela_wypożyczeń SET data_zwrotu = DATE_ADD(data_zwrotu, INTERVAL ? DAY), czy_byl_przedluzany = 1 WHERE uzytkownicy_id = ? AND $kolumna_id = ?");
        $q_up_w -> bind_param("iii", $dni, $uzytkownik_id, $id);
        $q_up_w -> execute();
        $q_up_w -> close();


        $q_up_u = $polaczenie->prepare("UPDATE uzytkownicy SET portfel = portfel - ? WHERE id = ?");
        $q_up_u -> bind_param("di", $cena, $uzytkownik_id);
        $q_up_u -> execute();
        $q_up_u -> close();


        $polaczenie -> commit();
        $polaczenie -> close();

        $_SESSION['konto_komunikat'] = "Termin zwrotu został przedłużony o $dni dni (pobrano $cena zł).";
        header('Location: konto.php');
        exit();

    } catch(Exception $e) {
        if (isset($polaczenie) && $polaczenie->ping()) {
            $polaczenie -> rollback();
            $polaczenie -> close();
        }
        $_SESSION['e_konto_komunikat'] = 'Błąd serwera podczas przedłużania wypożyczenia.';
        header('Location: konto.php');
        exit();
    }
?>