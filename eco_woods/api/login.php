<?php
require '../conexion.php';

header('Content-Type: application/json; charset=utf-8');

$respuesta = [
    'ok'      => false,
    'errores' => [],
    'usuario' => null
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $respuesta['errores'][] = 'Método no permitido. Usa POST.';
    echo json_encode($respuesta);
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    $respuesta['errores'][] = 'Debes introducir email y contraseña.';
    echo json_encode($respuesta);
    exit;
}

// Mismo SQL que en login.php (incluyendo es_admin)
$sql = "SELECT id_usuario, nombre, email, password, es_admin 
        FROM usuarios 
        WHERE email = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario   = $resultado->fetch_assoc();
$stmt->close();

if ($usuario && password_verify($password, $usuario['password'])) {
    $respuesta['ok'] = true;
    $respuesta['usuario'] = [
        'id_usuario' => (int)$usuario['id_usuario'],
        'nombre'     => $usuario['nombre'],
        'email'      => $usuario['email'],
        'es_admin'   => (int)$usuario['es_admin']
    ];
} else {
    $respuesta['errores'][] = 'Email o contraseña incorrectos.';
}

echo json_encode($respuesta);
