<?php
// usun_muzyka.php — usunięcie albumu muzycznego (tylko administrator).


    require_once "czy_zalogowany.php";

    // Usuwanie albumów jest dostępne tylko dla administratora.
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
                $albumId = null;

                // Zapamiętujemy powiązany album, żeby potem usunąć go, jeśli zostanie pusty.
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

                // Usunięcie wpisu muzyka.
                $stmt = $polaczenie->prepare("DELETE FROM muzyka WHERE id = ?");

                if ($stmt) {
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $stmt->close();
                }

                if ($albumId !== null) {
                    // Album usuwamy tylko wtedy, gdy nie ma już żadnych powiązanych wpisów.
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
                // Flaga komunikatu odczytywana przez muzyka.php.
                $_SESSION['usunieto_muzyke'] = true;
            }
        } catch (Exception $e) {
            // Po błędzie wracamy na listę bez komunikatu.
        }
    }

    header('Location: muzyka.php');
    exit();
?>