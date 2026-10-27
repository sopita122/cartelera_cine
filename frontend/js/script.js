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

function inicializarPeliculasExtra() {
    const contenedorExtra = document.querySelector('.peliculas-extra');
    const botonToggle = document.getElementById('togglePeliculas');

    if (!contenedorExtra || !botonToggle) {
        return;
    }

    botonToggle.addEventListener('click', () => {
        const visible = contenedorExtra.classList.toggle('visible');
        botonToggle.textContent = visible ? 'Mostrar menos' : 'Mostrar más';
    });
}

document.addEventListener('DOMContentLoaded', () => {
    mostrarImagen(0);
    inicializarMenuHamburguesa();

    document.querySelectorAll('.carrusel-punto').forEach((punto) => {
        punto.addEventListener('click', () => {
            mostrarImagen(Number(punto.dataset.index));
            clearInterval(intervaloCarrusel);
            intervaloCarrusel = setInterval(() => cambiarImagen(1), 4000);
        });
    });

    inicializarPeliculasExtra();
    iniciarCarruselAutomatico();
});

function inicializarMenuHamburguesa() {
	const navigation = document.querySelector(".main-nav");
	const toggle = document.querySelector(".nav-toggle");

	if (!navigation || !toggle) {
		return;
	}

	const setMenuOpen = (isOpen) => {
		navigation.classList.toggle("is-open", isOpen);
		toggle.setAttribute("aria-expanded", String(isOpen));
		toggle.setAttribute("aria-label", isOpen ? "Cerrar menú" : "Abrir menú");
	};

	toggle.addEventListener("click", () => {
		setMenuOpen(toggle.getAttribute("aria-expanded") !== "true");
	});

	navigation.querySelectorAll("a").forEach((link) => {
		link.addEventListener("click", () => setMenuOpen(false));
	});

	document.addEventListener("keydown", (event) => {
		if (event.key === "Escape") {
			setMenuOpen(false);
		}
	});

	window.addEventListener("resize", () => {
		if (window.innerWidth > 900) {
			setMenuOpen(false);
		}
	});
}

// PERFIL DE USUARIO
// Consulta el estado de la sesión y adapta el menú de navegación al usuario actual.
document.addEventListener("DOMContentLoaded", async () => {
	const navigation = document.querySelector(".main-nav");
	const existingProfileMenu = navigation
		? getProfileMenu(navigation)
		: document.querySelector(".site-header .profile-menu");

	if (existingProfileMenu) {
		bindProfileMenu(existingProfileMenu);
	}

	if (!navigation) {
		return;
	}

	try {
		const sessionUrl = document.body.dataset.sessionUrl || "../../backend/sesion.php";
		const response = await fetch(sessionUrl, {
			credentials: "same-origin",
			headers: { Accept: "application/json" }
		});
		const session = await response.json();

		if (session.autenticado) {
			renderAuthenticatedMenu(navigation, session);
		}
	} catch (error) {
		// Si la página se abre sin servidor PHP, conservamos el menú público original.
		console.warn("No se pudo consultar la sesión del usuario.", error);
	}
});

// Crea las iniciales que se muestran dentro del avatar del botón de perfil.
const getProfileInitials = (user) => {
	const firstName = (user.nombre || "").trim().charAt(0);
	const lastName = (user.apellido || "").trim().charAt(0);
	return `${firstName}${lastName}`.toUpperCase() || "U";
};

const getProfileMenu = (navigation) => {
	const siteHeader = navigation.closest(".site-header");
	if (!siteHeader) {
		return navigation.querySelector(".profile-menu");
	}

	const profileMenus = [...siteHeader.querySelectorAll(".profile-menu")];
	const profileMenu = profileMenus.find((menu) => !navigation.contains(menu)) || profileMenus[0];

	profileMenus.forEach((menu) => {
		if (menu !== profileMenu) {
			menu.remove();
		}
	});

	const toggle = siteHeader.querySelector(".nav-toggle");
	if (profileMenu && toggle && profileMenu.nextElementSibling !== toggle) {
		toggle.before(profileMenu);
	}

	return profileMenu;
};

// Sustituye los enlaces de iniciar sesión y registrarse por el botón de perfil.
const renderAuthenticatedMenu = (navigation, session) => {
	navigation.querySelector(".botones-cuenta")?.remove();

	navigation.querySelectorAll("a").forEach((link) => {
		const href = link.getAttribute("href") || "";
		const text = link.textContent.toLowerCase();
		const isAuthenticationLink = href.includes("formulario.html")
			|| href.includes("registro.html")
			|| href.includes("register.html")
			|| text.includes("iniciar sesión")
			|| text.includes("registrarse");

		if (isAuthenticationLink) {
			link.remove();
		}
	});

	const siteHeader = navigation.closest(".site-header");
	const existingProfileMenu = getProfileMenu(navigation);
	if (existingProfileMenu) {
		bindProfileMenu(existingProfileMenu);
	} else {
		const profileMenu = document.createElement("div");
		profileMenu.className = "profile-menu";
		profileMenu.innerHTML = `
			<button class="profile-button" type="button" aria-expanded="false" aria-controls="profile-panel">
				<span class="profile-avatar" aria-hidden="true">${getProfileInitials(session.usuario || session)}</span>
				<span class="profile-name">${escapeHtml((session.usuario || session).nombre || "Mi perfil")}</span>
			</button>
			<div class="profile-panel" id="profile-panel" hidden>
				<button class="profile-option profile-logout" type="button" data-profile-action="logout">Cerrar sesión</button>
			</div>`;
		const toggle = siteHeader?.querySelector(".nav-toggle");
		if (toggle) {
			toggle.before(profileMenu);
		} else if (siteHeader) {
			siteHeader.append(profileMenu);
		} else {
			navigation.after(profileMenu);
		}
		bindProfileMenu(profileMenu);
	}
};

// Escapa texto antes de insertarlo en el HTML creado dinámicamente.
const escapeHtml = (value) => String(value)
	.replaceAll("&", "&amp;")
	.replaceAll("<", "&lt;")
	.replaceAll(">", "&gt;")
	.replaceAll('"', "&quot;")
	.replaceAll("'", "&#039;");

// Conecta el botón del perfil con el menú para cerrar sesión.
const bindProfileMenu = (profileMenu) => {
	if (profileMenu.dataset.bound === "true") {
		return;
	}
	profileMenu.dataset.bound = "true";

	const profileButton = profileMenu.querySelector(".profile-button");
	const profilePanel = profileMenu.querySelector(".profile-panel");

	profileButton.addEventListener("click", () => {
		const isOpen = profileButton.getAttribute("aria-expanded") === "true";
		profileButton.setAttribute("aria-expanded", String(!isOpen));
		profilePanel.hidden = isOpen;
	});

	profileMenu.querySelector('[data-profile-action="logout"]').addEventListener("click", () => {
		// El backend destruye la sesión y devuelve a la página de inicio.
		window.location.href = document.body.dataset.logoutUrl || "../../backend/logout.php";
	});
};