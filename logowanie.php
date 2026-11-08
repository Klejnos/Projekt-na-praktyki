<?php
    session_start();
    if (isset($_SESSION['zalogowany']) && $_SESSION['zalogowany'] == true) {
        header('Location: index.html');
        exit();
    }
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie</title>
</head>
<body>
    <form action="zaloguj.php" method="post">
        <label for="nazwa">Nazwa:</label><br>
        <input type="text" name="nazwa">
        <label for="haslo">Hasło:</label><br>
        <input type="password" name="haslo">
        <input type="submit" value="Zaloguj się">
    </form>
<?php
    if (isset($_SESSION['blad'])) echo $_SESSION['blad'];
?>
</body>
</html>