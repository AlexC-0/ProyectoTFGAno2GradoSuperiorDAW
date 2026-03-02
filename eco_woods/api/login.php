<?php
/*
DOCUMENTACION_PASO4
API de login para clientes externos o frontend asincrono.
- Valida credenciales y devuelve estado de autenticacion.
- Mantiene formato JSON estable para consumo seguro.
- Alineada con reglas de sesion del proyecto.
*/
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../includes/http.php';
require_once __DIR__ . '/../includes/validators.php';

// API de login para cliente externo (app/web desacoplada).
// No abre sesion PHP: devuelve datos de usuario en JSON.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ew_json(['ok' => false, 'errores' => ['Metodo no permitido. Usa POST.'], 'usuario' => null], 405);
}

$email = ew_post_string('email');
$password = (string)($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    ew_json(['ok' => false, 'errores' => ['Debes introducir email y contrasena.'], 'usuario' => null], 400);
}

$sql = "SELECT id_usuario, nombre, email, password, es_admin
        FROM usuarios
        WHERE email = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param('s', $email);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();
$stmt->close();

if (!$usuario || !password_verify($password, $usuario['password'])) {
    ew_json(['ok' => false, 'errores' => ['Email o contrasena incorrectos.'], 'usuario' => null], 401);
}

ew_json([
    'ok' => true,
    'errores' => [],
    'usuario' => [
        'id_usuario' => (int)$usuario['id_usuario'],
        'nombre' => (string)$usuario['nombre'],
        'email' => (string)$usuario['email'],
        'es_admin' => (int)$usuario['es_admin'],
    ],
]);

