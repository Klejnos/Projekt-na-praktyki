// script_filmy.js - automatyczne odświeżanie katalogu.
// Reaguje na zmianę sortowania i zatwierdzenie wyszukiwania Enterem.

document.addEventListener("DOMContentLoaded", function () {
    const sortowanie = document.getElementById("sortowanie");
    const wyszukiwarka = document.getElementById("wyszukiwarka");

    if (!sortowanie && !wyszukiwarka) {
        return;
    }

    // Buduje nowy adres na podstawie aktualnych filtrów.
    function zaktualizujURL() {
        const url = new URL(window.location.href);

        // Sortowanie: "default" usuwa parametr z adresu.
        if (sortowanie) {
            if (sortowanie.value === "default") {
                url.searchParams.delete("sort");
            } else {
                url.searchParams.set("sort", sortowanie.value);
            }
        }

        // Wyszukiwanie trafia do parametru q.
        if (wyszukiwarka) {
            const query = wyszukiwarka.value.trim();
            if (query === "") {
                url.searchParams.delete("q");
            } else {
                url.searchParams.set("q", query);
            }
        }

        window.location.href = url.toString();
    }

    if (sortowanie) {
        sortowanie.addEventListener("change", zaktualizujURL);
    }

    if (wyszukiwarka) {
        wyszukiwarka.addEventListener("keypress", function (e) {
            if (e.key === "Enter") {
                zaktualizujURL();
            }
        });
    }
});

