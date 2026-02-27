<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../includes/http.php';
require_once __DIR__ . '/../includes/validators.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ew_json(['ok' => false, 'errores' => ['Metodo no permitido. Usa POST.'], 'mensaje' => ''], 405);
}

$nombre = ew_post_string('nombre');
$email = ew_post_string('email');
$password = (string)($_POST['password'] ?? '');
$password2 = (string)($_POST['password2'] ?? '');
$telefono = ew_post_string('telefono');
$provincia = ew_post_string('provincia');
$localidad = ew_post_string('localidad');

$errores = [];
if ($nombre === '' || $email === '' || $password === '' || $password2 === '') {
    $errores[] = 'Todos los campos obligatorios deben estar rellenos.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errores[] = 'El email no tiene un formato valido.';
}
if ($password !== $password2) {
    $errores[] = 'Las contrasenas no coinciden.';
}
if (!empty($errores)) {
    ew_json(['ok' => false, 'errores' => $errores, 'mensaje' => ''], 400);
}

$sql = "SELECT id_usuario FROM usuarios WHERE email = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->close();
    ew_json(['ok' => false, 'errores' => ['Ya existe un usuario registrado con ese email.'], 'mensaje' => ''], 409);
}
$stmt->close();

$hash = password_hash($password, PASSWORD_DEFAULT);
$telefono_db = ($telefono !== '') ? $telefono : null;
$provincia_db = ($provincia !== '') ? $provincia : null;
$localidad_db = ($localidad !== '') ? $localidad : null;

$sql_insert = "INSERT INTO usuarios (nombre, email, password, telefono, provincia, localidad, es_admin)
               VALUES (?, ?, ?, ?, ?, ?, 0)";
$stmt_insert = $conexion->prepare($sql_insert);
$stmt_insert->bind_param('ssssss', $nombre, $email, $hash, $telefono_db, $provincia_db, $localidad_db);

if (!$stmt_insert->execute()) {
    $stmt_insert->close();
    ew_json(['ok' => false, 'errores' => ['Error al registrar el usuario. Intentalo de nuevo.'], 'mensaje' => ''], 500);
}
$stmt_insert->close();

ew_json(['ok' => true, 'errores' => [], 'mensaje' => 'Registro completado correctamente. Ya puedes iniciar sesion.']);
