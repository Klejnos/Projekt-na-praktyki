<?php
    session_start();
    if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] == false) {
        header('Location: logowanie.php');
        exit();
    }

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($id > 0) {
        require_once "polaczenie.php";

        try {
            $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);

            if (!$polaczenie->connect_errno) {
                $stmt = $polaczenie->prepare("DELETE FROM filmy WHERE id = ?");

                if ($stmt) {
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $stmt->close();
                }

                $polaczenie->close();
                $_SESSION['usunieto_film'] = true;
            }
        } catch (Exception $e) {
            // Po błędzie wracamy na listę bez komunikatu.
        }
    }

    header('Location: filmy.php');
    exit();
