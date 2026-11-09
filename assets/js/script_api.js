const API_URL = "api_film.php";

const tytul = document.getElementById("tytul");
const podpowiedzi = document.getElementById("podpowiedzi");

let opoznienie;

// Bezpieczne wypisywanie tekstu w HTML
function escapeHtml(tekst) {
    const div = document.createElement("div");
    div.textContent = tekst ?? "";
    return div.innerHTML;
}

// Ustawianie wartości pola, jeśli istnieje
function ustawWartosc(id, wartosc) {
    const element = document.getElementById(id);

    if (element) {
        element.value = wartosc ?? "";
    }
}

// Wyszukiwanie filmów podczas wpisywania
tytul.addEventListener("input", function () {

    clearTimeout(opoznienie);

    let nazwa = tytul.value.trim();

    podpowiedzi.innerHTML = "";

    // Ukrywanie poprzedniej okładki
    document.getElementById("okladka").style.display = "none";

    // Czyszczenie danych z poprzednio wybranego filmu
    ustawWartosc("okladka_url", "");
    ustawWartosc("tytul_oryginalny", "");
    ustawWartosc("tmdb_id", "");
    ustawWartosc("imdb_id", "");

    if (nazwa.length < 2) {
        return;
    }

    opoznienie = setTimeout(async function () {

        try {

            let odpowiedz = await fetch(
                `${API_URL}?action=search&q=${encodeURIComponent(nazwa)}`
            );

            let dane = await odpowiedz.json();

            podpowiedzi.innerHTML = "";

            if (!dane.ok || !dane.results || dane.results.length === 0) {
                return;
            }

            dane.results.forEach(function (film) {

                let element = document.createElement("div");

                element.className = "podpowiedz";

                let obraz = film.okladka || "";

                let oryginalny = (film.tytul_oryginalny && film.tytul_oryginalny !== film.tytul)
                    ? `<p>${escapeHtml(film.tytul_oryginalny)}</p>`
                    : "";

                element.innerHTML = `
                    <img src="${escapeHtml(obraz)}" alt="Plakat"
                     onerror="this.style.display='none'">

                 <div>
                <strong>${escapeHtml(film.tytul)}</strong>
                ${oryginalny}
                <p>${escapeHtml(film.rok)}</p>
    </div>
`;

                element.addEventListener("click", function () {
                    pobierzFilm(film.tmdb_id);
                });

                podpowiedzi.appendChild(element);

            });

        } catch (blad) {
            console.log("Błąd API:", blad);
        }

    }, 400);

});


// Pobieranie informacji o wybranym filmie
async function pobierzFilm(id) {

    try {

        let odpowiedz = await fetch(
            `${API_URL}?action=details&id=${encodeURIComponent(id)}`
        );

        let dane = await odpowiedz.json();

        if (!dane.ok || !dane.film) {
            alert("Nie udało się pobrać szczegółów filmu!");
            return;
        }

        let film = dane.film;

        // Uzupełnianie formularza

        ustawWartosc("tytul", film.tytul);
        ustawWartosc("tytul_oryginalny", film.tytul_oryginalny);
        ustawWartosc("tmdb_id", film.tmdb_id);
        ustawWartosc("imdb_id", film.imdb_id);
        ustawWartosc("gatunek", film.gatunek);
        ustawWartosc("rok", film.rok);
        ustawWartosc("ocena", film.ocena);
        ustawWartosc("rezyser", film.rezyser);
        ustawWartosc("czas_trwania", film.czas_trwania);
        ustawWartosc("jezyk", film.jezyk);
        ustawWartosc("produkcja", film.produkcja);
        ustawWartosc("nagrody", film.nagrody);
        ustawWartosc("opis", film.opis);
        ustawWartosc("budzet", film.budzet);

        // Pola do ręcznego uzupełnienia

        ustawWartosc("ilosc_dostepnych", "");
        ustawWartosc("cena", "");

        // Wyświetlanie okładki filmu

        let okladka = document.getElementById("okladka");

        let okladkaUrl = film.okladka || "";

        ustawWartosc("okladka_url", okladkaUrl);

        if (okladkaUrl) {

            okladka.src = okladkaUrl;
            okladka.style.display = "block";

        } else {

            okladka.src = "";
            okladka.style.display = "none";

        }

        // Usuwanie podpowiedzi

        podpowiedzi.innerHTML = "";

    } catch (blad) {

        console.log("Błąd:", blad);
        alert("Problem z połączeniem z API!");

    }

}
