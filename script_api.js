
const API_KEY = "138317b9";//AI

const tytul = document.getElementById("tytul");
const podpowiedzi = document.getElementById("podpowiedzi");

let opoznienie;

// Wyszukiwanie filmów podczas wpisywania
tytul.addEventListener("input", function () {

    clearTimeout(opoznienie);

    let nazwa = tytul.value.trim();

    podpowiedzi.innerHTML = "";

    // Ukrywanie poprzedniej okładki
    document.getElementById("okladka").style.display = "none";

    if (nazwa.length < 2) {
        return;
    }

    opoznienie = setTimeout(async function () {

        try {

            let odpowiedz = await fetch(
                `https://www.omdbapi.com/?apikey=${API_KEY}&s=${encodeURIComponent(nazwa)}&type=movie`
            );

            let dane = await odpowiedz.json();

            podpowiedzi.innerHTML = "";

            if (dane.Response === "False") {
                return;
            }

            dane.Search.forEach(function (film) {

                let element = document.createElement("div");

                element.className = "podpowiedz";

                let obraz = film.Poster !== "N/A" ? film.Poster : "";

                element.innerHTML = `
                    <img src="${obraz}" alt="Plakat"
                     onerror="this.style.display='none'">

                 <div>
                <strong>${film.Title}</strong>
             <p>${film.Year}</p>
    </div>
`;

                element.addEventListener("click", function () {
                    pobierzFilm(film.imdbID);
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
            `https://www.omdbapi.com/?apikey=${API_KEY}&i=${id}&plot=full`
        );

        let film = await odpowiedz.json();

        if (film.Response === "False") {
            alert("Nie udało się pobrać filmu!");
            return;
        }

        // Uzupełnianie formularza

        document.getElementById("tytul").value = film.Title;
        document.getElementById("gatunek").value = film.Genre;
        document.getElementById("rok").value = film.Year;
        document.getElementById("ocena").value = film.imdbRating;
        document.getElementById("rezyser").value = film.Director;

        document.getElementById("czas_trwania").value =
            film.Runtime.replace(" min", "");

        document.getElementById("jezyk").value = film.Language;
        document.getElementById("produkcja").value = film.Production;
        document.getElementById("nagrody").value = film.Awards;
        document.getElementById("opis").value = film.Plot;

        // Wyświetlanie okładki filmu

        let okladka = document.getElementById("okladka");

        if (film.Poster && film.Poster !== "N/A") {

            okladka.src = film.Poster;
            okladka.style.display = "block";

        } else {

            okladka.src = "";
            okladka.style.display = "none";

        }

        // Pola do ręcznego uzupełnienia

        document.getElementById("ilosc_dostepnych").value = "";
        document.getElementById("budzet").value = "";
        document.getElementById("cena").value = "";

        // Usuwanie podpowiedzi

        podpowiedzi.innerHTML = "";

    } catch (blad) {

        console.log("Błąd:", blad);
        alert("Problem z połączeniem z API!");

    }

}
