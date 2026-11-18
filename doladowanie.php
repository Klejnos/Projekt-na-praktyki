<?php
    require_once "czy_zalogowany.php";

    require_once "polaczenie.php";

    try {
        $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
        if ($polaczenie -> connect_errno) {
            throw new Exception(mysqli_connect_errno());
        }
        $kwota = (int) ($_GET['k'] ?? 0);
        $uzytkownik_id = $_SESSION['id'];

        $kwoty = [10, 20, 35, 50];
        if (!in_array($kwota, $kwoty)) {
            header('Location: index.php');
            exit();
        }

        $polaczenie -> begin_transaction();

        $q = $polaczenie -> prepare("UPDATE uzytkownicy SET portfel = portfel + ? WHERE id = ?");
        $q -> bind_param("di", $kwota, $uzytkownik_id);

        if ($q -> execute()) {
            if ($q -> affected_rows > 0) {
                $polaczenie -> commit();
                $_SESSION['konto_komunikat'] = "Portfel został doładowany kwotą '$kwota' zł.";
            }
            else {
                $polaczenie -> rollback();
                $_SESSION['e_konto_komunikat'] = 'Nie udało się doładować konta.';
            }
        }

        else {
            throw new Exception($q -> error);
        }

        $q -> close();
        $polaczenie -> close();

        header('Location: konto.php');
        exit();
    }

    catch(Exception $e) {
        if (isset($polaczenie) && $polaczenie -> ping()) {
            $polaczenie -> rollback();
        }
        $_SESSION['e_konto_komunikat'] = 'Błąd serwera podczas doładowywania konta.';
        header('Location: konto.php');
        exit();
    }
?>