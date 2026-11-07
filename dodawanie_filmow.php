<?php
// Konfiguracja połączenia z bazą danych
$host = 'localhost';
$db   = 'database';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);// Nawiązanie połączenia z bazą
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


    if ($_SERVER["REQUEST_METHOD"] == "POST") {// Sprawdzenie, czy formularz został wysłany metodą POST
        
        // Pobranie i zabezpieczenie danych z formularza
        $tytul = $_POST['tytul'] ?? '';
        $gatunek = $_POST['gatunek'] ?? '';
        $rok = $_POST['rok'] !== '' ? $_POST['rok'] : null;
        $ocena = $_POST['ocena'] !== '' ? $_POST['ocena'] : null;
        $rezyser = $_POST['rezyser'] ?? '';
        $czas_trwania = $_POST['czas_trwania'] !== '' ? $_POST['czas_trwania'] : null;
        $jezyk = $_POST['jezyk'] ?? '';
        $ilosc_dostepnych = $_POST['ilosc_dostepnych'] !== '' ? $_POST['ilosc_dostepnych'] : null;
        $budzet = $_POST['budzet'] !== '' ? $_POST['budzet'] : null;
        $produkcja = $_POST['produkcja'] ?? '';
        $nagrody = $_POST['nagrody'] ?? '';
        $cena = $_POST['cena'] !== '' ? $_POST['cena'] : null;
        $opis = $_POST['opis'] ?? '';

        $sql = "INSERT INTO filmy (tytul, gatunek, rok, ocena, rezyser, czas_trwania, jezyk, ilosc_dostepnych, budzet, produkcja, nagrody, cena, opis) 
                VALUES (:tytul, :gatunek, :rok, :ocena, :rezyser, :czas_trwania, :jezyk, :ilosc_dostepnych, :budzet, :produkcja, :nagrody, :cena, :opis)";
        
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            'tytul' => $tytul,
            'gatunek' => $gatunek,
            'rok' => $rok,
            'ocena' => $ocena,
            'rezyser' => $rezyser,
            'czas_trwania' => $czas_trwania,
            'jezyk' => $jezyk,
            'ilosc_dostepnych' => $ilosc_dostepnych,
            'budzet' => $budzet,
            'produkcja' => $produkcja,
            'nagrody' => $nagrody,
            'cena' => $cena,
            'opis' => $opis
        ]);
// Wyświetlenie komunikatu o sukcesie
        echo "<!DOCTYPE html>
        <html lang='pl'>
        <head>
            <meta charset='UTF-8'>
            <title>Sukces</title>
            <link rel='stylesheet' href='style.css'>
        </head>
        <body>
            <main>
                <h2>Film został pomyślnie dodany do bazy danych!</h2>
                <a href='index.html'>Powrót do strony głównej</a>
            </main>
        </body>
        </html>";
    }

}
?>