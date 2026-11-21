<?php
// ulubione_utwor.php — dodanie/usunięcie utworu z ulubionych (tylko administrator).


    require_once "czy_zalogowany.php";

    // Ulubione może zmieniać tylko administrator.
    if (!isset($_SESSION['czy_admin']) || (int) $_SESSION['czy_admin'] !== 1) {
        header('Location: utwory.php');
        exit();
    }

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $powrot = $_GET['powrot'] ?? 'utwory';

    if ($id > 0) {
        require_once "polaczenie.php";

        try {
            $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);

            if (!$polaczenie->connect_errno) {
                $polaczenie->set_charset("utf8mb4");

                // Przełączenie stanu ulubionego (0 <-> 1).
                $stmt = $polaczenie->prepare("UPDATE utwory SET czy_ulubiony = 1 - czy_ulubiony WHERE id = ?");

                if ($stmt) {
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $stmt->close();
                }

                // Odczyt nowego stanu i komunikat dla użytkownika.
                $stmt = $polaczenie->prepare("SELECT czy_ulubiony FROM utwory WHERE id = ? LIMIT 1");

                if ($stmt) {
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $wynik = $stmt->get_result();

                    if ($wynik && $wynik->num_rows === 1) {
                        $czyUlubiony = (int) $wynik->fetch_assoc()['czy_ulubiony'];
                        $_SESSION['ulubione_komunikat'] = $czyUlubiony === 1
                            ? 'Utwór został dodany do polecanych na stronie głównej.'
                            : 'Utwór został usunięty z polecanych.';
                    }

                    $stmt->close();
                }

                $polaczenie->close();
            }
        } catch (Exception $e) {
            // Po błędzie wracamy bez komunikatu.
        }
    }

    // Powrót na stronę, z której przyszło kliknięcie.
    if ($powrot === 'utwor') {
        header('Location: utwor.php?id=' . $id);
    } else {
        header('Location: utwory.php');
    }

    exit();
?>