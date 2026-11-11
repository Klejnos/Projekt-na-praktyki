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
                $albumId = null;

                $stmt = $polaczenie->prepare("SELECT album_id FROM muzyka WHERE id = ? LIMIT 1");

                if ($stmt) {
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $wynik = $stmt->get_result();

                    if ($wynik && $wynik->num_rows === 1) {
                        $albumId = $wynik->fetch_assoc()['album_id'];
                    }

                    $stmt->close();
                }

                $stmt = $polaczenie->prepare("DELETE FROM muzyka WHERE id = ?");

                if ($stmt) {
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $stmt->close();
                }

                if ($albumId !== null) {
                    $stmt = $polaczenie->prepare("SELECT COUNT(*) AS ile FROM muzyka WHERE album_id = ?");

                    if ($stmt) {
                        $stmt->bind_param("i", $albumId);
                        $stmt->execute();
                        $wynik = $stmt->get_result();
                        $ile = $wynik ? (int) $wynik->fetch_assoc()['ile'] : 0;
                        $stmt->close();

                        if ($ile === 0) {
                            $stmt = $polaczenie->prepare("DELETE FROM albumy WHERE id = ?");

                            if ($stmt) {
                                $stmt->bind_param("i", $albumId);
                                $stmt->execute();
                                $stmt->close();
                            }
                        }
                    }
                }

                $polaczenie->close();
                $_SESSION['usunieto_muzyke'] = true;
            }
        } catch (Exception $e) {
            // Po błędzie wracamy na listę bez komunikatu.
        }
    }

    header('Location: muzyka.php');
    exit();
