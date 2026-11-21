// Obsługa formularza dodawania utworu:
// - podpowiedzi tytułów utworów z Discogs oraz z lokalnej bazy,
// - uzupełnianie albumu, wykonawcy, roku i okładki,
// - zapamiętanie album_id, żeby utwór trafił do istniejącego albumu,
// - ręczny podgląd okładki wpisanej w polu URL.

const API_URL = "api_muzyka.php";

document.addEventListener("DOMContentLoaded", function () {
    const tytul = document.getElementById("tytul");
    const podpowiedzi = document.getElementById("podpowiedzi");

    if (!tytul || !podpowiedzi) {
        return;
    }

    let opoznienie;

    function escapeHtml(tekst) {
        const div = document.createElement("div");
        div.textContent = tekst ?? "";
        return div.innerHTML;
    }

    function ustawWartosc(id, wartosc) {
        const element = document.getElementById(id);

        if (element) {
            element.value = wartosc ?? "";
        }
    }

    function wyczyscPodpowiedzi() {
        podpowiedzi.innerHTML = "";
    }

    function pokazOkladke(url, ustawPole = true) {
        if (ustawPole) {
            ustawWartosc("okladka_url", url || "");
        }

        const okladka = document.getElementById("okladka");

        if (!okladka) {
            return;
        }

        if (url) {
            okladka.src = url;
            okladka.style.display = "block";
        } else {
            okladka.src = "";
            okladka.style.display = "none";
        }
    }

    const poleOkladki = document.getElementById("okladka_url");

    if (poleOkladki) {
        poleOkladki.addEventListener("input", function () {
            pokazOkladke(poleOkladki.value.trim(), false);
        });
    }

    tytul.addEventListener("input", function () {
        clearTimeout(opoznienie);

        const nazwa = tytul.value.trim();

        wyczyscPodpowiedzi();

        // Ręczna zmiana tytułu odłącza wcześniej wybrany album.
        ustawWartosc("album_id", "");
        ustawWartosc("discogs_id", "");
        ustawWartosc("discogs_uri", "");
        pokazOkladke("");

        if (nazwa.length < 2) {
            return;
        }

        opoznienie = setTimeout(async function () {

            //nie wysyłamy zapytania po każdym znaku.
            try {
                const odpowiedz = await fetch(
                    `${API_URL}?action=search_tracks&q=${encodeURIComponent(nazwa)}`
                );

                const dane = await odpowiedz.json();

                wyczyscPodpowiedzi();

                if (!dane.ok) {
                    podpowiedzi.innerHTML = '<p class="podpowiedz-info">Discogs chwilowo nie odpowiada. Spróbuj ponownie za chwilę.</p>';
                    return;
                }

                if (!Array.isArray(dane.results) || dane.results.length === 0) {
                    if (dane.discogs_error) {
                        podpowiedzi.innerHTML = '<p class="podpowiedz-info">Discogs nie odpowiada i nie znaleziono pasujących utworów w bazie.</p>';
                    }

                    return;
                }

                // Renderowanie listy podpowiedzi (okładka lub placeholder).
                dane.results.forEach(function (utwor) {
                    const element = document.createElement("div");
                    element.className = "podpowiedz";

                    const rok = utwor.rok ? ' • ' + escapeHtml(String(utwor.rok)) : '';
                    const obraz = utwor.okladka || "";
                    const obrazHtml = obraz
                        ? `<img src="${escapeHtml(obraz)}" alt="Okładka albumu" onerror="this.style.display='none'">`
                        : '<span class="podpowiedz-brak-okladki" aria-hidden="true">♫</span>';

                    element.innerHTML = `
                        ${obrazHtml}
                        <div>
                            <strong>${escapeHtml(utwor.tytul)}</strong>
                            <p>${escapeHtml(utwor.album_tytul)}${rok}</p>
                        </div>
                    `;

                    element.addEventListener("click", function () {
                        wybierzUtwor(utwor);
                    });

                    podpowiedzi.appendChild(element);
                });
            } catch (blad) {
                console.log("Błąd API:", blad);
            }
        }, 400);
    });

    async function wybierzUtwor(utwor) {
        // Tytuł utworu pochodzi z podpowiedzi, a nie z nazwy albumu.
        ustawWartosc("tytul", utwor.tytul);
        ustawWartosc("album_tytul", utwor.album_tytul);
        ustawWartosc("wykonawca", utwor.wykonawca);
        ustawWartosc("rok", utwor.rok > 0 ? utwor.rok : "");
        ustawWartosc("gatunek", utwor.gatunek);
        ustawWartosc("numer", utwor.numer);
        ustawWartosc("czas", utwor.czas);
        // album_id pozwala dodać utwór do istniejącego albumu bez tworzenia duplikatu.
        ustawWartosc("album_id", utwor.album_id || "");
        ustawWartosc("discogs_id", utwor.discogs_id);
        ustawWartosc("discogs_uri", utwor.discogs_uri);

        pokazOkladke(utwor.okladka);
        wyczyscPodpowiedzi();

        const albumId = utwor.discogs_id;

        if (!albumId) {
            return;
        }

        // Dociągamy pełne dane albumu (wykonawca, gatunek, okładka).
        // Gdy Discogs nie odpowie, zostają dane z podpowiedzi.
        try {
            const odpowiedz = await fetch(
                `${API_URL}?action=details&id=${encodeURIComponent(albumId)}`
            );

            const dane = await odpowiedz.json();

            if (!dane.ok || !dane.album) {
                return;
            }

            const album = dane.album;

            ustawWartosc("album_tytul", album.tytul || utwor.album_tytul);
            ustawWartosc("wykonawca", album.wykonawca || utwor.wykonawca);
            ustawWartosc("rok", album.rok > 0 ? album.rok : "");
            ustawWartosc("gatunek", album.gatunek);
            ustawWartosc("discogs_id", album.discogs_id);
            ustawWartosc("discogs_uri", album.discogs_uri);

            pokazOkladke(album.okladka || utwor.okladka);

            // Automatyczny wybór typu płyty, jeśli Discogs podaje CD.
            const typ = document.getElementById("typ_plyty");

            if (typ && /CD/i.test(album.format || "")) {
                for (let i = 0; i < typ.options.length; i++) {
                    if (typ.options[i].text.trim().toUpperCase() === "CD") {
                        typ.selectedIndex = i;
                        break;
                    }
                }
            }
        } catch (blad) {
            console.log("Błąd szczegółów albumu:", blad);
        }
    }

    document.addEventListener("click", function (event) {
        if (!event.target.closest(".search-box")) {
            wyczyscPodpowiedzi();
        }
    });
});
