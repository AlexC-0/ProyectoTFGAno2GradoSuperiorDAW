<?php
/*
DOCUMENTACION_EXPLICATIVA_TFG
Que hace: Alterna favorito en recambios 3D.
Por que se hizo asi: Replica el comportamiento de favoritos de muebles con mismas garantias de seguridad.
Para que sirve: Mantiene una experiencia coherente entre catalogos.
*/
/*
DOCUMENTACION_PASO4
Endpoint de favoritos especifico para recambios 3D.
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

function tableExists(mysqli $conexion, string $tabla): bool
{
    $stmt = mysqli_prepare(
        $conexion,
        "SELECT 1
         FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
         LIMIT 1"
    );
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, 's', $tabla);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return false;
    }
    $res = mysqli_stmt_get_result($stmt);
    $ok = ($res && mysqli_num_rows($res) > 0);
    mysqli_stmt_close($stmt);
    return $ok;
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

if (!tableExists($conexion, 'favoritos_recambios')) {
    ew_json_error('La tabla favoritos_recambios aun no existe en la base de datos.', 501);
}

$id_usuario = (int)$_SESSION['usuario_id'];
$id_recambio = ew_post_int('id_recambio');
if ($id_recambio <= 0) {
    ew_json_error('Recambio no valido.', 400);
}

$res_check = ew_stmt_result(
    $conexion,
    "SELECT id_recambio FROM recambios3d WHERE id_recambio = ? LIMIT 1",
    'i',
    [$id_recambio]
);
if (!$res_check || mysqli_num_rows($res_check) === 0) {
    ew_json_error('El recambio no existe.', 404);
}

$res_exist = ew_stmt_result(
    $conexion,
    "SELECT id_favorito FROM favoritos_recambios WHERE id_usuario = ? AND id_recambio = ? LIMIT 1",
    'ii',
    [$id_usuario, $id_recambio]
);

if ($res_exist && mysqli_num_rows($res_exist) > 0) {
    $fila = mysqli_fetch_assoc($res_exist);
    $id_favorito = (int)$fila['id_favorito'];

    $ok_del = ew_stmt_execute(
        $conexion,
        "DELETE FROM favoritos_recambios WHERE id_favorito = ?",
        'i',
        [$id_favorito]
    );
    if (!$ok_del) {
        ew_json_error('Error al quitar de favoritos.', 500);
    }

    ew_json([
        'ok' => true,
        'es_favorito' => false,
        'message' => 'Quitado de favoritos.'
    ]);
}

$ok_ins = ew_stmt_execute(
    $conexion,
    "INSERT INTO favoritos_recambios (id_usuario, id_recambio, fecha_guardado) VALUES (?, ?, NOW())",
    'ii',
    [$id_usuario, $id_recambio]
);

if (!$ok_ins) {
    $ok_ins2 = ew_stmt_execute(
        $conexion,
        "INSERT INTO favoritos_recambios (id_usuario, id_recambio) VALUES (?, ?)",
        'ii',
        [$id_usuario, $id_recambio]
    );
    if (!$ok_ins2) {
        ew_json_error('Error al anadir a favoritos.', 500);
    }
}

ew_json([
    'ok' => true,
    'es_favorito' => true,
    'message' => 'Anadido a favoritos.'
]);

