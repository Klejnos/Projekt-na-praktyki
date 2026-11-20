<?php
    require_once "czy_zalogowany.php";

    $typ = $_GET['typ'] ?? '';
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if (!in_array($typ, ['film', 'muzyka', 'utwor'], true) || $id <= 0) {
        header('Location: index.php');
        exit();
    }

    require_once "polaczenie.php";

    $uzytkownikId = (int) $_SESSION['id'];
    if ($typ === 'film') {
        $powrot = 'film.php?id='.$id;
    } 

    elseif ($typ === 'muzyka') {
        $powrot = 'album.php?id='.$id;
    } 
    
    else {
        $powrot = 'utwor.php?id='.$id;
    }

    try {
        $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);

        if ($polaczenie->connect_errno) {
            throw new Exception();
        }

        $polaczenie->set_charset("utf8mb4");
        $polaczenie -> begin_transaction();

        $q = $polaczenie -> prepare("SELECT portfel FROM uzytkownicy WHERE id = ? FOR UPDATE");
        $q -> bind_param("i", $uzytkownikId);
        $q -> execute();
        $portfel_wynik = $q -> get_result();
        $dane_uzytkownika = $portfel_wynik ? $portfel_wynik -> fetch_assoc() : null;
        $q -> close();

        if (!$dane_uzytkownika) {
            throw new Exception("Nie znaleziono użytkownika");
        }

        $portfel_stan = (float) $dane_uzytkownika['portfel'];

        if ($typ === 'film') {
            $stmt = $polaczenie -> prepare("SELECT tytul, ilosc_dostepnych, cena FROM filmy WHERE id = ? LIMIT 1");
            $stmt -> bind_param("i", $id);
            $stmt -> execute();
            $wynik = $stmt -> get_result();
            $film = $wynik && $wynik->num_rows === 1 ? $wynik -> fetch_assoc() : null;
            $stmt->close();

            if (!$film) {
                $polaczenie -> rollback();
                $_SESSION['rent_blad'] = 'Nie znaleziono wybranego filmu.';
                header('Location: filmy.php');
                exit();
            }

            if ($film['ilosc_dostepnych'] !== null && (int) $film['ilosc_dostepnych'] <= 0) {
                $polaczenie -> rollback();
                $_SESSION['rent_blad'] = 'Ten film jest chwilowo niedostępny.';
                header('Location: ' . $powrot);
                exit();
            }

            $cena_wypozyczenia = (float) $film['cena'];
            if ($portfel_stan < $cena_wypozyczenia) {
                $polaczenie -> rollback();
                $_SESSION['rent_blad'] = 'Nie masz wystarczająco dużo pieniędzy, aby wypożyczyć ten film.';
                header('Location: '.$powrot);
                exit();
            }

            $stmt = $polaczenie -> prepare(
                "SELECT 1 FROM wypozyczenia_filmy WHERE uzytkownicy_id = ? AND film_id = ? LIMIT 1"
            );
            $stmt -> bind_param("ii", $uzytkownikId, $id);
            $stmt -> execute();
            $wynik = $stmt -> get_result();
            $juzWypozyczony = $wynik && $wynik->num_rows === 1;
            $stmt -> close();

            if ($juzWypozyczony) {
                $_SESSION['rent_blad'] = 'Masz już wypożyczony ten film.';
                header('Location: ' . $powrot);
                exit();
            }

            $q = $polaczenie -> prepare("UPDATE uzytkownicy SET portfel = portfel - ? WHERE id = ?");
            $q -> bind_param("di", $cena_wypozyczenia, $uzytkownikId);
            $q -> execute();
            $q -> close();

            $stmt = $polaczenie -> prepare(
                "INSERT INTO wypozyczenia_filmy
                    (uzytkownicy_id, film_id, data_wypozyczenia, data_zwrotu)
                 VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 10 DAY))"
            );
            $stmt -> bind_param("ii", $uzytkownikId, $id);
            $stmt -> execute();
            $stmt -> close();

            if ($film['ilosc_dostepnych'] !== null) {
                $stmt = $polaczenie -> prepare(
                    "UPDATE filmy SET ilosc_dostepnych = ilosc_dostepnych - 1
                     WHERE id = ? AND ilosc_dostepnych > 0"
                );
                $stmt -> bind_param("i", $id);
                $stmt -> execute();
                $stmt -> close();
            }

            $_SESSION['rent_sukces'] = 'Film został wypożyczony: ' . $film['tytul'];
        } 
        
        elseif ($typ === "muzyka") {
            $stmt = $polaczenie -> prepare("SELECT tytul, ilosc_dostepnych, cena FROM muzyka WHERE id = ? LIMIT 1");
            $stmt -> bind_param("i", $id);
            $stmt -> execute();
            $wynik = $stmt -> get_result();
            $album = $wynik && $wynik->num_rows === 1 ? $wynik -> fetch_assoc() : null;
            $stmt->close();

            if (!$album) {
                $polaczenie -> rollback();
                $_SESSION['rent_blad'] = 'Nie znaleziono wybranego albumu.';
                header('Location: muzyka.php');
                exit();
            }

            if ($album['ilosc_dostepnych'] !== null && (int) $album['ilosc_dostepnych'] <= 0) {
                $polaczenie -> rollback();
                $_SESSION['rent_blad'] = 'Ten album jest chwilowo niedostępny.';
                header('Location: ' . $powrot);
                exit();
            }

            $cena_wypozyczenia = (float) $album['cena'];
            if ($portfel_stan < $cena_wypozyczenia) {
                $polaczenie -> rollback();
                $_SESSION['rent_blad'] = 'Nie masz wystarczająco dużo pieniędzy, aby wypożyczyć ten album.';
                header('Location: '.$powrot);
                exit();
            }

            $stmt = $polaczenie -> prepare(
                "SELECT 1 FROM wypozyczenia_muzyka WHERE uzytkownicy_id = ? AND muzyka_id = ? LIMIT 1"
            );
            $stmt -> bind_param("ii", $uzytkownikId, $id);
            $stmt -> execute();
            $wynik = $stmt -> get_result();
            $juzWypozyczony = $wynik && $wynik->num_rows === 1;
            $stmt -> close();

            if ($juzWypozyczony) {
                $_SESSION['rent_blad'] = 'Masz już wypożyczony ten album.';
                header('Location: ' . $powrot);
                exit();
            }

            $q = $polaczenie -> prepare("UPDATE uzytkownicy SET portfel = portfel - ? WHERE id = ?");
            $q -> bind_param("di", $cena_wypozyczenia, $uzytkownikId);
            $q -> execute();
            $q -> close();

            $stmt = $polaczenie -> prepare(
                "INSERT INTO wypozyczenia_muzyka
                    (uzytkownicy_id, muzyka_id, data_wypozyczenia, data_zwrotu)
                 VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 15 DAY))"
            );
            $stmt -> bind_param("ii", $uzytkownikId, $id);
            $stmt -> execute();
            $stmt -> close();

            if ($album['ilosc_dostepnych'] !== null) {
                $stmt = $polaczenie -> prepare(
                    "UPDATE muzyka SET ilosc_dostepnych = ilosc_dostepnych - 1
                     WHERE id = ? AND ilosc_dostepnych > 0"
                );
                $stmt -> bind_param("i", $id);
                $stmt -> execute();
                $stmt -> close();
            }

            $_SESSION['rent_sukces'] = 'Album został wypożyczony: ' . $album['tytul'];
        }

        else {
            $stmt = $polaczenie -> prepare("SELECT tytul, ilosc_dostepnych, cena FROM utwory WHERE id = ? LIMIT 1");
            $stmt -> bind_param("i", $id);
            $stmt -> execute();
            $wynik = $stmt -> get_result();
            $utwor = $wynik && $wynik->num_rows === 1 ? $wynik->fetch_assoc() : null;
            $stmt -> close();

            if (!$utwor) {
                $polaczenie -> rollback();
                $_SESSION['rent_blad'] = 'Nie znaleziono wybranego utworu.';
                header('Location: ' . $powrot);
                exit();
            }

            if ($utwor['ilosc_dostepnych'] !== null && (int) $utwor['ilosc_dostepnych'] <= 0) {
                $polaczenie -> rollback();
                $_SESSION['rent_blad'] = 'Ten utwór jest chwilowo niedostępny.';
                header('Location: ' . $powrot);
                exit();
            }

            $cena_wypozyczenia = (float) $utwor['cena'];
            if ($portfel_stan < $cena_wypozyczenia) {
                $polaczenie -> rollback();
                $_SESSION['rent_blad'] = 'Nie masz wystarczająco dużo pieniędzy, aby wypożyczyć ten utwór.';
                header('Location: '.$powrot);
                exit();
            }

            $stmt = $polaczenie -> prepare(
                "SELECT 1 FROM wypozyczenia_utwory WHERE uzytkownicy_id = ? AND utwor_id = ? LIMIT 1"
            );
            $stmt -> bind_param("ii", $uzytkownikId, $id);
            $stmt -> execute();
            $wynik = $stmt -> get_result();
            $juzWypozyczony = $wynik && $wynik->num_rows === 1;
            $stmt -> close();

            if ($juzWypozyczony) {
                $_SESSION['rent_blad'] = 'Masz już wypożyczony ten utwór.';
                header('Location: ' . $powrot);
                exit();
            }

            $q = $polaczenie -> prepare("UPDATE uzytkownicy SET portfel = portfel - ? WHERE id = ?");
            $q -> bind_param("di", $cena_wypozyczenia, $uzytkownikId);
            $q -> execute();
            $q -> close();

            $stmt = $polaczenie -> prepare(
                "INSERT INTO wypozyczenia_utwory
                    (uzytkownicy_id, utwor_id, data_wypozyczenia, data_zwrotu)
                 VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 15 DAY))"
            );
            $stmt -> bind_param("ii", $uzytkownikId, $id);
            $stmt -> execute();
            $stmt -> close();

            if ($utwor['ilosc_dostepnych'] !== null) {
                $stmt = $polaczenie -> prepare(
                    "UPDATE utwory SET ilosc_dostepnych = ilosc_dostepnych - 1
                     WHERE id = ? AND ilosc_dostepnych > 0"
                );
                $stmt -> bind_param("i", $id);
                $stmt -> execute();
                $stmt -> close();
            }

            $_SESSION['rent_sukces'] = 'Utwór został wypożyczony: ' . $utwor['tytul'];
        }
        $polaczenie -> commit();
        $polaczenie->close();
    } catch (Exception $e) {
        if (isset($polaczenie) && $polaczenie -> ping()) {
            $polaczenie -> rollback();
            $polaczenie -> close();
        }
        $_SESSION['rent_blad'] = 'Nie udało się wypożyczyć.';
    }

    header('Location: ' . $powrot);
    exit();
?>