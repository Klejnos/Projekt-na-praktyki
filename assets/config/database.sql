CREATE TABLE uzytkownicy (
	id INT AUTO_INCREMENT PRIMARY KEY,
	nazwa VARCHAR(20) NOT NULL UNIQUE,
	haslo VARCHAR(100) NOT NULL,
	czy_admin TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE typy_plyt (
	id INT AUTO_INCREMENT PRIMARY KEY,
	nazwa VARCHAR(30) NOT NULL UNIQUE
);

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
    opis TEXT,
    okladka VARCHAR(500),
	typ_plyty_id INT,

	FOREIGN KEY (typ_plyty_id) REFERENCES typy_plyt(id) 
	ON DELETE SET NULL -- w przypadku usunięcia typu płyty, nie usunie filmu
	ON UPDATE CASCADE -- przy zamianie id wszystkie rekordy zostaną zakutalizowane
);

-- nie jestem pewny czy ta tabela jest skończona
CREATE TABLE albumy (
	id INT AUTO_INCREMENT PRIMARY KEY,
	tytul VARCHAR(50) NOT NULL,
	typ_plyty_id INT,

	FOREIGN KEY (typ_plyty_id) REFERENCES typy_plyt(id)
	ON DELETE SET NULL
	ON UPDATE CASCADE
);

-- tabela do muzyki
CREATE TABLE muzyka (
	id INT AUTO_INCREMENT PRIMARY KEY,
	tytul VARCHAR(50) NOT NULL,
	gatunek VARCHAR(50),
	rok INT,
	ilosc_dostepnych INT,
	cena DECIMAL(6,2),
	album_id INT,

	FOREIGN KEY (album_id) REFERENCES albumy(id)
	ON DELETE SET NULL
	ON UPDATE CASCADE
);

CREATE TABLE wypozyczenia_filmy (
	uzytkownicy_id INT NOT NULL,
	film_id INT NOT NULL,
	data_wypozyczenia DATETIME NOT NULL,
	data_zwrotu DATETIME,

	PRIMARY KEY (uzytkownicy_id, film_id),
	FOREIGN KEY (uzytkownicy_id) REFERENCES uzytkownicy(id) 
	ON DELETE CASCADE -- w przypadku usunięcia użytkownika, usunie równierz jego wypożyczenia
	ON UPDATE CASCADE,
	FOREIGN KEY (film_id) REFERENCES filmy(id) 
	ON DELETE CASCADE -- w przypadku usunięcia filmu, usuwa wypozyczenia, których się znalazł
	ON UPDATE CASCADE
);

CREATE TABLE wypozyczenia_muzyka (
	uzytkownicy_id INT NOT NULL,
	muzyka_id INT NOT NULL,
	data_wypozyczenia DATETIME NOT NULL,
	data_zwrotu DATETIME,

	PRIMARY KEY (uzytkownicy_id, muzyka_id),
	FOREIGN KEY (uzytkownicy_id) REFERENCES uzytkownicy(id)
	ON DELETE CASCADE
	ON UPDATE CASCADE,
	FOREIGN KEY (muzyka_id) REFERENCES muzyka(id)
	ON DELETE CASCADE
	ON UPDATE CASCADE
);

INSERT INTO typy_plyt (nazwa) VALUES ('CD'), ('DVD'), ('Blu-ray'), ('VHS');