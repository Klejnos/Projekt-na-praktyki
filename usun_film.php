<?php
// usun_film.php — usunięcie filmu (tylko administrator).


    require_once "czy_zalogowany.php";

    // Usuwanie filmów jest dostępne tylko dla administratora.
    if (!isset($_SESSION['czy_admin']) || $_SESSION['czy_admin'] != 1) {
        header('Location: index.php');
        exit();
    }

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($id > 0) {
        require_once "polaczenie.php";

        try {
            $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);

            if (!$polaczenie->connect_errno) {
                // Usunięcie filmu z bazy danych.
                $stmt = $polaczenie->prepare("DELETE FROM filmy WHERE id = ?");

                if ($stmt) {
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $stmt->close();
                }

                $polaczenie->close();
                // Flaga komunikatu odczytywana przez filmy.php.
                $_SESSION['usunieto_film'] = true;
            }
        } catch (Exception $e) {
            // Po błędzie wracamy na listę bez komunikatu.
        }
    }

    header('Location: filmy.php');
    exit();
?>