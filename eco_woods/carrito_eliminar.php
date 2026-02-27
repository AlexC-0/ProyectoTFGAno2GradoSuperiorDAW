<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/http.php';
require_once __DIR__ . '/includes/validators.php';
require_once 'conexion.php';

// Eliminacion directa de item de carrito.
// Mismas garantias de seguridad que el resto de endpoints de estado.
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

$sql_carrito = "SELECT id_carrito FROM carritos
                WHERE id_usuario = $id_usuario AND estado = 'activo'
                LIMIT 1";
$res_carrito = mysqli_query($conexion, $sql_carrito);

if (!$res_carrito || mysqli_num_rows($res_carrito) === 0) {
    ew_json_error('No tienes carrito activo.', 404);
}

$fila_carrito = mysqli_fetch_assoc($res_carrito);
$id_carrito = (int)$fila_carrito['id_carrito'];

$sql_del = "DELETE FROM carrito_items
            WHERE id_item = $id_item AND id_carrito = $id_carrito";
$ok_del = mysqli_query($conexion, $sql_del);

if (!$ok_del) {
    ew_json_error('Error al eliminar el producto.', 500);
}

if (mysqli_affected_rows($conexion) === 0) {
    ew_json_error('El item no existe en tu carrito.', 404);
}

ew_json_ok('Producto eliminado del carrito.');
