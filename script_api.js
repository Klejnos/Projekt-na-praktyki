const API_KEY = "138317b9";//AI

function szukajFilmu() {

    let nazwa = document.getElementById("szukajFilm").value;

    if (nazwa.trim() === "") {
        alert("Wpisz nazwę filmu!");
        return;
    }

    fetch(`https://www.omdbapi.com/?apikey=${API_KEY}&s=${encodeURIComponent(nazwa)}&type=movie`)
        .then(response => response.json())
        .then(dane => {

            let wyniki = document.getElementById("wyniki");
            wyniki.innerHTML = "";

            if (dane.Response === "False") {
                wyniki.innerHTML = "Nie znaleziono filmów!";
                return;
            }

            dane.Search.forEach(film => {

                wyniki.innerHTML += `
                    <div class="film">
                        <img src="${film.Poster !== "N/A" ? film.Poster : ""}" width="150">

                        <h3>${film.Title}</h3>

                        <p>Rok: ${film.Year}</p>

                        <button onclick="szczegolyFilmu('${film.imdbID}')">
                            Szczegóły
                        </button>
                    </div>
                `;

            });

        })
        .catch(error => {
            console.log(error);
            alert("Wystąpił błąd podczas pobierania filmów.");
        });
}

function szczegolyFilmu(id) {

    fetch(`https://www.omdbapi.com/?apikey=${API_KEY}&i=${id}&plot=full`)
        .then(response => response.json())
        .then(film => {

            alert(
                "Tytuł: " + film.Title +
                "\nRok: " + film.Year +
                "\nGatunek: " + film.Genre +
                "\nReżyser: " + film.Director +
                "\nOcena IMDb: " + film.imdbRating +
                "\nOpis: " + film.Plot
            );

        });
}//AI END