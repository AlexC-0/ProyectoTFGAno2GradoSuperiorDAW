<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/http.php';
require_once __DIR__ . '/includes/validators.php';
require_once 'conexion.php';

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
$accion = ew_post_string('accion');

if ($id_item <= 0 || ($accion !== 'mas' && $accion !== 'menos')) {
    ew_json_error('Datos no validos.', 400);
}

$sql_carrito = "SELECT id_carrito FROM carritos
                WHERE id_usuario = $id_usuario AND estado = 'activo'
                LIMIT 1";
$res_carrito = mysqli_query($conexion, $sql_carrito);

if (!$res_carrito || mysqli_num_rows($res_carrito) === 0) {
    ew_json_error('No tienes carrito activo.', 404);
}

$fila_carrito = mysqli_fetch_assoc($res_carrito);
$id_carrito = (int)$fila_carrito['id_carrito'];

$sql_item = "SELECT ci.id_item, ci.cantidad, ci.id_recambio, ci.id_mueble,
                    r.precio AS precio_recambio,
                    m.precio AS precio_mueble
             FROM carrito_items ci
             LEFT JOIN recambios3d r ON ci.id_recambio = r.id_recambio
             LEFT JOIN muebles m ON ci.id_mueble = m.id_mueble
             WHERE ci.id_item = $id_item AND ci.id_carrito = $id_carrito
             LIMIT 1";
$res_item = mysqli_query($conexion, $sql_item);

if (!$res_item || mysqli_num_rows($res_item) === 0) {
    ew_json_error('El item no existe en tu carrito.', 404);
}

$item = mysqli_fetch_assoc($res_item);
$cantidad = (int)$item['cantidad'];
$cantidad_nueva = ($accion === 'mas') ? ($cantidad + 1) : ($cantidad - 1);

if ($cantidad_nueva <= 0) {
    $sql_del = "DELETE FROM carrito_items WHERE id_item = $id_item AND id_carrito = $id_carrito";
    $ok_del = mysqli_query($conexion, $sql_del);

    if (!$ok_del) {
        ew_json_error('Error al eliminar el producto.', 500);
    }

    ew_json([
        'ok' => true,
        'eliminado' => true,
        'message' => 'Producto eliminado del carrito.',
    ]);
}

$sql_up = "UPDATE carrito_items
           SET cantidad = $cantidad_nueva
           WHERE id_item = $id_item AND id_carrito = $id_carrito";
$ok_up = mysqli_query($conexion, $sql_up);

if (!$ok_up) {
    ew_json_error('Error al actualizar la cantidad.', 500);
}

$precio = 0.0;
if (!empty($item['id_recambio'])) {
    $precio = (float)$item['precio_recambio'];
} elseif (!empty($item['id_mueble'])) {
    $precio = (float)$item['precio_mueble'];
}
$subtotal = $precio * $cantidad_nueva;

ew_json([
    'ok' => true,
    'eliminado' => false,
    'cantidad' => $cantidad_nueva,
    'subtotal' => $subtotal,
    'message' => 'Cantidad actualizada.',
]);
