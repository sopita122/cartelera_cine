<?php
session_start();
require_once 'conexion.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $peliculaRegreso = $_POST['volver_a_pelicula'] ?? '';
    $funcionRegreso = filter_var(
        $_POST['volver_a_funcion'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    $peliculaRegresoValida = preg_match('/^[a-z0-9-]+$/D', $peliculaRegreso) === 1;
    $destinoExitoso = $peliculaRegresoValida
        ? 'detalle-pelicula.php?pelicula=' . rawurlencode($peliculaRegreso)
        : 'index.php';
    if ($peliculaRegresoValida && $funcionRegreso !== false) {
        $destinoExitoso .= '&funcion=' . $funcionRegreso;
    }
    $destinoError = '../frontend/html/login.html?error=Email%20o%20contrase%C3%B1a%20incorrectos.';
    if ($peliculaRegresoValida) {
        $destinoError .= '&pelicula=' . rawurlencode($peliculaRegreso);
        if ($funcionRegreso !== false) {
            $destinoError .= '&funcion=' . $funcionRegreso;
        }
    }

    if ($email === '' || $password === '') {
        $destinoError = '../frontend/html/login.html?error=Complet%C3%A1%20todos%20los%20campos.';
        if ($peliculaRegresoValida) {
            $destinoError .= '&pelicula=' . rawurlencode($peliculaRegreso);
            if ($funcionRegreso !== false) {
                $destinoError .= '&funcion=' . $funcionRegreso;
            }
        }
        header("Location: " . $destinoError);
        exit;
    }

    /* PRIMERO BUSCAMOS EN LA TABLA USUARIOS*/
    $sql = "SELECT * FROM usuarios WHERE email = :email ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);

    $usuario = $stmt->fetch();

    if ($usuario && password_verify($password, $usuario['contrasena'])) {

        session_regenerate_id(true);
        $_SESSION['tipo'] = 'usuario';
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['email'] = $usuario['email'];

        header("Location: " . $destinoExitoso);
        exit;
    }
    /* SI NO ES USUARIO, BUSCAMOS EN ADMINISTRADORES*/
    $sql = "SELECT * FROM empleado WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);

    $empleado = $stmt->fetch();

    if ($empleado && password_verify($password, $empleado['contraseña'])) {

        session_regenerate_id(true);
        $_SESSION['tipo'] = 'empleado';
        $_SESSION['id_empleado'] = $empleado['id_empleado'];
        $_SESSION['nombre'] = $empleado['nombre'];
        $_SESSION['email'] = $empleado['email'];

        header("Location: " . $destinoExitoso);
        exit;
    }
    /* SI NO COINCIDE CON NINGUNA TABLA*/
    header("Location: " . $destinoError);
    exit;
}
header("Location: index.php");
exit;
?>