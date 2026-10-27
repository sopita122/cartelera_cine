<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

$autenticado = isset($_SESSION['tipo']) && in_array($_SESSION['tipo'], ['usuario', 'empleado'], true);

echo json_encode([
    'autenticado' => $autenticado,
    'usuario' => $autenticado ? [
        'tipo' => $_SESSION['tipo'],
        'nombre' => $_SESSION['nombre'] ?? '',
        'apellido' => $_SESSION['apellido'] ?? '',
        'email' => $_SESSION['email'] ?? '',
    ] : null,
], JSON_UNESCAPED_UNICODE);
