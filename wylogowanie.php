<?php
// wylogowanie.php — kończy sesję i przenosi na stronę logowania.


    session_start();

    // Usunięcie danych sesji i powrót na ekran logowania.
    session_unset();
    header('Location: logowanie.php');
?>