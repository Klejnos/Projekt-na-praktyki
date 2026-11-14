<?php
    session_start();
    if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] == false) {
        header('Location: logowanie.php');
        exit();
    }

    $typ = $_GET['typ'] ?? '';
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if (!in_array($typ, ['film', 'muzyka'], true) || $id <= 0) {
        header('Location: index.php');
        exit();
    }

    require_once "polaczenie.php";

    $uzytkownikId = (int) $_SESSION['id'];
    $powrot = $typ === 'film' ? 'film.php?id=' . $id : 'album.php?id=' . $id;

    try {
        $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);

        if ($polaczenie->connect_errno) {
            throw new Exception();
        }

        $polaczenie->set_charset("utf8mb4");

        if ($typ === 'film') {
            $stmt = $polaczenie->prepare("SELECT tytul, ilosc_dostepnych FROM filmy WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $wynik = $stmt->get_result();
            $film = $wynik && $wynik->num_rows === 1 ? $wynik->fetch_assoc() : null;
            $stmt->close();

            if (!$film) {
                $_SESSION['rent_blad'] = 'Nie znaleziono wybranego filmu.';
                header('Location: filmy.php');
                exit();
            }

            if ($film['ilosc_dostepnych'] !== null && (int) $film['ilosc_dostepnych'] <= 0) {
                $_SESSION['rent_blad'] = 'Ten film jest chwilowo niedostępny.';
                header('Location: ' . $powrot);
                exit();
            }

            $stmt = $polaczenie->prepare(
                "SELECT 1 FROM wypozyczenia_filmy WHERE uzytkownicy_id = ? AND film_id = ? LIMIT 1"
            );
            $stmt->bind_param("ii", $uzytkownikId, $id);
            $stmt->execute();
            $wynik = $stmt->get_result();
            $juzWypozyczony = $wynik && $wynik->num_rows === 1;
            $stmt->close();

            if ($juzWypozyczony) {
                $_SESSION['rent_blad'] = 'Masz już wypożyczony ten film.';
                header('Location: ' . $powrot);
                exit();
            }

            $stmt = $polaczenie->prepare(
                "INSERT INTO wypozyczenia_filmy
                    (uzytkownicy_id, film_id, data_wypozyczenia, data_zwrotu)
                 VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 10 DAY))"
            );
            $stmt->bind_param("ii", $uzytkownikId, $id);
            $stmt->execute();
            $stmt->close();

            if ($film['ilosc_dostepnych'] !== null) {
                $stmt = $polaczenie->prepare(
                    "UPDATE filmy SET ilosc_dostepnych = ilosc_dostepnych - 1
                     WHERE id = ? AND ilosc_dostepnych > 0"
                );
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
            }

            $_SESSION['rent_sukces'] = 'Film został wypożyczony: ' . $film['tytul'];
        } else {
            $stmt = $polaczenie->prepare("SELECT tytul, ilosc_dostepnych FROM muzyka WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $wynik = $stmt->get_result();
            $album = $wynik && $wynik->num_rows === 1 ? $wynik->fetch_assoc() : null;
            $stmt->close();

            if (!$album) {
                $_SESSION['rent_blad'] = 'Nie znaleziono wybranego albumu.';
                header('Location: muzyka.php');
                exit();
            }

            if ($album['ilosc_dostepnych'] !== null && (int) $album['ilosc_dostepnych'] <= 0) {
                $_SESSION['rent_blad'] = 'Ten album jest chwilowo niedostępny.';
                header('Location: ' . $powrot);
                exit();
            }

            $stmt = $polaczenie->prepare(
                "SELECT 1 FROM wypozyczenia_muzyka WHERE uzytkownicy_id = ? AND muzyka_id = ? LIMIT 1"
            );
            $stmt->bind_param("ii", $uzytkownikId, $id);
            $stmt->execute();
            $wynik = $stmt->get_result();
            $juzWypozyczony = $wynik && $wynik->num_rows === 1;
            $stmt->close();

            if ($juzWypozyczony) {
                $_SESSION['rent_blad'] = 'Masz już wypożyczony ten album.';
                header('Location: ' . $powrot);
                exit();
            }

            $stmt = $polaczenie->prepare(
                "INSERT INTO wypozyczenia_muzyka
                    (uzytkownicy_id, muzyka_id, data_wypozyczenia, data_zwrotu)
                 VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 15 DAY))"
            );
            $stmt->bind_param("ii", $uzytkownikId, $id);
            $stmt->execute();
            $stmt->close();

            if ($album['ilosc_dostepnych'] !== null) {
                $stmt = $polaczenie->prepare(
                    "UPDATE muzyka SET ilosc_dostepnych = ilosc_dostepnych - 1
                     WHERE id = ? AND ilosc_dostepnych > 0"
                );
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
            }

            $_SESSION['rent_sukces'] = 'Album został wypożyczony: ' . $album['tytul'];
        }

        $polaczenie->close();
    } catch (Exception $e) {
        $_SESSION['rent_blad'] = 'Nie udało się wypożyczyć. Spróbuj ponownie później.';
    }

    header('Location: ' . $powrot);
    exit();
