const API_URL = "api_muzyka.php";

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

// Wyszukiwanie albumów podczas wpisywania
tytul.addEventListener("input", function () {

    clearTimeout(opoznienie);

    let nazwa = tytul.value.trim();

    podpowiedzi.innerHTML = "";

    // Ukrywanie poprzedniej okładki
    document.getElementById("okladka").style.display = "none";

    // Czyszczenie danych z poprzednio wybranego albumu
    ustawWartosc("okladka_url", "");
    ustawWartosc("discogs_id", "");
    ustawWartosc("discogs_uri", "");

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

            dane.results.forEach(function (album) {

                let element = document.createElement("div");

                element.className = "podpowiedz";

                let obraz = album.okladka || "";

                element.innerHTML = `
                    <img src="${escapeHtml(obraz)}" alt="Okładka"
                     onerror="this.style.display='none'">

                 <div>
                <strong>${escapeHtml(album.tytul)}</strong>
                <p>${escapeHtml(album.rok)}${album.format ? ' • ' + escapeHtml(album.format) : ''}</p>
    </div>
`;

                element.addEventListener("click", function () {
                    pobierzAlbum(album.discogs_id);
                });

                podpowiedzi.appendChild(element);

            });

        } catch (blad) {
            console.log("Błąd API:", blad);
        }

    }, 400);

});


// Pobieranie informacji o wybranym albumie
async function pobierzAlbum(id) {

    try {

        let odpowiedz = await fetch(
            `${API_URL}?action=details&id=${encodeURIComponent(id)}`
        );

        let dane = await odpowiedz.json();

        if (!dane.ok || !dane.album) {
            alert("Nie udało się pobrać szczegółów albumu!");
            return;
        }

        let album = dane.album;

        // Uzupełnianie formularza

        ustawWartosc("tytul", album.tytul);
        ustawWartosc("wykonawca", album.wykonawca);
        ustawWartosc("rok", album.rok);
        ustawWartosc("gatunek", album.gatunek);
        ustawWartosc("discogs_id", album.discogs_id);
        ustawWartosc("discogs_uri", album.discogs_uri);

        // Pola do ręcznego uzupełnienia

        ustawWartosc("ilosc_dostepnych", "");
        ustawWartosc("cena", "");

        // Automatyczny wybór typu płyty, jeśli Discogs podaje CD

        let typ = document.getElementById("typ_plyty");

        if (typ && /CD/i.test(album.format || "")) {
            for (let i = 0; i < typ.options.length; i++) {
                if (typ.options[i].text.trim().toUpperCase() === "CD") {
                    typ.selectedIndex = i;
                    break;
                }
            }
        }

        // Wyświetlanie okładki albumu

        let okladka = document.getElementById("okladka");

        let okladkaUrl = album.okladka || "";

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
