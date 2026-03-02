<?php
// Dependencias comunes: sesión/utilidades, auth y helpers HTTP/validación.
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/http.php';
require_once __DIR__ . '/includes/validators.php';
require_once 'conexion.php';

// Este endpoint modifica estado, por tanto se limita a POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ew_json_error('Metodo no permitido.', 405);
}

if (!ew_is_logged_in()) {
    ew_json_error('Debes iniciar sesion para usar favoritos.', 401);
}

if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    ew_json_error('Sesion expirada. Recarga la pagina e intentalo de nuevo.', 419);
}

// Verifica existencia de tabla para evitar errores SQL opacos.
function tableExists(mysqli $conexion, string $tabla): bool
{
    $tabla_esc = mysqli_real_escape_string($conexion, $tabla);
    $sql = "SHOW TABLES LIKE '$tabla_esc'";
    $res = mysqli_query($conexion, $sql);
    return ($res && mysqli_num_rows($res) > 0);
}

if (!tableExists($conexion, 'favoritos_recambios')) {
    ew_json_error('La tabla favoritos_recambios aun no existe en la base de datos.', 501);
}

$id_usuario = (int)$_SESSION['usuario_id'];
$id_recambio = ew_post_int('id_recambio');

if ($id_recambio <= 0) {
    ew_json_error('Recambio no valido.', 400);
}

// Validación previa para no insertar referencias huérfanas.
$sql_check = "SELECT id_recambio FROM recambios3d WHERE id_recambio = $id_recambio LIMIT 1";
$res_check = mysqli_query($conexion, $sql_check);
if (!$res_check || mysqli_num_rows($res_check) === 0) {
    ew_json_error('El recambio no existe.', 404);
}

$sql_exist = "SELECT id_favorito
              FROM favoritos_recambios
              WHERE id_usuario = $id_usuario AND id_recambio = $id_recambio
              LIMIT 1";
$res_exist = mysqli_query($conexion, $sql_exist);

if ($res_exist && mysqli_num_rows($res_exist) > 0) {
    $fila = mysqli_fetch_assoc($res_exist);
    $id_favorito = (int)$fila['id_favorito'];

    $sql_del = "DELETE FROM favoritos_recambios WHERE id_favorito = $id_favorito";
    $ok_del = mysqli_query($conexion, $sql_del);
    if (!$ok_del) {
        ew_json_error('Error al quitar de favoritos.', 500);
    }

    ew_json([
        'ok' => true,
        'es_favorito' => false,
        'message' => 'Quitado de favoritos.'
    ]);
}

// Inserción principal; fallback por compatibilidad si falta columna fecha_guardado.
$sql_ins = "INSERT INTO favoritos_recambios (id_usuario, id_recambio, fecha_guardado)
            VALUES ($id_usuario, $id_recambio, NOW())";
$ok_ins = mysqli_query($conexion, $sql_ins);

if (!$ok_ins) {
    $sql_ins2 = "INSERT INTO favoritos_recambios (id_usuario, id_recambio)
                 VALUES ($id_usuario, $id_recambio)";
    $ok_ins2 = mysqli_query($conexion, $sql_ins2);

    if (!$ok_ins2) {
        ew_json_error('Error al anadir a favoritos.', 500);
    }
}

ew_json([
    'ok' => true,
    'es_favorito' => true,
    'message' => 'Anadido a favoritos.'
]);
