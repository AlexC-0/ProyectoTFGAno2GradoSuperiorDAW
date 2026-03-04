<?php
/*
DOCUMENTACION_EXPLICATIVA_TFG
Que hace: Actualiza la cantidad de una linea del carrito.
Por que se hizo asi: Valida valores y controla limites para evitar estados invalidos.
Para que sirve: Hace que el carrito refleje la intencion real de compra.
*/
/*
DOCUMENTACION_PASO4
Endpoint para ajustar cantidad de un item del carrito.
- Requiere login, POST y CSRF.
- Permite accion mas/menos con validacion de pertenencia.
- Devuelve subtotal y estado para actualizar la interfaz.
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
$accion = ew_post_string('accion');

if ($id_item <= 0 || ($accion !== 'mas' && $accion !== 'menos')) {
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

$res_item = ew_stmt_result(
    $conexion,
    "SELECT ci.id_item, ci.cantidad, ci.id_recambio, ci.id_mueble,
            r.precio AS precio_recambio,
            m.precio AS precio_mueble
     FROM carrito_items ci
     LEFT JOIN recambios3d r ON ci.id_recambio = r.id_recambio
     LEFT JOIN muebles m ON ci.id_mueble = m.id_mueble
     WHERE ci.id_item = ? AND ci.id_carrito = ?
     LIMIT 1",
    'ii',
    [$id_item, $id_carrito]
);
if (!$res_item || mysqli_num_rows($res_item) === 0) {
    ew_json_error('El item no existe en tu carrito.', 404);
}

$item = mysqli_fetch_assoc($res_item);
$cantidad = (int)$item['cantidad'];
$cantidad_nueva = ($accion === 'mas') ? ($cantidad + 1) : ($cantidad - 1);

if ($cantidad_nueva <= 0) {
    $ok_del = ew_stmt_execute(
        $conexion,
        "DELETE FROM carrito_items WHERE id_item = ? AND id_carrito = ?",
        'ii',
        [$id_item, $id_carrito]
    );
    if (!$ok_del) {
        ew_json_error('Error al eliminar el producto.', 500);
    }

    ew_json([
        'ok' => true,
        'eliminado' => true,
        'message' => 'Producto eliminado del carrito.',
    ]);
}

$ok_up = ew_stmt_execute(
    $conexion,
    "UPDATE carrito_items SET cantidad = ? WHERE id_item = ? AND id_carrito = ?",
    'iii',
    [$cantidad_nueva, $id_item, $id_carrito]
);
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

