<?php
    session_start();

    if (isset($_SESSION['zalogowany']) && $_SESSION['zalogowany'] == true) {
        header('Location: index.php');
        exit();
    }
    
    if (isset($_POST['nazwa'])) {
        $ok = true;
        $nazwa = $_POST['nazwa'];
        $nazwa = htmlentities($nazwa, ENT_QUOTES, "UTF-8");

        if ((strlen($nazwa) < 3) || (strlen($nazwa) > 20)) {
            $ok = false;
            $_SESSION['e_nazwa'] = "Nazwa musi posiadać od 3 do 20 znaków";
        }

        if (ctype_alnum($nazwa) == false) {
            $ok = false;
            $_SESSION['e_nazwa'] = "Nazwa może składać się tylko z liter i cyfr (bez polskich znaków)";
        }

        $email = $_POST['email'];
        $email2 = filter_var($email, FILTER_SANITIZE_EMAIL);
        if ((filter_var($email2, FILTER_VALIDATE_EMAIL) == false) || ($email2 != $email)) {
            $ok = false;
            $_SESSION['e_email'] = "Podaj poprawny adres e-mail";
        }

        $haslo1 = $_POST['haslo1'] ?? ''; 
        $haslo2 = $_POST['haslo2'] ?? '';
        $haslo1 = htmlentities($haslo1, ENT_QUOTES, "UTF-8");
        $haslo2 = htmlentities($haslo2, ENT_QUOTES, "UTF-8");

        if ((strlen($haslo1) < 8) || (strlen($haslo1) > 20)) {
            $ok = false;
            $_SESSION['e_haslo'] = "Hasło musi posiadać od 8 do 20 znaków";
        }

        if ($haslo1 != $haslo2) {
            $ok = false;
            $_SESSION['e_haslo'] = "Hasła muszą być identyczne";
        }

        $haslo_hash = password_hash($haslo1, PASSWORD_DEFAULT);

        require_once "assets/config/rejestracja_config.php";

        $czy_admin = 0;
        if (!empty($_POST['klucz_admin'])) {
            if ($_POST['klucz_admin'] == $klucz_admin) {
                $czy_admin = 1;
            } else {
                $ok = false;
                $_SESSION['e_czy_admin'] = "Zły kod administratora";
            }
        }

        require_once "polaczenie.php";

        try {
            $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
            if ($polaczenie->connect_errno) {
                throw new Exception(mysqli_connect_errno());
            }

            $q_sprawdzenie = $polaczenie -> prepare("SELECT nazwa, email FROM uzytkownicy WHERE nazwa = ? OR email = ?");
            $q_sprawdzenie -> bind_param("ss", $nazwa, $email2);
            $q_sprawdzenie -> execute();
            $result = $q_sprawdzenie -> get_result();

            while ($wiersz = $result -> fetch_assoc()) {
                if ($wiersz['nazwa'] === $nazwa) {
                    $ok = false;
                    $_SESSION['e_nazwa'] = "Istnieje już konto z taką nazwą";
                }
                if ($wiersz['email'] === $email2) {
                    $ok = false;
                    $_SESSION['e_email'] = "Ten adres email jest już przypisany do jakiegoś konta";
                }
            }
            $q_sprawdzenie -> close();

            if ($ok) {
                $token = bin2hex(random_bytes(32));
                $stmt_insert = $polaczenie -> prepare("INSERT INTO uzytkownicy (nazwa, email, haslo, czy_admin, token, czy_potwierdzony) VALUES (?, ?, ?, ?, ?, 0)");
                $stmt_insert -> bind_param("sssis", $nazwa, $email2, $haslo_hash, $czy_admin, $token);
                
                if ($stmt_insert->execute()) {
                    require_once __DIR__.'/assets/config/PHPMailer/Exception.php';
                    require_once __DIR__.'/assets/config/PHPMailer/PHPMailer.php';
                    require_once __DIR__.'/assets/config/PHPMailer/SMTP.php';
                    require_once "assets/config/mail.php";
                    $email_uzytkownika = $email2;
                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

                    try {
                        $mail -> isSMTP();
                        $mail -> Host = $mail_host;
                        $mail -> SMTPAuth = true;
                        $mail -> Username = $mail_user;
                        $mail -> Password = $mail_pass;
                        $mail -> SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                        $mail -> Port = $mail_port;
                        $mail->CharSet = 'UTF-8';
                        $mail->Encoding = 'base64';
                        $mail->isHTML(true);
                        $mail->ContentType = 'text/html';

                        $mail -> setFrom($mail_user, 'Wypożyczalnia filmów i muzyki');
                        $mail -> addAddress($email_uzytkownika, $nazwa);
                        $link_aktywacyjny = $base_url."witam.php?token=".$token;

                        $mail -> isHTML(true);
                        $mail -> Subject = 'Potwierdzenie założenia konta';
                        $mail -> Body = "Dzień dobry ".htmlspecialchars($nazwa, ENT_QUOTES, 'UTF-8').",<br>Dziękujemy za założenie konta. 
                        Aby aktywować konto, kliknij w poniższy link:<br><a href='".htmlspecialchars($link_aktywacyjny, ENT_QUOTES, 'UTF-8')."'>Aktywuj konto</a><br><br>Życzymy miłego dnia.";
                        $mail -> send();

                        $_SESSION['udana_rejestracja'] = true;
                    }
                    catch (Exception $e) { }
                    $stmt_insert -> close();
                    $polaczenie -> close();
                    header('Location: witam.php');
                    exit();
                } 

                else {
                    throw new Exception($polaczenie -> error);
                }

            } 
            else {
                $polaczenie -> close();
                header('Location: rejestracja.php');
                exit();
            }

        } catch (Exception $e) {
            $_SESSION['e_serwer'] = "Błąd serwera. Spróbuj później.";
            header('Location: rejestracja.php');
            exit();
        }
    }
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejestracja</title>
    <link rel="stylesheet" href="assets/css/rejestracja.css">
    <link rel="icon" type="image/x-icon" href="assets/img/icon.ico">
</head>
<body>
    <header>
        <div class="header-top">
            <a href="index.php" class="brand">
                <img src="assets/img/icon.ico" alt="plyta">
                <div class="brand-text">
                    <strong>Wypożyczalnia</strong>
                    <small>Filmów i muzyki</small>
                </div>
            </a>
            <div class="header-actions">
                <a href="konto.php" class="top-btn">Konto</a>
            </div>
        </div>
    </header>

    <nav>
        <a href="index.php">Strona główna</a> 
        <a href="filmy.php">Filmy</a>
        <a href="muzyka.php">Muzyka</a>
        <a href="utwory.php">Utwory</a>
        <a href="kontakt.php">Kontakt</a>
    </nav>

    <main>
        <form method="post">
            <h2>Zarejestruj się</h2>
            
            <?php
                if (isset($_SESSION['e_serwer'])) {
                    echo '<div class="error">'.$_SESSION['e_serwer']."</div>";
                    unset($_SESSION['e_serwer']);
                }
            ?>

            <label for="nazwa">Nazwa użytkownika (Bez polskich znaków):</label>
            <input type="text" id="nazwa" name="nazwa">
            <?php
                if (isset($_SESSION['e_nazwa'])) {
                    echo '<div class="error">'.$_SESSION['e_nazwa']."</div>";
                    unset($_SESSION['e_nazwa']);
                }
            ?>

            <label for="email">Podaj swój adres e-mail:</label>
            <input type="text" id="email" name="email">
            <?php
                if (isset($_SESSION['e_email'])) {
                    echo '<div class="error">'.$_SESSION['e_email']."</div>";
                    unset($_SESSION['e_email']);
                }
            ?>

            <label for="haslo1">Hasło (8 - 20 znaków):</label>
            <input type="password" id="haslo1" name="haslo1">

            <label for="haslo2">Powtórz hasło:</label>
            <div class="password-container">
                <input type="password" id="haslo2" name="haslo2">
                <button type="button" id="przycisk_pokazHaslo">
                    <img id="img_pokaz_haslo" src="assets/img/lock.png" alt="Pokaż hasło" draggable="false">
                </button>
            </div>
            <?php
                if (isset($_SESSION['e_haslo'])) {
                    echo '<div class="error">'.$_SESSION['e_haslo']."</div>";
                    unset($_SESSION['e_haslo']);
                }
            ?>

            <label for="klucz_admin">Kod do zalogowania się jako administrator (opcjonalne):</label>
            <input type="text" id="klucz_admin" name="klucz_admin">
            <?php
                if (isset($_SESSION['e_czy_admin'])) {
                    echo '<div class="error">'.$_SESSION['e_czy_admin']."</div>";
                    unset($_SESSION['e_czy_admin']);
                }
            ?>

            <button type="submit">Zarejestruj się</button>
        </form>
        <script src="assets/js/pokaz_haslo.js"></script>
    </main>

    <footer>
        <p>2026 - Przemysław Gałęziowski oraz Filip Klein</p>
    </footer>
</body>
</html>