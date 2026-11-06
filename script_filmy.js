const czyZalogowanyAdmin = true;
document.addEventListener("DOMContentLoaded", () => {
    const formularz = document.getElementById("formularz");
    const addBtn = document.getElementById("addBtn");

    if (czyZalogowanyAdmin) {
        addBtn.classList.remove("ukryty");
    }
});