<?php
/*
DOCUMENTACION_PASO4
Endpoint unificado de favoritos para muebles y recambios.
- Solo permite POST autenticado con CSRF.
- Comprueba existencia del producto antes de actuar.
- Ejecuta toggle anadir/quitar y devuelve estado final.
*/
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/http.php';
require_once __DIR__ . '/includes/validators.php';
require_once 'conexion.php';

// POST-only para evitar cambios de estado por URL (GET).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ew_json_error('Metodo no permitido.', 405);
}

if (!ew_is_logged_in()) {
    ew_json_error('Debes iniciar sesion para usar favoritos.', 401);
}

if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    ew_json_error('Sesion expirada. Recarga la pagina e intentalo de nuevo.', 419);
}

$source = $_POST;
$id_usuario = (int)$_SESSION['usuario_id'];

// Endpoint unificado para favoritos de mueble/recambio.
// Se determina tipo a partir de parametro recibido.
$tipo = null;
$id_producto = 0;

if (isset($source['id_mueble'])) {
    $tipo = 'mueble';
    $id_producto = (int)$source['id_mueble'];
} elseif (isset($source['id_recambio'])) {
    $tipo = 'recambio';
    $id_producto = (int)$source['id_recambio'];
} else {
    ew_json_error('Producto no especificado.', 400);
}

if ($id_producto <= 0) {
    ew_json_error('Producto no valido.', 400);
}

if ($tipo === 'mueble') {
    $sql_check = "SELECT id_mueble FROM muebles WHERE id_mueble = $id_producto LIMIT 1";
} else {
    $sql_check = "SELECT id_recambio FROM recambios3d WHERE id_recambio = $id_producto LIMIT 1";
}

$res_check = mysqli_query($conexion, $sql_check);
if (!$res_check || mysqli_num_rows($res_check) === 0) {
    ew_json_error('El producto no existe.', 404);
}

if ($tipo === 'mueble') {
    $sql_exist = "SELECT 1 FROM favoritos WHERE id_usuario = $id_usuario AND id_mueble = $id_producto LIMIT 1";
    $res_exist = mysqli_query($conexion, $sql_exist);

    if ($res_exist && mysqli_num_rows($res_exist) > 0) {
        $sql_del = "DELETE FROM favoritos WHERE id_usuario = $id_usuario AND id_mueble = $id_producto LIMIT 1";
        $ok = mysqli_query($conexion, $sql_del);
        if (!$ok) {
            ew_json_error('Error al quitar de favoritos.', 500);
        }
        ew_json_ok('Quitado de favoritos.', ['action' => 'removed']);
    }

    $sql_ins = "INSERT INTO favoritos (id_usuario, id_mueble, fecha_guardado)
                VALUES ($id_usuario, $id_producto, NOW())";
    $ok = mysqli_query($conexion, $sql_ins);
    if (!$ok) {
        ew_json_error('Error al anadir a favoritos.', 500);
    }
    ew_json_ok('Anadido a favoritos.', ['action' => 'added']);
}

// Lado recambio con la misma estrategia de toggle:
// existe -> delete, no existe -> insert.
$sql_exist = "SELECT 1 FROM favoritos WHERE id_usuario = $id_usuario AND id_recambio = $id_producto LIMIT 1";
$res_exist = mysqli_query($conexion, $sql_exist);

if ($res_exist && mysqli_num_rows($res_exist) > 0) {
    $sql_del = "DELETE FROM favoritos WHERE id_usuario = $id_usuario AND id_recambio = $id_producto LIMIT 1";
    $ok = mysqli_query($conexion, $sql_del);
    if (!$ok) {
        ew_json_error('Error al quitar de favoritos.', 500);
    }
    ew_json_ok('Quitado de favoritos.', ['action' => 'removed']);
}

$sql_ins = "INSERT INTO favoritos (id_usuario, id_recambio, fecha_guardado)
            VALUES ($id_usuario, $id_producto, NOW())";
$ok = mysqli_query($conexion, $sql_ins);
if (!$ok) {
    ew_json_error('Error al anadir a favoritos.', 500);
}

ew_json_ok('Anadido a favoritos.', ['action' => 'added']);

