<?php
/*
DOCUMENTACION_EXPLICATIVA_TFG
Que hace: Quita un item concreto del carrito.
Por que se hizo asi: Identifica la linea exacta y ejecuta borrado seguro.
Para que sirve: Permite corregir el pedido antes de finalizar compra.
*/
/*
DOCUMENTACION_PASO4
Endpoint para eliminar un item concreto del carrito.
- Requiere login, POST y CSRF.
- Verifica que el item pertenece al carrito del usuario.
- Devuelve respuesta JSON clara para feedback inmediato.
*/
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/http.php';
require_once __DIR__ . '/includes/validators.php';
require_once 'conexion.php';

/*BORRAR*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ew_json_error('Metodo no permitido.', 405);
}
if (!ew_is_logged_in()) {
    ew_json_error('Debes iniciar sesion.', 401);
}
if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    ew_json_error('Sesion expirada. Recarga la pagina e intentalo de nuevo.', 419);
}

$id_usuario = (int)$_SESSION['usuario_id'];
$id_item = ew_post_int('id_item');

if ($id_item <= 0) {
    ew_json_error('Datos no validos.', 400);
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

$res_carrito = ew_stmt_result(
    $conexion,
    "SELECT id_carrito FROM carritos WHERE id_usuario = ? AND estado = 'activo' LIMIT 1",
    'i',
    [$id_usuario]
);
if (!$res_carrito || mysqli_num_rows($res_carrito) === 0) {
    ew_json_error('No tienes carrito activo.', 404);
}

$fila_carrito = mysqli_fetch_assoc($res_carrito);
$id_carrito = (int)$fila_carrito['id_carrito'];

$stmt_del = mysqli_prepare($conexion, "DELETE FROM carrito_items WHERE id_item = ? AND id_carrito = ?");
if (!$stmt_del) {
    ew_json_error('Error al eliminar el producto.', 500);
}
mysqli_stmt_bind_param($stmt_del, 'ii', $id_item, $id_carrito);
$ok_del = mysqli_stmt_execute($stmt_del);
$affected = mysqli_stmt_affected_rows($stmt_del);
mysqli_stmt_close($stmt_del);

if (!$ok_del) {
    ew_json_error('Error al eliminar el producto.', 500);
}
if ($affected === 0) {
    ew_json_error('El item no existe en tu carrito.', 404);
}

ew_json_ok('Producto eliminado del carrito.');

