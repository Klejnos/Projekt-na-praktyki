document.addEventListener("DOMContentLoaded", function () {
    const sortowanie = document.getElementById("sortowanie");

    if (!sortowanie) {
        return;
    }

    sortowanie.addEventListener("change", function () {
        const url = new URL(window.location.href);

        if (sortowanie.value === "default") {
            url.searchParams.delete("sort");
        } else {
            url.searchParams.set("sort", sortowanie.value);
        }

        window.location.href = url.toString();
    });
});
