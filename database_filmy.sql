CREATE DATABASE wypozyczalnia;
USE wypozyczalnia;

CREATE TABLE filmy (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tytul VARCHAR(100),
    gatunek VARCHAR(50),
    rok INT,
    ocena varchar(10),
    rezyser VARCHAR(100),
    czas_trwania INT,
    ilosc_dostepnych INT,
    budzet DECIMAL(10,2),
    produkcja VARCHAR(100),
    nagrody VARCHAR(255),
    opis TEXT,
    cena DECIMAL(6,2)
);