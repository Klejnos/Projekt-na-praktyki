<?php
    require_once "czy_zalogowany.php";
    if (!isset($_SESSION['czy_admin']) || (int) $_SESSION['czy_admin'] !== 1) {
        header('Location: index.php');
        exit();
    }

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($id > 0) {
        require_once "polaczenie.php";

        try {
            $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);

            if ($polaczenie->connect_errno) {
                throw new Exception("Błąd połączenia z bazą danych.");
            }

            $polaczenie->set_charset("utf8mb4");

            $stmt = $polaczenie->prepare("DELETE FROM utwory WHERE id = ?");

            if (!$stmt) {
                throw new Exception("Błąd przygotowania zapytania usuwania.");
            }

            $stmt -> bind_param("i", $id);
            $stmt -> execute();
            $usunieto = $stmt -> affected_rows > 0;
            $stmt -> close();

            $polaczenie->close();

            if ($usunieto) {
                $_SESSION['usunieto_utwor'] = true;
            }
        } catch (Exception $e) {
            if (isset($polaczenie) && $polaczenie instanceof mysqli && !$polaczenie -> connect_errno) {
                $polaczenie->close();
            }
        }
    }

    header('Location: utwory.php');
    exit();
?>