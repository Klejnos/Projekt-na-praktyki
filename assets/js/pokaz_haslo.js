document.addEventListener("DOMContentLoaded", function() {
    poleHaslo1 = document.getElementById("haslo1");
    poleHaslo2 = document.getElementById("haslo2");
    przycisk = document.getElementById("przycisk_pokazHaslo");

    if (przycisk) {
        let pojedynczeHaslo = document.getElementById("haslo");
        if (pojedynczeHaslo) {
            przycisk.addEventListener("mousedown", function() {
                pojedynczeHaslo.type = "text";
                przycisk.textContent = "Ukryj hasło";
            });
            przycisk.addEventListener("mouseup", function() {
                pojedynczeHaslo.type = "password";
                przycisk.textContent = "Pokaż hasło";
            });
            przycisk.addEventListener("mouseleave", function() {
                pojedynczeHaslo.type = "password";
                przycisk.textContent = "Pokaż hasło";
            });
        }

        if (poleHaslo1 && poleHaslo2) {
            przycisk.addEventListener("mousedown", function() {
                poleHaslo1.type = "text";
                poleHaslo2.type = "text";
                przycisk.textContent = "Ukryj hasła";
            });
            przycisk.addEventListener("mouseup", function() {
                poleHaslo1.type = "password";
                poleHaslo2.type = "password";
                przycisk.textContent = "Pokaż hasła";
            });
            przycisk.addEventListener("mouseleave", function() {
                poleHaslo1.type = "password";
                poleHaslo2.type = "password";
                przycisk.textContent = "Pokaż hasła";
            });
        }
    }
});