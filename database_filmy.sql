CREATE TABLE filmy (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tytul VARCHAR(150) NOT NULL,
    gatunek VARCHAR(50),
    rok INT,
    ocena DECIMAL(3,1),
    rezyser VARCHAR(100),
    czas_trwania INT,
    jezyk VARCHAR(50),
    ilosc_dostepnych INT,
    budzet DECIMAL(12,2),
    produkcja VARCHAR(100),
    nagrody TEXT,
    cena DECIMAL(6,2),
    opis TEXT
);