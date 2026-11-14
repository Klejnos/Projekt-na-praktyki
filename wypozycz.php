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
                $q = "INSERT INTO wypozyczenia_filmy (uzytkownicy_id, film_id, data_wypozyczenia, data_zwrotu) 
                VALUES ($uzytkownik_id, $id, NOW(), DATE_ADD(NOW(), INTERVAL 10 DAY))";

                if ($polaczenie -> query($q)) {
                    $polaczenie -> query("UPDATE filmy SET ilosc_dostepnych = ilosc_dostepnych - 1 WHERE id = $id AND ilosc_dostepnych > 0");
                    $_SESSION['wypozyczono_film'] = true;

                    header('Location: filmy.php');
                    exit();
                }
                else {
                    throw new Exception($polaczenie -> error);
                }
            }

            elseif ($typ == 'muzyka') {
                $q = "INSERT INTO wypozyczenia_muzyka (uzytkownicy_id, muzyka_id, data_wypozyczenia, data_zwrotu) 
                VALUES ($uzytkownik_id, $id, NOW(), DATE_ADD(NOW(), INTERVAL 15 DAY))";

                if ($polaczenie -> query($q)) {
                    $polaczenie -> query("UPDATE muzyka SET ilosc_dostepnych = ilosc_dostepnych - 1 WHERE id = $id AND ilosc_dostepnych > 0");
                    $_SESSION['wypozyczono_album'] = true;

                    header('Location: muzyka.php');
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