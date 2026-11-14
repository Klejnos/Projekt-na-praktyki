document.addEventListener("DOMContentLoaded", function () {
    const sortowanie = document.getElementById("sortowanie");
    const wyszukiwarka = document.getElementById("wyszukiwarka");

    if (!sortowanie && !wyszukiwarka) {
        return;
    }

    function zaktualizujURL() {
        const url = new URL(window.location.href);

        if (sortowanie) {
            if (sortowanie.value === "default") {
                url.searchParams.delete("sort");
            } else {
                url.searchParams.set("sort", sortowanie.value);
            }
        }

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

