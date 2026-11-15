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

            $polaczenie -> begin_transaction();

            if ($typ == 'film') {
                $q = "DELETE FROM wypozyczenia_filmy WHERE film_id = '$id' AND uzytkownicy_id = '$uzytkownik_id'";

                if ($polaczenie -> query($q)) {
                    if ($polaczenie -> affected_rows > 0) {
                        $polaczenie -> query("UPDATE filmy SET ilosc_dostepnych = ilosc_dostepnych + 1 WHERE id = '$id'");
                        $polaczenie -> commit();

                        $_SESSION['konto_komunikat'] = 'Film został zwrócony.';
                        header('Location: konto.php');
                        exit();
                    }
                    else {
                        $polaczenie -> rollback();
                        $_SESSION['e_konto_komunikat'] = 'Nie znaleziono takiego aktywnego wypożyczenia filmu.';
                        header('Location: konto.php');
                        exit();
                    }
                }
                else {
                    throw new Exception($polaczenie -> error);
                }
            }

            elseif ($typ == 'muzyka') {
                $q = "DELETE FROM wypozyczenia_muzyka WHERE muzyka_id = '$id' AND uzytkownicy_id = '$uzytkownik_id'";

                if ($polaczenie -> query($q)) {
                    if ($polaczenie -> affected_rows > 0) {
                        $polaczenie -> query("UPDATE muzyka SET ilosc_dostepnych = ilosc_dostepnych + 1 WHERE id = '$id'");
                        $polaczenie -> commit();

                        $_SESSION['konto_komunikat'] = 'Muzyka została zwrócona.';
                        header('Location: konto.php');
                        exit();
                    }
                    else {
                        $polaczenie -> rollback();
                        $_SESSION['e_konto_komunikat'] = 'Nie znaleziono takiego aktywnego wypożyczenia albumu.';
                        header('Location: konto.php');
                        exit();
                    }
                }
                else {
                    throw new Exception($polaczenie -> error);
                }
            }

            else {
                $polaczenie -> rollback();
                header('Location: index.php');
                exit();
            }
        }

        $polaczenie -> close();
    }

    catch(Exception $e) {
        if (isset($polaczenie) && $polaczenie -> ping()) {
            $polaczenie -> rollback();
        }
        echo "Błąd serwera";
    }
?>