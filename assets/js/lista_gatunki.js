// lista_gatunki.js — obsługa rozwijanego filtra gatunków w katalogach.

document.addEventListener("DOMContentLoaded", function () {
            const dropdown = document.getElementById("gatunkiDropdown");
            const btn = document.getElementById("gatunkiBtn");
            const menu = document.getElementById("gatunkiMenu");
            const label = document.getElementById("gatunkiLabel");

            if (!dropdown || !btn || !menu || !label) {
                return;
            }

            const checkboxes = menu.querySelectorAll('input[type="checkbox"]');

            // Etykieta pokazuje liczbę zaznaczonych gatunków.
            function odswiezEtykiete() {
                const ile = Array.from(checkboxes).filter(cb => cb.checked).length;
                label.textContent = ile > 0 ? `Gatunki (${ile})` : "Gatunki";
            }

            // Otwieranie i zamykanie listy gatunków.
            btn.addEventListener("click", function (e) {
                e.stopPropagation();
                const isOpen = menu.classList.toggle("open");
                btn.setAttribute("aria-expanded", isOpen ? "true" : "false");
            });

            menu.addEventListener("click", function (e) {
                e.stopPropagation();
            });

            checkboxes.forEach(function (cb) {
                cb.addEventListener("change", odswiezEtykiete);
            });

            // Kliknięcie poza menu zamyka listę.
            document.addEventListener("click", function () {
                menu.classList.remove("open");
                btn.setAttribute("aria-expanded", "false");
            });
        });