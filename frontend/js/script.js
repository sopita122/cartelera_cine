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


// PERFIL DE USUARIO
// Consulta el estado de la sesión y adapta el menú de navegación al usuario actual.
document.addEventListener("DOMContentLoaded", async () => {
	const navigation = document.querySelector(".main-nav");

	if (!navigation) {
		return;
	}

	try {
		const response = await fetch("../../backend/sesion.php", {
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

// Sustituye los enlaces de iniciar sesión y registrarse por el botón de perfil.
const renderAuthenticatedMenu = (navigation, session) => {
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

	if (!navigation.querySelector(".profile-menu")) {
		const profileMenu = document.createElement("div");
		profileMenu.className = "profile-menu";
		profileMenu.innerHTML = `
			<button class="profile-button" type="button" aria-expanded="false" aria-controls="profile-panel">
				<span class="profile-avatar" aria-hidden="true">${getProfileInitials(session.usuario || session)}</span>
				<span class="profile-name">${escapeHtml((session.usuario || session).nombre || "Mi perfil")}</span>
			</button>
			<div class="profile-panel" id="profile-panel" hidden>
				<button class="profile-option" type="button" data-profile-action="settings">Configuración</button>
				<button class="profile-option profile-logout" type="button" data-profile-action="logout">Cerrar sesión</button>
			</div>`;
		navigation.append(profileMenu);
		bindProfileMenu(profileMenu, session);
	}
};

// Escapa texto antes de insertarlo en el HTML creado dinámicamente.
const escapeHtml = (value) => String(value)
	.replaceAll("&", "&amp;")
	.replaceAll("<", "&lt;")
	.replaceAll(">", "&gt;")
	.replaceAll('"', "&quot;")
	.replaceAll("'", "&#039;");

// Conecta el botón del avatar con el panel de opciones del perfil.
const bindProfileMenu = (profileMenu, session) => {
	const profileButton = profileMenu.querySelector(".profile-button");
	const profilePanel = profileMenu.querySelector(".profile-panel");

	profileButton.addEventListener("click", () => {
		const isOpen = profileButton.getAttribute("aria-expanded") === "true";
		profileButton.setAttribute("aria-expanded", String(!isOpen));
		profilePanel.hidden = isOpen;
	});

	profileMenu.querySelector('[data-profile-action="settings"]').addEventListener("click", () => {
		openProfileDialog(session);
		profilePanel.hidden = true;
		profileButton.setAttribute("aria-expanded", "false");
	});

	profileMenu.querySelector('[data-profile-action="logout"]').addEventListener("click", () => {
		// El backend destruye la sesión y devuelve a la página de inicio.
		window.location.href = "../../backend/logout.php";
	});
};

// Muestra un formulario reutilizable para modificar la información del usuario.
const openProfileDialog = async (session) => {
	let user = session.usuario || session;

	try {
		const response = await fetch("../../backend/perfil.php", {
			credentials: "same-origin",
			headers: { Accept: "application/json" }
		});
		const profileResponse = await response.json();
		if (profileResponse.ok) {
			user = profileResponse.usuario;
		}
	} catch (error) {
		console.warn("No se pudo cargar la configuración del perfil.", error);
	}

	const dialog = document.createElement("dialog");
	dialog.className = "profile-dialog";
	dialog.style.left = "auto";
	dialog.style.right = "24px";
	dialog.style.top = "50%";
	dialog.style.transform = "translateY(-50%)";
	dialog.style.margin = "0";
	dialog.innerHTML = `
		<form class="profile-form" method="dialog">
			<div class="profile-dialog-header">
				<div>
					<p class="profile-kicker">Mi cuenta</p>
					<h2>Configuración del perfil</h2>
				</div>
				<button class="profile-close" type="button" aria-label="Cerrar configuración">×</button>
			</div>
			<div class="profile-fields">
				<label>Nombre<input name="nombre" value="${escapeHtml(user.nombre || "")}" required></label>
				<label>Apellido<input name="apellido" value="${escapeHtml(user.apellido || "")}" required></label>
				<label>DNI<input name="dni" value="${escapeHtml(user.dni || "")}" required></label>
				<label>Teléfono<input name="telefono" value="${escapeHtml(user.telefono || "")}" required></label>
				<label class="profile-field-wide">Email<input name="email" type="email" value="${escapeHtml(user.email || "")}" required></label>
				<label class="profile-field-wide">Nueva contraseña <small>(opcional)</small><input name="password" type="password" minlength="6" autocomplete="new-password"></label>
			</div>
			<p class="profile-status" role="status"></p>
			<div class="profile-dialog-actions">
				<button class="profile-cancel" type="button">Cancelar</button>
				<button class="profile-save" type="submit">Guardar cambios</button>
			</div>
		</form>`;

	document.body.append(dialog);
	dialog.showModal();
	bindProfileDialog(dialog);
};

// Envía los cambios al backend y actualiza el menú cuando se guardan correctamente.
const bindProfileDialog = (dialog) => {
	const form = dialog.querySelector(".profile-form");
	const status = dialog.querySelector(".profile-status");

	const closeDialog = () => {
		dialog.close();
		dialog.remove();
	};

	dialog.querySelector(".profile-close").addEventListener("click", closeDialog);
	dialog.querySelector(".profile-cancel").addEventListener("click", closeDialog);

	form.addEventListener("submit", async (event) => {
		event.preventDefault();
		status.textContent = "Guardando cambios...";

		try {
			const response = await fetch("../../backend/perfil.php", {
				method: "POST",
				body: new FormData(form),
				credentials: "same-origin",
				headers: { Accept: "application/json" }
			});
			const result = await response.json();

			if (!result.ok) {
				status.textContent = result.mensaje;
				return;
			}

			status.textContent = result.mensaje;
			setTimeout(closeDialog, 700);
			window.location.reload();
		} catch (error) {
			status.textContent = "No se pudo conectar con el servidor.";
		}
	});
};