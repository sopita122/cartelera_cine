<?php
session_start();

$catalogo = [
    'edward-scissorhands' => [
        'titulo' => 'Edward Scissorhands',
        'portada' => 'edward scisssorhands.jpeg',
        'sinopsis' => '',
    ],
    'frankenweenie' => [
        'titulo' => 'frankenweenie',
        'portada' => 'frankenweenie.jpeg',
        'sinopsis' => '',
    ],
    'la-verdad-del-mal' => [
        'titulo' => 'El silencio de los corderos',
        'portada' => 'El silencio de los corderos.jpeg',
        'sinopsis' => '',
    ],
    'avengers-infinity-war' => [
        'titulo' => 'Avengers infinity war',
        'portada' => 'marvel_com.jpeg',
        'sinopsis' => '',
    ],
    'masacre-en-texas' => [
        'titulo' => 'masacre en texas',
        'portada' => 'masacre en texas.jpeg',
        'sinopsis' => '',
    ],
    'paprika' => [
        'titulo' => 'Paprika',
        'portada' => 'Paprika.jpeg',
        'sinopsis' => '',
    ],
    'piratas-del-caribe' => [
        'titulo' => 'Piratas del caribe',
        'portada' => 'Piratas del Caribe_ La maldición de la Perla Negra  (2003).jpeg',
        'sinopsis' => '',
    ],
    'superpoli-de-centro-comercial' => [
        'titulo' => 'Superpoli de centro comercial',
        'portada' => 'Superpoli de centro comercial (2009) - tt1114740.jpeg',
        'sinopsis' => '',
    ],
    'robot-salvaje' => [
        'titulo' => 'Robot Salvaje',
        'portada' => 'Robot Salvaje.jpeg',
        'sinopsis' => '',
    ],
    'obsesion' => [
        'titulo' => 'Obsesión',
        'portada' => 'obsesion.jpeg',
        'sinopsis' => '',
    ],
    'michael' => [
        'titulo' => 'Michael Jackson',
        'portada' => 'Michael (2026).jpeg',
        'sinopsis' => '',
    ],
    'viernes-13' => [
        'titulo' => 'viernes 13',
        'portada' => 'viernes 13th.jpeg',
        'sinopsis' => '',
    ],
];

$metodo = $_SERVER['REQUEST_METHOD'];
$slugRecibido = $metodo === 'POST'
    ? ($_POST['pelicula'] ?? '')
    : ($_GET['pelicula'] ?? '');
$slug = is_string($slugRecibido) ? $slugRecibido : '';

if (!isset($catalogo[$slug])) {
    http_response_code(404);
    $pelicula = null;
} else {
    $pelicula = $catalogo[$slug];
}

$escape = static fn(string $valor): string => htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$autenticado = ($_SESSION['tipo'] ?? '') === 'usuario' && !empty($_SESSION['id_usuario']);
$errorCompra = '';
$compraRegistrada = null;
$asientosElegidos = [];
$errorConsulta = '';
$errorFunciones = '';
$datosBase = [];
$funciones = [];
$asientos = [];
$asientosPorFila = [];
$funcionSeleccionadaId = 0;
$precioFuncion = 0;
$idSalaSeleccionada = 0;

if ($pelicula !== null) {
    require_once __DIR__ . '/conexion.php';

    try {
        $consulta = $pdo->prepare(
            'SELECT p.id_peliculas, p.titulo, p.`duracion (minutos)` AS duracion, p.formato, p.idioma, c.nombre AS categoria
            FROM peliculas AS p
            LEFT JOIN categorias AS c ON c.id_categoria = p.id_categoria
            WHERE LOWER(p.titulo) = LOWER(:titulo)
            LIMIT 1'
        );
        $consulta->execute(['titulo' => $pelicula['titulo']]);
        $datosBase = $consulta->fetch() ?: [];

        if ($datosBase !== []) {
            $consultaFunciones = $pdo->prepare(
                'SELECT f.id_funcion, f.id_sala, f.fecha_hora, f.coste, s.nombre AS sala
                FROM `horarios/funciones` AS f
                LEFT JOIN salas AS s ON s.id_salas = f.id_sala
                WHERE f.id_peliculas = :id_pelicula
                ORDER BY f.fecha_hora, f.id_funcion'
            );
            $consultaFunciones->execute(['id_pelicula' => $datosBase['id_peliculas']]);
            $funciones = $consultaFunciones->fetchAll();
        }
    } catch (PDOException $error) {
        error_log('No se pudieron consultar los detalles y funciones de la película: ' . $error->getMessage());
        $errorConsulta = 'No se pudieron consultar los datos de esta película en la base de datos.';
    }

    if (!isset($_SESSION['csrf_entradas'])) {
        $_SESSION['csrf_entradas'] = bin2hex(random_bytes(32));
    }

    $funcionSolicitada = $metodo === 'POST'
        ? ($_POST['funcion'] ?? null)
        : ($_GET['funcion'] ?? null);
    $solicitudFuncion = is_string($funcionSolicitada) || is_int($funcionSolicitada)
        ? filter_var($funcionSolicitada, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
        : false;

    foreach ($funciones as $funcion) {
        if ((int) $funcion['id_funcion'] === $solicitudFuncion) {
            $funcionSeleccionadaId = (int) $funcion['id_funcion'];
            break;
        }
    }

    if ($funcionSeleccionadaId === 0 && $metodo !== 'POST' && $funciones !== []) {
        $funcionSeleccionadaId = (int) $funciones[0]['id_funcion'];
    }

    foreach ($funciones as $funcion) {
        if ((int) $funcion['id_funcion'] === $funcionSeleccionadaId) {
            $precioFuncion = (int) $funcion['coste'];
            $idSalaSeleccionada = (int) $funcion['id_sala'];
            break;
        }
    }

    if ($funcionSeleccionadaId > 0) {
        try {
            $consultaAsientos = $pdo->prepare(
                'SELECT a.id_asiento, a.fila, a.columna, a.tipo_asiento,
                    CASE WHEN e.id_entrada IS NULL THEN 0 ELSE 1 END AS ocupado
                FROM asientos AS a
                LEFT JOIN entradas AS e
                    ON e.id_asiento = a.id_asiento AND e.id_funcion = :id_funcion
                WHERE a.id_sala = :id_sala
                ORDER BY a.fila, a.columna'
            );
            $consultaAsientos->execute([
                'id_funcion' => $funcionSeleccionadaId,
                'id_sala' => $idSalaSeleccionada,
            ]);
            $asientos = $consultaAsientos->fetchAll();
            foreach ($asientos as $asiento) {
                $asientosPorFila[(int) $asiento['fila']][] = $asiento;
            }
        } catch (PDOException $error) {
            error_log('No se pudieron consultar los asientos de la función: ' . $error->getMessage());
            $errorFunciones = 'No se pudieron consultar los asientos de esta función.';
        }
    }

    $compraSolicitada = $metodo === 'GET'
        ? filter_var($_GET['compra'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
        : false;
    if ($autenticado && $compraSolicitada !== false && $funcionSeleccionadaId > 0) {
        try {
            $consultaCompra = $pdo->prepare(
                'SELECT c.id_compra, c.total, c.fecha_compra, e.id_asiento, a.fila, a.columna
                FROM compra AS c
                INNER JOIN entradas AS e ON e.id_compra = c.id_compra
                INNER JOIN asientos AS a ON a.id_asiento = e.id_asiento
                WHERE c.id_compra = :id_compra
                    AND c.id_usuario = :id_usuario
                    AND e.id_funcion = :id_funcion
                ORDER BY a.fila, a.columna'
            );
            $consultaCompra->execute([
                'id_compra' => $compraSolicitada,
                'id_usuario' => (int) $_SESSION['id_usuario'],
                'id_funcion' => $funcionSeleccionadaId,
            ]);
            $filasCompra = $consultaCompra->fetchAll();
            if ($filasCompra !== []) {
                $compraRegistrada = [
                    'id' => (int) $filasCompra[0]['id_compra'],
                    'total' => (int) $filasCompra[0]['total'],
                    'asientos' => array_map(
                        static fn(array $fila): string => etiquetaFila((int) $fila['fila']) . (int) $fila['columna'],
                        $filasCompra
                    ),
                ];
            }
        } catch (PDOException $error) {
            error_log('No se pudo consultar el resumen de la compra: ' . $error->getMessage());
            $errorCompra = 'No se pudo consultar el resumen de la compra.';
        }
    }

    if ($metodo === 'POST') {
        $tokenRecibido = $_POST['csrf_token'] ?? '';
        $token = is_string($tokenRecibido) ? $tokenRecibido : '';
        $seleccion = $_POST['asientos'] ?? [];
        $asientosPost = is_array($seleccion) ? $seleccion : [];

        if (!$autenticado) {
            $errorCompra = 'Iniciá sesión con una cuenta de usuario para continuar con la compra.';
        } elseif (!hash_equals($_SESSION['csrf_entradas'], $token)) {
            $errorCompra = 'La página venció. Actualizala e intentá nuevamente.';
        } elseif ($funcionSeleccionadaId === 0) {
            $errorCompra = 'Elegí una función disponible.';
        } elseif (count($asientosPost) < 1 || count($asientosPost) > 10) {
            $errorCompra = 'Seleccioná entre 1 y 10 asientos disponibles.';
        } else {
            $idsElegidos = [];
            foreach ($asientosPost as $idAsiento) {
                if (!is_string($idAsiento) && !is_int($idAsiento)) {
                    $idsElegidos = [];
                    break;
                }
                $idValido = filter_var($idAsiento, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($idValido === false || in_array($idValido, $idsElegidos, true)) {
                    $idsElegidos = [];
                    break;
                }
                $idsElegidos[] = $idValido;
            }

            $asientosDisponibles = [];
            foreach ($asientos as $asiento) {
                if (!(bool) $asiento['ocupado']) {
                    $asientosDisponibles[] = (int) $asiento['id_asiento'];
                }
            }

            if ($idsElegidos === [] || array_diff($idsElegidos, $asientosDisponibles) !== []) {
                $errorCompra = 'Uno de los asientos seleccionados ya no está disponible. Actualizá la página y volvé a elegir.';
            } else {
                try {
                    $pdo->beginTransaction();
                    $marcadores = implode(', ', array_fill(0, count($idsElegidos), '?'));
                    $bloqueoAsientos = $pdo->prepare(
                        "SELECT id_asiento
                        FROM asientos
                        WHERE id_sala = ? AND id_asiento IN ($marcadores)
                        FOR UPDATE"
                    );
                    $bloqueoAsientos->execute(array_merge([$idSalaSeleccionada], $idsElegidos));
                    $asientosDeLaSala = array_map('intval', $bloqueoAsientos->fetchAll(PDO::FETCH_COLUMN));

                    if (count($asientosDeLaSala) !== count($idsElegidos)) {
                        throw new DomainException('Uno de los asientos no pertenece a la sala de esta función.');
                    }

                    $consultaOcupados = $pdo->prepare(
                        "SELECT id_asiento
                        FROM entradas
                        WHERE id_funcion = ? AND id_asiento IN ($marcadores)
                        FOR UPDATE"
                    );
                    $consultaOcupados->execute(array_merge([$funcionSeleccionadaId], $idsElegidos));
                    if ($consultaOcupados->fetchColumn() !== false) {
                        throw new DomainException('Uno de los asientos ya fue ocupado. Actualizá la página y volvé a elegir.');
                    }

                    $registrarCompra = $pdo->prepare(
                        'INSERT INTO compra (id_usuario, fecha_compra, total, metodo_pago, estado_pago)
                        VALUES (:id_usuario, CURRENT_DATE(), :total, :metodo_pago, 0)'
                    );
                    $registrarCompra->execute([
                        'id_usuario' => (int) $_SESSION['id_usuario'],
                        'total' => $precioFuncion * count($idsElegidos),
                        'metodo_pago' => 'Sin cobro',
                    ]);
                    $idCompra = (int) $pdo->lastInsertId();
                    if ($idCompra < 1) {
                        throw new RuntimeException('No se pudo obtener el número de compra registrado.');
                    }

                    $registrarEntrada = $pdo->prepare(
                        'INSERT INTO entradas (COD_Entrada, id_asiento, id_compra, id_funcion)
                        VALUES (:codigo, :id_asiento, :id_compra, :id_funcion)'
                    );
                    foreach ($idsElegidos as $idAsiento) {
                        $registrarEntrada->execute([
                            'codigo' => strtoupper(bin2hex(random_bytes(5))),
                            'id_asiento' => $idAsiento,
                            'id_compra' => $idCompra,
                            'id_funcion' => $funcionSeleccionadaId,
                        ]);
                    }

                    $pdo->commit();
                    header(
                        'Location: detalle-pelicula.php?pelicula=' . rawurlencode($slug)
                        . '&funcion=' . $funcionSeleccionadaId
                        . '&compra=' . $idCompra,
                        true,
                        303
                    );
                    exit;
                } catch (DomainException $error) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $errorCompra = $error->getMessage();
                } catch (PDOException | RuntimeException $error) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    error_log('No se pudo registrar la compra y sus entradas: ' . $error->getMessage());
                    $errorCompra = 'No se pudo registrar la compra. Verificá la base de datos e intentá nuevamente.';
                }
            }
        }
    } elseif ($metodo !== 'GET') {
        http_response_code(405);
        header('Allow: GET, POST');
    }
}

function etiquetaFila(int $fila): string
{
    if ($fila < 1) {
        return (string) $fila;
    }

    $etiqueta = '';
    while ($fila > 0) {
        $fila--;
        $etiqueta = chr(65 + ($fila % 26)) . $etiqueta;
        $fila = intdiv($fila, 26);
    }

    return $etiqueta;
}

function mostrarDuracion(?string $duracion): string
{
    if (!$duracion || !preg_match('/^(\d{2}):(\d{2}):\d{2}$/', $duracion, $partes)) {
        return 'No disponible';
    }

    $horas = (int) $partes[1];
    $minutos = (int) $partes[2];
    $totalMinutos = ($horas * 60) + $minutos;

    return $totalMinutos . ' min';
}

function mostrarFormato(?int $formato): string
{
    if ($formato === null || $formato < 1) {
        return 'No disponible';
    }

    return $formato . 'D';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../frontend/css/style.css?v=<?= filemtime(__DIR__ . '/../frontend/css/style.css') ?>">
    <link rel="stylesheet" href="../frontend/css/perfil.css?v=<?= filemtime(__DIR__ . '/../frontend/css/perfil.css') ?>">
    <link rel="stylesheet" href="../frontend/css/detalle-pelicula.css?v=<?= filemtime(__DIR__ . '/../frontend/css/detalle-pelicula.css') ?>">
    <title><?= $pelicula ? $escape($pelicula['titulo']) . ' | Lumenova Cinema' : 'Película no encontrada | Lumenova Cinema' ?></title>
    <link rel="icon" type="image/png" href="../frontend/img/logo/lumenova.png">
</head>
<body class="detalle-page" data-session-url="sesion.php" data-logout-url="logout.php">
    <header>
        <div class="site-header">
            <a class="brand" href="index.php" aria-label="Lumenova Cinema, inicio">
                <span class="brand-mark" aria-hidden="true">LC</span>
                <span class="brand-text">Lumenova<small>CINEMA</small></span>
            </a>
            <?php if (!$autenticado): ?>
                <nav class="main-nav" id="main-nav" aria-label="Navegación principal">
                    <a href="../frontend/html/login.html<?= $pelicula ? '?pelicula=' . rawurlencode($slug) . ($funcionSeleccionadaId > 0 ? '&funcion=' . $funcionSeleccionadaId : '') : '' ?>">Iniciar sesión</a>
                </nav>
            <?php endif; ?>
            <?php if ($autenticado): ?>
                <?php
                $nombrePerfil = trim((string) ($_SESSION['nombre'] ?? 'Mi perfil'));
                $apellidoPerfil = trim((string) ($_SESSION['apellido'] ?? ''));
                preg_match('/^./u', $nombrePerfil, $inicialNombre);
                preg_match('/^./u', $apellidoPerfil, $inicialApellido);
                $inicialesPerfil = strtoupper(($inicialNombre[0] ?? 'U') . ($inicialApellido[0] ?? ''));
                ?>
                <div class="profile-menu">
                    <button class="profile-button" type="button" aria-expanded="false" aria-controls="profile-panel">
                        <span class="profile-avatar" aria-hidden="true"><?= $escape($inicialesPerfil) ?></span>
                        <span class="profile-name"><?= $escape($nombrePerfil) ?></span>
                    </button>
                    <div class="profile-panel" id="profile-panel" hidden>
                        <button class="profile-option profile-logout" type="button" data-profile-action="logout">Cerrar sesión</button>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (!$autenticado): ?>
                <button class="nav-toggle" type="button" aria-label="Abrir menú" aria-expanded="false" aria-controls="main-nav">
                    <span></span><span></span><span></span>
                </button>
            <?php endif; ?>
        </div>
    </header>

    <main class="detalle-main">
        <?php if ($pelicula === null): ?>
            <section class="detalle-mensaje">
                <h1>Película no encontrada</h1>
                <p>Elegí una película desde la cartelera para consultar sus detalles.</p>
                <a class="detalle-enlace" href="index.php">Volver a cartelera</a>
            </section>
        <?php else: ?>
            <a class="detalle-volver" href="index.php#cartelera">← Volver a cartelera</a>

            <?php if ($errorConsulta !== ''): ?>
                <p class="detalle-alerta" role="alert"><?= $escape($errorConsulta) ?></p>
            <?php endif; ?>

            <article class="detalle-ficha">
                <img class="detalle-portada" src="../frontend/img/peliculas/<?= rawurlencode($pelicula['portada']) ?>" alt="Afiche de <?= $escape($pelicula['titulo']) ?>">
                <div class="detalle-contenido">
                    <h1><?= $escape($pelicula['titulo']) ?></h1>
                    <p class="detalle-sinopsis"><?= $escape($pelicula['sinopsis']) ?></p>

                    <dl class="detalle-datos">
                        <div><dt>Duración</dt><dd><?= $escape(mostrarDuracion(isset($datosBase['duracion']) ? (string) $datosBase['duracion'] : null)) ?></dd></div>
                        <div><dt>Categoría</dt><dd><?= $escape((string) ($datosBase['categoria'] ?? 'No disponible')) ?></dd></div>
                        <div><dt>Formato</dt><dd><?= $escape(mostrarFormato(isset($datosBase['formato']) ? (int) $datosBase['formato'] : null)) ?></dd></div>
                        <div><dt>Idioma</dt><dd><?= $escape((string) ($datosBase['idioma'] ?? 'No disponible')) ?></dd></div>
                    </dl>

                </div>
            </article>

            <section class="detalle-reserva" aria-labelledby="compra-titulo">
                <h2 id="compra-titulo">Elegí tu función y tus asientos</h2>

                <?php if ($errorFunciones !== ''): ?>
                    <p class="detalle-alerta" role="alert"><?= $escape($errorFunciones) ?></p>
                <?php elseif ($funciones === []): ?>
                    <p class="detalle-aviso">Todavía no hay funciones programadas para esta película.</p>
                <?php else: ?>
                    <form class="detalle-funcion" method="get" action="detalle-pelicula.php">
                        <input type="hidden" name="pelicula" value="<?= $escape($slug) ?>">
                        <label for="funcion">Función</label>
                        <select id="funcion" name="funcion" onchange="this.form.submit()">
                            <?php foreach ($funciones as $funcion): ?>
                                <option value="<?= (int) $funcion['id_funcion'] ?>" <?= (int) $funcion['id_funcion'] === $funcionSeleccionadaId ? 'selected' : '' ?>>
                                    <?= $escape(substr((string) $funcion['fecha_hora'], 0, 5)) ?>
                                    · <?= $escape((string) ($funcion['sala'] ?? 'Sala')) ?>
                                    · $<?= number_format((int) $funcion['coste'], 0, ',', '.') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <noscript><button type="submit">Ver función</button></noscript>
                    </form>

                    <?php if ($asientos === []): ?>
                        <p class="detalle-aviso">Esta sala todavía no tiene asientos configurados.</p>
                    <?php else: ?>
                        <?php if ($errorCompra !== ''): ?>
                            <p class="detalle-alerta" role="alert"><?= $escape($errorCompra) ?></p>
                        <?php endif; ?>

                        <?php if ($compraRegistrada !== null): ?>
                            <div class="detalle-confirmacion" role="status">
                                <h3>Compra registrada correctamente</h3>
                                <p>Número de compra: <?= $compraRegistrada['id'] ?></p>
                                <p><?= count($compraRegistrada['asientos']) ?> <?= count($compraRegistrada['asientos']) === 1 ? 'entrada' : 'entradas' ?> para <?= $escape($pelicula['titulo']) ?>.</p>
                                <p>Asientos confirmados: <?= $escape(implode(', ', $compraRegistrada['asientos'])) ?></p>
                                <p>Total registrado: $<?= number_format($compraRegistrada['total'], 0, ',', '.') ?></p>
                                <p>La compra y los asientos quedaron registrados. No se realizó ningún cobro ni se guardaron datos de tarjeta.</p>
                            </div>
                        <?php endif; ?>

                        <div class="detalle-leyenda" aria-label="Estados de los asientos">
                            <span><i class="asiento-muestra disponible" aria-hidden="true"></i> Disponible</span>
                            <span><i class="asiento-muestra seleccionado" aria-hidden="true"></i> Seleccionado</span>
                            <span><i class="asiento-muestra ocupado" aria-hidden="true"></i> Ocupado</span>
                        </div>
                        <div class="detalle-pantalla" aria-hidden="true">PANTALLA</div>

                        <?php if ($autenticado): ?>
                            <form class="detalle-compra" method="post" action="detalle-pelicula.php?pelicula=<?= rawurlencode($slug) ?>&funcion=<?= $funcionSeleccionadaId ?>">
                                <input type="hidden" name="pelicula" value="<?= $escape($slug) ?>">
                                <input type="hidden" name="funcion" value="<?= $funcionSeleccionadaId ?>">
                                <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_entradas']) ?>">
                                <div class="asientos-grid" role="group" aria-label="Elegí tus asientos">
                                    <?php foreach ($asientosPorFila as $numeroFila => $asientosFila): ?>
                                        <div class="asiento-fila" aria-label="Fila <?= $escape(etiquetaFila((int) $numeroFila)) ?>">
                                            <?php foreach ($asientosFila as $asiento): ?>
                                                <?php
                                                $idAsiento = (int) $asiento['id_asiento'];
                                                $ocupado = (bool) $asiento['ocupado'];
                                                $etiquetaAsiento = etiquetaFila((int) $asiento['fila']) . (int) $asiento['columna'];
                                                ?>
                                                <label class="asiento-opcion<?= $ocupado ? ' asiento-opcion-ocupado' : '' ?>">
                                                    <input
                                                        type="checkbox"
                                                        name="asientos[]"
                                                        value="<?= $idAsiento ?>"
                                                        aria-label="<?= $escape($etiquetaAsiento . ($ocupado ? ', ocupado por otra persona' : ', disponible')) ?>"
                                                        title="<?= $escape($etiquetaAsiento . ($ocupado ? ' · Ocupado por otra persona' : ' · Disponible')) ?>"
                                                        <?= $ocupado ? 'disabled' : '' ?>
                                                        <?= in_array($idAsiento, $asientosElegidos, true) ? 'checked' : '' ?>>
                                                    <span><?= $escape($etiquetaAsiento) ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <p class="detalle-total">Asientos: <span data-asientos-elegidos>0</span> · Total: $<span data-total="<?= $precioFuncion ?>"><?= number_format(0, 0, ',', '.') ?></span></p>
                                <button type="submit" disabled>Confirmar compra</button>
                                <p class="detalle-aclaracion">Al confirmar, la compra y los asientos quedarán registrados a tu nombre. No se realizará ningún cobro ni se solicitarán datos de tarjeta.</p>
                            </form>
                        <?php else: ?>
                            <div class="asientos-grid asientos-solo-lectura" aria-label="Disponibilidad de asientos">
                                <?php foreach ($asientosPorFila as $numeroFila => $asientosFila): ?>
                                    <div class="asiento-fila" aria-label="Fila <?= $escape(etiquetaFila((int) $numeroFila)) ?>">
                                        <?php foreach ($asientosFila as $asiento): ?>
                                            <?php
                                            $etiquetaAsiento = etiquetaFila((int) $asiento['fila']) . (int) $asiento['columna'];
                                            $ocupado = (bool) $asiento['ocupado'];
                                            ?>
                                            <span class="asiento-opcion<?= $ocupado ? ' asiento-opcion-ocupado' : '' ?>" aria-label="<?= $escape($etiquetaAsiento . ($ocupado ? ', ocupado por otra persona' : ', disponible')) ?>" title="<?= $ocupado ? 'Ocupado por otra persona' : 'Disponible; iniciá sesión para seleccionarlo' ?>">
                                                <?= $escape($etiquetaAsiento) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="detalle-login">
                                <p>Iniciá sesión para seleccionar tus asientos y continuar con la compra.</p>
                                <a class="detalle-enlace" href="../frontend/html/login.html?pelicula=<?= rawurlencode($slug) ?>&funcion=<?= $funcionSeleccionadaId ?>">Iniciar sesión</a>
                            </div>
                        <?php endif; ?>
                        <p class="detalle-aviso">Los asientos ocupados corresponden a entradas registradas para esta función. Seleccionar asientos no los reserva ni confirma el pago.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>

    <script>
        document.querySelectorAll('.detalle-compra').forEach((formulario) => {
            const entradas = [...formulario.querySelectorAll('input[name="asientos[]"]')];
            const cantidad = formulario.querySelector('[data-asientos-elegidos]');
            const total = formulario.querySelector('[data-total]');
            const actualizarTotal = () => {
                const seleccionados = entradas.filter((entrada) => entrada.checked);
                cantidad.textContent = String(seleccionados.length);
                total.textContent = new Intl.NumberFormat('es-UY').format(
                    Number(total.dataset.total) * seleccionados.length
                );
                formulario.querySelector('button[type="submit"]').disabled =
                    seleccionados.length < 1 || seleccionados.length > 10;
            };

            entradas.forEach((entrada) => entrada.addEventListener('change', actualizarTotal));
            actualizarTotal();
        });
    </script>
    <script src="../frontend/js/script.js?v=<?= filemtime(__DIR__ . '/../frontend/js/script.js') ?>"></script>
</body>
</html>
