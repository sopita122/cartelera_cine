let indiceActual = 0;
let intervaloCarrusel;

function actualizarPuntos() {
    const puntos = document.querySelectorAll('.carrusel-punto');

    puntos.forEach((punto, index) => {
        punto.classList.toggle('activo', index === indiceActual);
    });
}

function mostrarImagen(indice) {
    const slides = document.querySelectorAll('.carrusel-slide');

    if (!slides.length) {
        return;
    }

    if (indice >= slides.length) {
        indiceActual = 0;
    } else if (indice < 0) {
        indiceActual = slides.length - 1;
    } else {
        indiceActual = indice;
    }

    slides.forEach((slide, index) => {
        slide.classList.toggle('activa', index === indiceActual);
    });

    actualizarPuntos();
}

function cambiarImagen(direccion) {
    mostrarImagen(indiceActual + direccion);
}

function iniciarCarruselAutomatico() {
    const carrusel = document.querySelector('.carrusel-contenedor');
    const slides = document.querySelectorAll('.carrusel-slide');

    if (!carrusel || slides.length < 2) {
        return;
    }

    const avanzar = () => cambiarImagen(1);

    const reiniciarIntervalo = () => {
        clearInterval(intervaloCarrusel);
        intervaloCarrusel = setInterval(avanzar, 4000);
    };

    reiniciarIntervalo();

    carrusel.addEventListener('mouseenter', () => {
        clearInterval(intervaloCarrusel);
    });

    carrusel.addEventListener('mouseleave', () => {
        reiniciarIntervalo();
    });
}

document.addEventListener('DOMContentLoaded', () => {
    mostrarImagen(0);

    document.querySelectorAll('.carrusel-punto').forEach((punto) => {
        punto.addEventListener('click', () => {
            mostrarImagen(Number(punto.dataset.index));
            clearInterval(intervaloCarrusel);
            intervaloCarrusel = setInterval(() => cambiarImagen(1), 4000);
        });
    });

    iniciarCarruselAutomatico();
});
