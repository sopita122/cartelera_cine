<?php
session_start();
$autenticado = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['usuario', 'empleado'], true);
$nombrePerfil = trim((string) ($_SESSION['nombre'] ?? ''));
$apellidoPerfil = trim((string) ($_SESSION['apellido'] ?? ''));
$nombreVisible = $nombrePerfil !== '' ? $nombrePerfil : 'Mi perfil';
preg_match('/^./u', $nombrePerfil, $inicialNombre);
preg_match('/^./u', $apellidoPerfil, $inicialApellido);
$inicialesPerfil = strtoupper(($inicialNombre[0] ?? 'U') . ($inicialApellido[0] ?? ''));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../frontend/css/style.css?v=<?= filemtime(__DIR__ . '/../frontend/css/style.css') ?>">
    <link rel="stylesheet" href="../frontend/css/perfil.css?v=<?= filemtime(__DIR__ . '/../frontend/css/perfil.css') ?>">
    <title>Lumenova Cinema</title>
    <link rel="icon" type="image/png" href="../frontend/img/logo/lumenova.png">
</head>
<body id="inicio" data-session-url="sesion.php" data-logout-url="logout.php">
    <header>
        <div class="site-header">
            <a class="brand" href="#inicio" aria-label="Lumenova Cinema, inicio">
                <span class="brand-mark" aria-hidden="true">LC</span>
                <span class="brand-text">Lumenova<small>CINEMA</small></span>
            </a>
            <nav class="main-nav" id="main-nav" aria-label="Navegacion principal">
                <a class="current" href="#inicio">Inicio</a>
                <a href="#cartelera">Cartelera</a>
                <a href="#estrenos">Estrenos</a>
                <a href="../frontend/html/promociones.html">Promos</a>
                <a href="#contacto">Contacto</a>
            <?php if (!$autenticado): ?>
            <div class="botones-cuenta">
                <button type="button" onclick="location.href='../frontend/html/login.html'">Iniciar sesión</button>
                <button type="button" onclick="location.href='../frontend/html/registro.html'">Registrarse</button>
            </div>
            <?php endif; ?>
            </nav>
            <?php if ($autenticado): ?>
            <div class="profile-menu">
                <button class="profile-button" type="button" aria-expanded="false" aria-controls="profile-panel">
                    <span class="profile-avatar" aria-hidden="true"><?= htmlspecialchars($inicialesPerfil, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="profile-name"><?= htmlspecialchars($nombreVisible, ENT_QUOTES, 'UTF-8') ?></span>
                </button>
                <div class="profile-panel" id="profile-panel" hidden>
                    <button class="profile-option profile-logout" type="button" data-profile-action="logout">Cerrar sesión</button>
                </div>
            </div>
            <?php endif; ?>
            <button class="nav-toggle" type="button" aria-label="Abrir menú" aria-expanded="false" aria-controls="main-nav">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>

    <main>
        <section class="seccion-cartelera">
            <div class="carrusel-contenedor" aria-label="Carrusel de imágenes de películas">
                <button class="carrusel-btn anterior" type="button" aria-label="Anterior" onclick="cambiarImagen(-1)">&#10094;</button>
                <div class="carrusel-imagenes">
                    <img class="carrusel-slide activa" src="../frontend/img/carrusel de imagenes/backrooms.jpeg" alt="Imagen 1">
                    <img class="carrusel-slide" src="../frontend/img/carrusel de imagenes/deadpool.jpeg" alt="Imagen 2">
                    <img class="carrusel-slide" src="../frontend/img/carrusel de imagenes/longlegs.jpeg" alt="Imagen 3">
                    <img class="carrusel-slide" src="../frontend/img/carrusel de imagenes/spider-man home coming.jpeg" alt="Imagen 4">
                    <img class="carrusel-slide" src="../frontend/img/carrusel de imagenes/LaOdisea.jpg" alt="Imagen 5">
                    <img class="carrusel-slide" src="../frontend/img/carrusel de imagenes/The Boys Wallpaper.jpeg" alt="Imagen 6">
                    <img class="carrusel-slide" src="../frontend/img/carrusel de imagenes/the texas chainsaw massacre.jpeg" alt="Imagen 7">
                    <img class="carrusel-slide" src="../frontend/img/carrusel de imagenes/zootopia 2.jpeg" alt="Imagen 8">
                </div>

                <div class="carrusel-puntos" aria-label="Indicadores del carrusel">
                    <button class="carrusel-punto activo" type="button" data-index="0" aria-label="Ir a la imagen 1"></button>
                    <button class="carrusel-punto" type="button" data-index="1" aria-label="Ir a la imagen 2"></button>
                    <button class="carrusel-punto" type="button" data-index="2" aria-label="Ir a la imagen 3"></button>
                    <button class="carrusel-punto" type="button" data-index="3" aria-label="Ir a la imagen 4"></button>
                    <button class="carrusel-punto" type="button" data-index="4" aria-label="Ir a la imagen 5"></button>
                    <button class="carrusel-punto" type="button" data-index="5" aria-label="Ir a la imagen 6"></button>
                    <button class="carrusel-punto" type="button" data-index="6" aria-label="Ir a la imagen 7"></button>
                    <button class="carrusel-punto" type="button" data-index="7" aria-label="Ir a la imagen 8"></button>
                </div>
                <button class="carrusel-btn siguiente" type="button" aria-label="Siguiente" onclick="cambiarImagen(1)">&#10095;</button>
            </div>
            <div id="cartelera"></div><br>
            <div class="peliculas-container">
                <h2>Películas en cartelera</h2>
                <div class="peliculas-grid">
                    <a class="pelicula-card" href="detalle-pelicula.php?pelicula=edward-scissorhands" aria-label="Ver los detalles de Edward Scissorhands">
                        <img src="../frontend/img/peliculas/edward scisssorhands.jpeg" alt="">
                        <div class="pelicula-info">
                            <h3>Edward Scissorhands</h3>
                            <p>Consultá funciones y asientos disponibles</p>
                        </div>
                    </a>
                    <a class="pelicula-card" href="detalle-pelicula.php?pelicula=frankenweenie" aria-label="Ver los detalles de Frankenweenie">
                        <img src="../frontend/img/peliculas/frankenweenie.jpeg" alt="">
                        <div class="pelicula-info">
                            <h3>Frankenweenie</h3>
                            <p>Consultá funciones y asientos disponibles</p>
                        </div>
                    </a>
                    <a class="pelicula-card" href="detalle-pelicula.php?pelicula=la-verdad-del-mal" aria-label="Ver los detalles de La verdad del mal">
                        <img src="../frontend/img/peliculas/La verdad del mal.jpeg" alt="">
                        <div class="pelicula-info">
                            <h3>La verdad del mal</h3>
                            <p>Consultá funciones y asientos disponibles</p>
                        </div>
                    </a>
                    <a class="pelicula-card" href="detalle-pelicula.php?pelicula=avengers-infinity-war" aria-label="Ver los detalles de Avengers Infinity War">
                        <img src="../frontend/img/peliculas/marvel_com.jpeg" alt="">
                        <div class="pelicula-info">
                            <h3>Avengers Infinity War</h3>
                            <p>Consultá funciones y asientos disponibles</p>
                        </div>
                    </a>
                    <a class="pelicula-card" href="detalle-pelicula.php?pelicula=masacre-en-texas" aria-label="Ver los detalles de Masacre en Texas">
                        <img src="../frontend/img/peliculas/masacre en texas.jpeg" alt="">
                        <div class="pelicula-info">
                            <h3>Masacre en Texas</h3>
                            <p>Consultá funciones y asientos disponibles</p>
                        </div>
                    </a>
                    <a class="pelicula-card" href="detalle-pelicula.php?pelicula=paprika" aria-label="Ver los detalles de Paprika">
                        <img src="../frontend/img/peliculas/Paprika.jpeg" alt="">
                        <div class="pelicula-info">
                            <h3>Paprika</h3>
                            <p>Consultá funciones y asientos disponibles</p>
                        </div>
                    </a>
                    <a class="pelicula-card" href="detalle-pelicula.php?pelicula=piratas-del-caribe" aria-label="Ver los detalles de Piratas del Caribe">
                        <img src="../frontend/img/peliculas/Piratas del Caribe_ La maldición de la Perla Negra  (2003).jpeg" alt="">
                        <div class="pelicula-info">
                            <h3>Piratas del caribe</h3>
                            <p>Consultá funciones y asientos disponibles</p>
                        </div>
                    </a>
                    <a class="pelicula-card" href="detalle-pelicula.php?pelicula=superpoli-de-centro-comercial" aria-label="Ver los detalles de Superpoli de centro comercial">
                        <img src="../frontend/img/peliculas/Superpoli de centro comercial (2009) - tt1114740.jpeg" alt="">
                        <div class="pelicula-info">
                            <h3>Superpoli de centro comercial</h3>
                            <p>Consultá funciones y asientos disponibles</p>
                        </div>
                    </a>
                </div>

                <div class="peliculas-grid peliculas-extra oculto">
                    <a class="pelicula-card" href="detalle-pelicula.php?pelicula=robot-salvaje" aria-label="Ver los detalles de Robot Salvaje">
                        <img src="../frontend/img/peliculas/Robot Salvaje.jpeg" alt="">
                        <div class="pelicula-info">
                            <h3>Robot Salvaje</h3>
                            <p>Consultá funciones y asientos disponibles</p>
                        </div>
                    </a>
                    <a class="pelicula-card" href="detalle-pelicula.php?pelicula=obsesion" aria-label="Ver los detalles de Obsesión">
                        <img src="../frontend/img/peliculas/obsesion.jpeg" alt="">
                        <div class="pelicula-info">
                            <h3>Obsesión</h3>
                            <p>Consultá funciones y asientos disponibles</p>
                        </div>
                    </a>
                    <a class="pelicula-card" href="detalle-pelicula.php?pelicula=michael" aria-label="Ver los detalles de Michael Jackson">
                        <img src="../frontend/img/peliculas/Michael (2026).jpeg" alt="">
                        <div class="pelicula-info">
                            <h3>Michael Jackson</h3>
                            <p>Consultá funciones y asientos disponibles</p>
                        </div>
                    </a>
                    <a class="pelicula-card" href="detalle-pelicula.php?pelicula=viernes-13" aria-label="Ver los detalles de Viernes 13">
                        <img src="../frontend/img/peliculas/viernes 13th.jpeg" alt="">
                        <div class="pelicula-info">
                            <h3>Viernes 13</h3>
                            <p>Consultá funciones y asientos disponibles</p>
                        </div>
                    </a>
                </div>

                <div class="expand-container">
                    <button type="button" class="btn-toggle" id="togglePeliculas">Mostrar más</button>
                </div>
            </div>
        </section>
        <section id="estrenos" class="contenido-seccion">
            <h2>Estrenos</h2>
            <p></p>
        </section>
        <hr>
    <footer class="site-footer" id="contacto">
        <div class="footer-content">
            <div class="footer-brand">
                <a class="footer-logo" href="index.php">Lumenova Cinema</a>
                <span>Mas que peliculas una nueva experiencia</span>
                <a class="footer-action" href="#cartelera">Nuestra Cartelera<span aria-hidden="true">→</span></a>
                
            </div>
            <div class="footer-column">
                <h2><span class="footer-heading-icon icon-clock" aria-hidden="true"></span> Atención</h2>
                <p>Lunes a viernes<br><strong>09:00 a 23:00 hs</strong></p>
            </div>
            <div class="footer-column">
                <h2><span class="fa fa-user-circle-o" aria-hidden="true"></span> Contactos</h2>
                <div class="footer-socials">
                    <a href="" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><img src="../frontend/img/logos/Instagram.png" alt=""><span>@LumenovaCinema</span></a>
                    <a href="" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><img src="../frontend/img/logos/facebook.png" alt=""><span>@LumeCine</span></a>
                    <a href="" target="_blank" rel="noopener noreferrer" aria-label="Mail"><img src="../frontend/img/logos/Gmail.png" alt=""><span>LumenovaCines@gmail.com</span></a>

                </div>
            </div>
            <div class="footer-column footer-links">
                <h2><span class="footer-heading-icon icon-explore" aria-hidden="true"></span> Explorar</h2>
                <a href="index.php">Inicio</a>
                <a href="#cartelera">Cartelera</a>
                <a href="#estrenos">Estrenos</a>
                <a href="../frontend/html/promociones.html">Promos</a>
            </div>
        </div>
        <div class="footer-bottom">
            <span>© 2026 Lumenova Cinemas. Todos los derechos reservados.</span>
            <span>LumenovaCinemas · Sitio web de entretenimiento</span>
        </div>
    </footer>
    <script src="../frontend/js/script.js?v=<?= filemtime(__DIR__ . '/../frontend/js/script.js') ?>"></script>
</body>
</html>