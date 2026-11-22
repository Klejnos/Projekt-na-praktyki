// pokaz_haslo.js - pokazywanie i ukrywanie hasła w formularzach.

document.addEventListener("DOMContentLoaded", function() {
    poleHaslo1 = document.getElementById("haslo1");
    poleHaslo2 = document.getElementById("haslo2");
    przycisk = document.getElementById("przycisk_pokazHaslo");
    zdjecie = document.getElementById("img_pokaz_haslo");

    if (przycisk) {
        // Formularz logowania: jedno pole hasła.
        let pojedynczeHaslo = document.getElementById("haslo");
        if (pojedynczeHaslo) {
            przycisk.addEventListener("mousedown", function() {
                pojedynczeHaslo.type = "text";
                zdjecie.src = "assets/img/lock_open.png";
                zdjecie.alt = "Ukryj hasło";
            });
            przycisk.addEventListener("mouseup", function() {
                pojedynczeHaslo.type = "password";
                zdjecie.src = "assets/img/lock.png";
                zdjecie.alt = "Pokaż hasło";
            });
            przycisk.addEventListener("mouseleave", function() {
                pojedynczeHaslo.type = "password";
                zdjecie.src = "assets/img/lock.png";
                zdjecie.alt = "Pokaż hasło";
            });
        }

        // Formularz rejestracji: hasło i jego potwierdzenie.
        if (poleHaslo1 && poleHaslo2) {
            przycisk.addEventListener("mousedown", function() {
                poleHaslo1.type = "text";
                poleHaslo2.type = "text";
                zdjecie.src = "assets/img/lock_open.png";
                zdjecie.alt = "Ukryj hasło";
            });
            przycisk.addEventListener("mouseup", function() {
                poleHaslo1.type = "password";
                poleHaslo2.type = "password";
                zdjecie.src = "assets/img/lock.png";
                zdjecie.alt = "Pokaż hasło";
            });
            przycisk.addEventListener("mouseleave", function() {
                poleHaslo1.type = "password";
                poleHaslo2.type = "password";
                zdjecie.src = "assets/img/lock.png";
                zdjecie.alt = "Pokaż hasło";
            });
        }
    }
});