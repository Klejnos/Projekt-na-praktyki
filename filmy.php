<?php
    session_start();
    if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] == false) {
        header('Location: logowanie.php');
        exit();
    }
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/style_filmy.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wypożyczalnia filmów DVD oraz muzyki CD</title>
</head>
<body>
    <?php
        if (isset($_SESSION['dodano_film']) && $_SESSION['dodano_film'] === true) {
            echo '<div class="sukces">Film został pomyślnie dodany do bazy danych.</div>';
            unset($_SESSION['dodano_film']);
        }
    ?>

    <header>
        <h1>Wypożyczalnia filmów DVD oraz muzyki CD<a class="przycisk" href="dodawanie_filmow.php">Dodaj film</a><h2 class="konto">Konto</h2></h1>
    </header>
    <nav>
            <a href="index.php">Strona główna</a> 
            <a href="filmy.php">Filmy</a>
            <a href="muzyka.php">Muzyka</a>
            <a href="kontakt.php">Kontakt</a>
    </nav>
    <main>
        <p class="srodek"><span>Filmy dostępne w naszej wypożyczalni:</span>
        <select id="sortowanie">
            <option value="default">Sortuj według</option>
            <option value="data-dodania">Data dodania</option>
            <option value="cena-rosnaco">Cena: od najniższej</option>
            <option value="cena-malejaco">Cena: od najwyższej</option>
            <option value="nazwa">Nazwa: A-Z</option>
        </select>
        </p>

        <p class="biblioteka">
            <span>Biblioteka filmów:</span>
        </p>

        <?php
            require_once "polaczenie.php";

            $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);

            if (!$polaczenie->connect_errno) {
                $polaczenie->set_charset("utf8mb4");

                $wynik = $polaczenie->query(
                    "SELECT id, tytul, tytul_oryginalny, gatunek, rok, ocena, cena, okladka FROM filmy ORDER BY id DESC"
                );

                if ($wynik) {
                    if ($wynik->num_rows === 0) {
                        echo '<p class="biblioteka"><span>Brak filmów w bazie danych.</span></p>';
                    } else {
                        echo '<div class="film-grid">';

                        while ($film = $wynik->fetch_assoc()) {
                            echo '<a class="film-card" href="film.php?id=' . (int) $film['id'] . '">';

                            if (!empty($film['okladka'])) {
                                echo '<img class="film-cover" src="' . htmlspecialchars($film['okladka']) . '" alt="Okładka filmu ' . htmlspecialchars($film['tytul']) . '">';
                            } else {
                                echo '<div class="film-cover film-cover--empty"><span>Brak okładki</span></div>';
                            }

                            echo '<div class="film-info">';
                            echo '<b>' . htmlspecialchars($film['tytul']) . '</b>';

                            if (!empty($film['tytul_oryginalny']) && $film['tytul_oryginalny'] !== $film['tytul']) {
                                echo '<span class="film-original">' . htmlspecialchars($film['tytul_oryginalny']) . '</span>';
                            }

                            $podtytul = [];

                            if ($film['gatunek']) {
                                $podtytul[] = htmlspecialchars($film['gatunek']);
                            }

                            if ($film['rok']) {
                                $podtytul[] = (int) $film['rok'];
                            }

                            if ($podtytul) {
                                echo '<span>' . implode(' · ', $podtytul) . '</span>';
                            }

                            $szczegoly = [];

                            if ($film['ocena'] !== null) {
                                $szczegoly[] = 'Ocena: ' . number_format((float) $film['ocena'], 1, ',', '');
                            }

                            if ($film['cena'] !== null) {
                                $szczegoly[] = 'Cena: ' . number_format((float) $film['cena'], 2, ',', ' ') . ' zł';
                            }

                            if ($szczegoly) {
                                echo '<span>' . implode(' | ', $szczegoly) . '</span>';
                            }

                            echo '</div>';
                            echo '</a>';
                        }

                        echo '</div>';
                    }

                    $wynik->free();
                }

                $polaczenie->close();
            }
        ?>
    </main>

    
    <footer>
        <p>Autorzy: Przemysław Gałęziowski i Filip Klein</p>
        <script src="assets/js/script.js"></script>
</body>
</html>
