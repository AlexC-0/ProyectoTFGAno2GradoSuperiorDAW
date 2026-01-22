<?php
require '../conexion.php';

header('Content-Type: application/json; charset=utf-8');

$respuesta = [
    'ok'      => false,
    'errores' => [],
    'mensaje' => ''
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $respuesta['errores'][] = 'Método no permitido. Usa POST.';
    echo json_encode($respuesta);
    exit;
}

$nombre    = trim($_POST['nombre'] ?? '');
$email     = trim($_POST['email'] ?? '');
$password  = $_POST['password']  ?? '';
$password2 = $_POST['password2'] ?? '';
$telefono  = trim($_POST['telefono']  ?? '');
$provincia = trim($_POST['provincia'] ?? '');
$localidad = trim($_POST['localidad'] ?? '');

// Validaciones básicas (igual que en registro.php)
if ($nombre === '' || $email === '' || $password === '' || $password2 === '') {
    $respuesta['errores'][] = 'Todos los campos obligatorios deben estar rellenos.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $respuesta['errores'][] = 'El email no tiene un formato válido.';
}

if ($password !== $password2) {
    $respuesta['errores'][] = 'Las contraseñas no coinciden.';
}

if (!empty($respuesta['errores'])) {
    echo json_encode($respuesta);
    exit;
}

// Comprobar si ya existe un usuario con ese email
$sql = "SELECT id_usuario FROM usuarios WHERE email = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $respuesta['errores'][] = 'Ya existe un usuario registrado con ese email.';
    $stmt->close();
    echo json_encode($respuesta);
    exit;
}

$stmt->close();

// Insertar el nuevo usuario (misma lógica que en registro.php)
$hash = password_hash($password, PASSWORD_DEFAULT);

$telefono_db  = ($telefono  !== '') ? $telefono  : null;
$provincia_db = ($provincia !== '') ? $provincia : null;
$localidad_db = ($localidad !== '') ? $localidad : null;

// es_admin = 0 por defecto
$sql_insert = "INSERT INTO usuarios 
               (nombre, email, password, telefono, provincia, localidad, es_admin)
               VALUES (?, ?, ?, ?, ?, ?, 0)";
$stmt_insert = $conexion->prepare($sql_insert);

$stmt_insert->bind_param(
    "ssssss",
    $nombre,
    $email,
    $hash,
    $telefono_db,
    $provincia_db,
    $localidad_db
);

if ($stmt_insert->execute()) {
    $respuesta['ok']      = true;
    $respuesta['mensaje'] = 'Registro completado correctamente. Ya puedes iniciar sesión.';
} else {
    $respuesta['errores'][] = 'Error al registrar el usuario. Inténtalo de nuevo.';
}

$stmt_insert->close();

echo json_encode($respuesta);
