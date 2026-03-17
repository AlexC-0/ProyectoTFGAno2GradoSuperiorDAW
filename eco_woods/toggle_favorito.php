<?php
/*
DOCUMENTACION_EXPLICATIVA_TFG
Que hace: Alterna estado favorito de un mueble.
Por que se hizo asi: Si existe lo quita y si no existe lo crea, en un flujo simple y seguro.
Para que sirve: Ayuda al usuario a guardar productos para revisarlos luego.
*/
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ew_json_error('Metodo no permitido.', 405);
}
if (!ew_is_logged_in()) {
    ew_json_error('Debes iniciar sesion para usar favoritos.', 401);
}
if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    ew_json_error('Sesion expirada. Recarga la pagina e intentalo de nuevo.', 419);
}

/*BORRAR*/

$id_usuario = (int)$_SESSION['usuario_id'];
$tipo = null;
$id_producto = 0;

if (isset($_POST['id_mueble'])) {
    $tipo = 'mueble';
    $id_producto = (int)$_POST['id_mueble'];
} elseif (isset($_POST['id_recambio'])) {
    $tipo = 'recambio';
    $id_producto = (int)$_POST['id_recambio'];
} else {
    ew_json_error('Producto no especificado.', 400);
}

if ($id_producto <= 0) {
    ew_json_error('Producto no valido.', 400);
}

function ew_stmt_result(mysqli $conexion, string $sql, string $types = '', array $params = [])
{
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return false;
    }
    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return false;
    }
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

function ew_stmt_execute(mysqli $conexion, string $sql, string $types, array $params): bool
{
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

if ($tipo === 'mueble') {
    $res_check = ew_stmt_result(
        $conexion,
        "SELECT id_mueble FROM muebles WHERE id_mueble = ? LIMIT 1",
        'i',
        [$id_producto]
    );
} else {
    $res_check = ew_stmt_result(
        $conexion,
        "SELECT id_recambio FROM recambios3d WHERE id_recambio = ? LIMIT 1",
        'i',
        [$id_producto]
    );
}

if (!$res_check || mysqli_num_rows($res_check) === 0) {
    ew_json_error('El producto no existe.', 404);
}

if ($tipo === 'mueble') {
    $res_exist = ew_stmt_result(
        $conexion,
        "SELECT 1 FROM favoritos WHERE id_usuario = ? AND id_mueble = ? LIMIT 1",
        'ii',
        [$id_usuario, $id_producto]
    );

    if ($res_exist && mysqli_num_rows($res_exist) > 0) {
        $ok = ew_stmt_execute(
            $conexion,
            "DELETE FROM favoritos WHERE id_usuario = ? AND id_mueble = ? LIMIT 1",
            'ii',
            [$id_usuario, $id_producto]
        );
        if (!$ok) {
            ew_json_error('Error al quitar de favoritos.', 500);
        }
        ew_json_ok('Quitado de favoritos.', ['action' => 'removed']);
    }

    $ok = ew_stmt_execute(
        $conexion,
        "INSERT INTO favoritos (id_usuario, id_mueble, fecha_guardado) VALUES (?, ?, NOW())",
        'ii',
        [$id_usuario, $id_producto]
    );
    if (!$ok) {
        ew_json_error('Error al anadir a favoritos.', 500);
    }
    ew_json_ok('Anadido a favoritos.', ['action' => 'added']);
}

$res_exist = ew_stmt_result(
    $conexion,
    "SELECT 1 FROM favoritos WHERE id_usuario = ? AND id_recambio = ? LIMIT 1",
    'ii',
    [$id_usuario, $id_producto]
);

if ($res_exist && mysqli_num_rows($res_exist) > 0) {
    $ok = ew_stmt_execute(
        $conexion,
        "DELETE FROM favoritos WHERE id_usuario = ? AND id_recambio = ? LIMIT 1",
        'ii',
        [$id_usuario, $id_producto]
    );
    if (!$ok) {
        ew_json_error('Error al quitar de favoritos.', 500);
    }
    ew_json_ok('Quitado de favoritos.', ['action' => 'removed']);
}

$ok = ew_stmt_execute(
    $conexion,
    "INSERT INTO favoritos (id_usuario, id_recambio, fecha_guardado) VALUES (?, ?, NOW())",
    'ii',
    [$id_usuario, $id_producto]
);
if (!$ok) {
    ew_json_error('Error al anadir a favoritos.', 500);
}

ew_json_ok('Anadido a favoritos.', ['action' => 'added']);

