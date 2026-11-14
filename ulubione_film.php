<?php
    session_start();
    if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] == false) {
        header('Location: logowanie.php');
        exit();
    }

    if (!isset($_SESSION['czy_admin']) || (int) $_SESSION['czy_admin'] !== 1) {
        header('Location: filmy.php');
        exit();
    }

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $powrot = $_GET['powrot'] ?? 'filmy';

    if ($id > 0) {
        require_once "polaczenie.php";

        try {
            $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);

            if (!$polaczenie->connect_errno) {
                $polaczenie->set_charset("utf8mb4");

                $stmt = $polaczenie->prepare("UPDATE filmy SET czy_ulubiony = 1 - czy_ulubiony WHERE id = ?");

                if ($stmt) {
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $stmt->close();
                }

                $stmt = $polaczenie->prepare("SELECT czy_ulubiony FROM filmy WHERE id = ? LIMIT 1");

                if ($stmt) {
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $wynik = $stmt->get_result();

                    if ($wynik && $wynik->num_rows === 1) {
                        $czyUlubiony = (int) $wynik->fetch_assoc()['czy_ulubiony'];
                        $_SESSION['ulubione_film_komunikat'] = $czyUlubiony === 1
                            ? 'Film został dodany do polecanych na stronie głównej.'
                            : 'Film został usunięty z polecanych.';
                    }

                    $stmt->close();
                }

                $polaczenie->close();
            }
        } catch (Exception $e) {
            // Po błędzie wracamy bez komunikatu.
        }
    }

    if ($powrot === 'film') {
        header('Location: film.php?id=' . $id);
    } else {
        header('Location: filmy.php');
    }

    exit();
