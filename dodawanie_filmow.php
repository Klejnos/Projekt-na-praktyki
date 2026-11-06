<?php
$laczenie = new mysqli("localhost", "root", "", "wypozyczalnia");
if ($laczenie->connect_error) {
    die("Błąd połączenia: " . $laczenie->connect_error);
}

$tytyl = $_POST['tytul'];
$gatunek = $_POST['gatunek'];
$rok_wydania = $_POST['rok_wydania'];
$ocena = $_POST['ocena'];
$rezyser = $_POST['rezyser'];
$czas_trwania = $_POST['czas_trwania'];
$ilosc_dostepnych = $_POST['ilosc_dostepnych'];
$budzet = $_POST['budzet'];
$produkcja = $_POST['produkcja'];
$nagrody = $_POST['nagrody'];
$opis = $_POST['opis'];
$cena = $_POST['cena'];

$sql = "INSERT INTO filmy (tytul, gatunek, rok_wydania, ocena, rezyser, czas_trwania, ilosc_dostepnych, budzet, produkcja, nagrody, opis, cena) VALUES ('$tytyl', '$gatunek', '$rok_wydania', '$ocena', '$rezyser', '$czas_trwania', '$ilosc_dostepnych', '$budzet', '$produkcja', '$nagrody', '$opis', '$cena')";
$stmt = $laczenie->prepare($sql);
$stmt->bind_param("ssisssisssss", $tytyl, $gatunek, $rok_wydania, $ocena, $rezyser, $czas_trwania, $ilosc_dostepnych, $budzet, $produkcja, $nagrody, $opis, $cena);

if ($stmt->execute()) {
    echo "Film dodany";
} else {
    echo "Błąd: " . $stmt->error;
}

$stmt->close();
$laczenie->close();
?>