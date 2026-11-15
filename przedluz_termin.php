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
                $result = $polaczenie -> query("SELECT * FROM wypozyczenia_filmy 
                WHERE film_id = '$id' AND uzytkownicy_id = '$uzytkownik_id' AND czy_byl_przedluzany = 0");
                if (!$result) {
                    throw new Exception($polaczenie -> error);
                }

                if ($result -> num_rows == 1) {
                    $result_cena = $polaczenie -> query("SELECT cena FROM filmy WHERE id = '$id'");

                    if ($result_cena && $result_cena -> num_rows == 1) {
                        $wiersz_cena = $result_cena -> fetch_assoc();
                        $cena = $wiersz_cena['cena'];
                        $result_cena -> free();

                        $q = "UPDATE wypozyczenia_filmy SET data_zwrotu = DATE_ADD(data_zwrotu, INTERVAL 10 DAY), czy_byl_przedluzany = 1 
                        WHERE uzytkownicy_id = '$uzytkownik_id' AND film_id = '$id'";

                        if ($polaczenie -> query($q)) {
                            $polaczenie -> query("UPDATE uzytkownicy SET portfel = portfel - '$cena' WHERE id = '$uzytkownik_id'");
                            $_SESSION['konto_komunikat'] = 'Termin zwrotu filmu został przedłużony o 10 dni.';
                            header('Location: konto.php');
                            exit();
                        }
                        else {
                            throw new Exception($polaczenie -> error);
                        }
                    }
                    else {
                        throw new Exception($polaczenie -> error);
                    }
                }
                
                else {
                    $_SESSION['e_konto_komunikat'] = 'Nie można przedłużyć terminu oddania tego filmu.';
                    header('Location: konto.php');
                    exit();
                }
                $result -> free();
            }

            elseif ($typ == 'muzyka') {
                $result = $polaczenie -> query("SELECT * FROM wypozyczenia_muzyka 
                WHERE muzyka_id = '$id' AND uzytkownicy_id = '$uzytkownik_id' AND czy_byl_przedluzany = 0");
                if (!$result) {
                    throw new Exception($polaczenie -> error);
                }

                if ($result -> num_rows == 1) {
                    $result_cena = $polaczenie -> query("SELECT cena FROM muzyka WHERE id = '$id'");

                    if ($result_cena && $result_cena -> num_rows == 1) {
                        $wiersz_cena = $result_cena -> fetch_assoc();
                        $cena = $wiersz_cena['cena'];
                        $result_cena -> free();

                        $q = "UPDATE wypozyczenia_muzyka SET data_zwrotu = DATE_ADD(data_zwrotu, INTERVAL 15 DAY), czy_byl_przedluzany = 1 
                        WHERE uzytkownicy_id = '$uzytkownik_id' AND muzyka_id = '$id'";

                        if ($polaczenie -> query($q)) {
                            $polaczenie -> query("UPDATE uzytkownicy SET portfel = portfel - '$cena' WHERE id = '$uzytkownik_id'");
                            $_SESSION['konto_komunikat'] = 'Termin zwrotu muzyki został przedłużony o 15 dni.';
                            header('Location: konto.php');
                            exit();
                        }
                        else {
                            throw new Exception($polaczenie -> error);
                        }
                    }
                    else {
                        throw new Exception($polaczenie -> error);
                    }
                }
                
                else {
                    $_SESSION['e_konto_komunikat'] = 'Nie można przedłużyć terminu oddania tego albumu.';
                    header('Location: konto.php');
                    exit();
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