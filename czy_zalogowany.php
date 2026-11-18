<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] == false) {
        header('Location: logowanie.php');
        exit();
    }

    require_once "polaczenie.php";

    $uzytkownik_id = $_SESSION['id'];
    $sprawdz_status = $polaczenie -> prepare("SELECT czy_potwierdzony FROM uzytkownicy WHERE id = ?");
    $sprawdz_status -> bind_param("i", $uzytkownik_id);
    $sprawdz_status -> execute();
    $wynik = $sprawdz_status -> get_result() -> fetch_assoc();

    if (!$wynik || $wynik['czy_potwierdzony'] == 0) {
        $sprawdz_status -> close();
        $polaczenie -> close();
        session_unset();

        $_SESSION['blad'] = "Musisz aktywować konto poprzez link z maila";
        header('Location: logowanie.php');
        exit();
    }  
?>