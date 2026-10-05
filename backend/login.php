<?php
session_start();
require_once 'conexion.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        header("Location: ../frontend/html/login.html?error=Completá todos los campos.");
        exit;
    }

    /* PRIMERO BUSCAMOS EN LA TABLA USUARIOS*/
    $sql = "SELECT * FROM usuarios WHERE email = :email ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);

    $usuario = $stmt->fetch();

    if ($usuario && password_verify($password, $usuario['contrasena'])) {

        $_SESSION['tipo'] = 'usuario';
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['email'] = $usuario['email'];

        header("Location: ../frontend/html/index.html");
        exit;
    }
    /* SI NO ES USUARIO, BUSCAMOS EN ADMINISTRADORES*/
    $sql = "SELECT * FROM empleado WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);

    $empleado = $stmt->fetch();

    if ($empleado && password_verify($password, $empleado['contraseña'])) {

        $_SESSION['tipo'] = 'empleado';
        $_SESSION['id_empleado'] = $empleado['id_empleado'];
        $_SESSION['nombre'] = $empleado['nombre'];
        $_SESSION['email'] = $empleado['email'];

        // IMPORTANTE:
        // El administrador NO va directamente al panel.
        // También vuelve a la página principal.
        header("Location: ../frontend/html/user.php");
        exit;
    }
    /* SI NO COINCIDE CON NINGUNA TABLA*/
    header("Location: ../frontend/html/login.html?error=Email o contraseña incorrectos.");
    exit;
}
header("Location: ../frontend/html/index.html");
exit;
?>