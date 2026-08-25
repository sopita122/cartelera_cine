let indiceActual = 0;

function mostrarImagen(indice) {
    const slides = document.querySelectorAll('.carrusel-slide');
    
    if (indice >= slides.length) {
        indiceActual = 0;
    } else if (indice < 0) {
        indiceActual = slides.length - 1;
    } else {
        indiceActual = indice;
    }
    
    slides.forEach(slide => slide.classList.remove('activa'));
    slides[indiceActual].classList.add('activa');
}

function cambiarImagen(direccion) {
    mostrarImagen(indiceActual + direccion);
}

let intervaloCarrusel;

function iniciarCarruselAutomatico() {
    const carrusel = document.querySelector('.carrusel-contenedor');
    const slides = document.querySelectorAll('.carrusel-slide');

    if (!carrusel || slides.length < 2) {
        return;
    }

    const avanzar = () => cambiarImagen(1);

    intervaloCarrusel = setInterval(avanzar, 4000);

    carrusel.addEventListener('mouseenter', () => {
        clearInterval(intervaloCarrusel);
    });

    carrusel.addEventListener('mouseleave', () => {
        clearInterval(intervaloCarrusel);
        intervaloCarrusel = setInterval(avanzar, 4000);
    });
}

iniciarCarruselAutomatico();
