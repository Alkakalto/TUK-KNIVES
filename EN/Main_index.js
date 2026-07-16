/* ============================================= */
/*                MOBILE MENU                     */
/* ============================================= */

const menuBtn = document.getElementById("menu-btn");
const navbar = document.getElementById("navbar");


/* Öppna/Stäng meny */

menuBtn.addEventListener("click", (event) => {

    event.stopPropagation();

    navbar.classList.toggle("active");

    if (navbar.classList.contains("active")) {

        menuBtn.innerHTML = "✕";

    } else {

        menuBtn.innerHTML = "☰";

    }

});


/* Klick inne i menyn ska inte stänga den */

navbar.addEventListener("click", (event) => {

    event.stopPropagation();

});


/* Klick utanför menyn stänger den */

document.addEventListener("click", () => {

    if (navbar.classList.contains("active")) {

        navbar.classList.remove("active");

        menuBtn.innerHTML = "☰";

    }

});


/* Stäng menyn när en länk klickas */

document.querySelectorAll("#navbar a").forEach(link => {

    link.addEventListener("click", () => {

        navbar.classList.remove("active");

        menuBtn.innerHTML = "☰";

    });

});