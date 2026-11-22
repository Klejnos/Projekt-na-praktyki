// Obsługa formularza dodawania albumu:
// - podpowiedzi albumów z Discogs podczas wpisywania,
// - uzupełnianie pól po wybraniu podpowiedzi,
// - ręczny podgląd okładki wpisanej w polu URL.

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

// Wyświetlanie lub ukrywanie okładki.
function pokazOkladke(url) {
    const obraz = document.getElementById("okladka");

    if (!obraz) {
        return;
    }

    if (url) {
        obraz.src = url;
        obraz.style.display = "block";
    } else {
        obraz.src = "";
        obraz.style.display = "none";
    }
}

// Ręczne wpisanie adresu okładki od razu pokazuje podgląd.
const poleOkladki = document.getElementById("okladka_url");

if (poleOkladki) {
    poleOkladki.addEventListener("input", function () {
        pokazOkladke(poleOkladki.value.trim());
    });
}

// Wyszukiwanie albumów podczas wpisywania
tytul.addEventListener("input", function () {

    clearTimeout(opoznienie);

    let nazwa = tytul.value.trim();

    podpowiedzi.innerHTML = "";

    // Ukrywanie poprzedniej okładki
    pokazOkladke("");

    // Czyszczenie danych z poprzednio wybranego albumu
    ustawWartosc("okladka_url", "");
    ustawWartosc("discogs_id", "");
    ustawWartosc("discogs_uri", "");
    ustawWartosc("utwory_json", "[]");

    if (nazwa.length < 2) {
        return;
    }

    opoznienie = setTimeout(async function () {

        // Debounce: zapytanie leci dopiero 400 ms po ostatnim znaku.
        try {

            let odpowiedz = await fetch(
                `${API_URL}?action=search&q=${encodeURIComponent(nazwa)}`
            );

            let dane = await odpowiedz.json();

            podpowiedzi.innerHTML = "";

            if (!dane.ok) {
                podpowiedzi.innerHTML = '<p class="podpowiedz-info">Discogs chwilowo nie odpowiada. Spróbuj ponownie za chwilę.</p>';
                return;
            }

            if (!dane.results || dane.results.length === 0) {
                return;
            }

            // Renderowanie pojedynczej podpowiedzi: okładka albo placeholder.
            dane.results.forEach(function (album) {

                let element = document.createElement("div");

                element.className = "podpowiedz";

                let obraz = album.okladka || "";
                let obrazHtml = obraz
                    ? `<img src="${escapeHtml(obraz)}" alt="Okładka" onerror="this.style.display='none'">`
                    : '<span class="podpowiedz-brak-okladki" aria-hidden="true">♫</span>';

                element.innerHTML = `
                    ${obrazHtml}

                 <div>
                <strong>${escapeHtml(album.tytul)}</strong>
                <p>${escapeHtml(album.rok)}${album.format ? ' • ' + escapeHtml(album.format) : ''}</p>
    </div>
`;

                element.addEventListener("click", function () {
                    pobierzAlbum(album);
                });

                podpowiedzi.appendChild(element);

            });

        } catch (blad) {
            console.log("Błąd API:", blad);
        }

    }, 400);

});


// Pobieranie informacji o wybranym albumie.
// Najpierw próbujemy pełnych szczegółów, a przy błędzie korzystamy z danych wyszukiwania.
async function pobierzAlbum(albumWyniku) {

    let album = null;

    try {

        let odpowiedz = await fetch(
            `${API_URL}?action=details&id=${encodeURIComponent(albumWyniku.discogs_id)}`
        );

        let dane = await odpowiedz.json();

        if (dane.ok && dane.album) {
            album = dane.album;
        } else {
            console.warn("Discogs nie zwrócił szczegółów albumu:", dane.error || "nieznany błąd");
        }

    } catch (blad) {

        console.log("Błąd szczegółów albumu:", blad);

    }

    // Gdy Discogs jest chwilowo niedostępny (np. limit)
    // korzystamy z danych, które przyszły już w wyszukiwaniu.
    let tytulZapasowy = albumWyniku.tytul || "";
    let czesciTytulu = tytulZapasowy.split(" - ");
    let wykonawcaZapasowy = czesciTytulu.length > 1 ? czesciTytulu.shift() : "";
    let albumZapasowy = czesciTytulu.length ? czesciTytulu.join(" - ") : tytulZapasowy;

    let daneAlbumu = album || {
        tytul: albumZapasowy,
        wykonawca: wykonawcaZapasowy,
        rok: albumWyniku.rok,
        gatunek: albumWyniku.gatunek,
        format: albumWyniku.format,
        discogs_id: albumWyniku.discogs_id,
        discogs_uri: albumWyniku.discogs_uri,
        okladka: albumWyniku.okladka
    };

    // Uzupełnianie formularza

    ustawWartosc("tytul", daneAlbumu.tytul);
    ustawWartosc("wykonawca", daneAlbumu.wykonawca);
    ustawWartosc("rok", daneAlbumu.rok > 0 ? daneAlbumu.rok : "");
    ustawWartosc("gatunek", daneAlbumu.gatunek);
    ustawWartosc("discogs_id", daneAlbumu.discogs_id);
    ustawWartosc("discogs_uri", daneAlbumu.discogs_uri);
    ustawWartosc("utwory_json", JSON.stringify(album ? (album.utwory || []) : []));

    // Pola do ręcznego uzupełnienia

    ustawWartosc("ilosc_dostepnych", "");
    ustawWartosc("cena", "");

    // Automatyczny wybór typu płyty, jeśli Discogs podaje CD

    let typ = document.getElementById("typ_plyty");

    if (typ && /CD/i.test(daneAlbumu.format || "")) {
        for (let i = 0; i < typ.options.length; i++) {
            if (typ.options[i].text.trim().toUpperCase() === "CD") {
                typ.selectedIndex = i;
                break;
            }
        }
    }

    // Wyświetlanie okładki albumu
    let okladkaUrl = daneAlbumu.okladka || "";

    ustawWartosc("okladka_url", okladkaUrl);
    pokazOkladke(okladkaUrl);

    // Usuwanie podpowiedzi
    if (album) {
        podpowiedzi.innerHTML = "";
    } else {
        podpowiedzi.innerHTML = '<p class="podpowiedz-info">Uzupełniono podstawowe dane. Discogs nie zwrócił pełnej listy utworów - spróbuj ponownie później.</p>';
    }

}