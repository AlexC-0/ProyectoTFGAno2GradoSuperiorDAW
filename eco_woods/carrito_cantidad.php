<?php
session_start();
require_once "conexion.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(["ok" => false, "message" => "Debes iniciar sesión."]);
    exit;
}

$id_usuario = (int)$_SESSION['usuario_id'];
$id_item = isset($_POST['id_item']) ? (int)$_POST['id_item'] : 0;
$accion = $_POST['accion'] ?? '';

if ($id_item <= 0 || ($accion !== 'mas' && $accion !== 'menos')) {
    http_response_code(400);
    echo json_encode(["ok" => false, "message" => "Datos no válidos."]);
    exit;
}

$sql_carrito = "SELECT id_carrito FROM carritos
                WHERE id_usuario = $id_usuario AND estado = 'activo'
                LIMIT 1";
$res_carrito = mysqli_query($conexion, $sql_carrito);

if (!$res_carrito || mysqli_num_rows($res_carrito) == 0) {
    http_response_code(404);
    echo json_encode(["ok" => false, "message" => "No tienes carrito activo."]);
    exit;
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

if (!$res_item || mysqli_num_rows($res_item) == 0) {
    http_response_code(404);
    echo json_encode(["ok" => false, "message" => "El item no existe en tu carrito."]);
    exit;
}

$item = mysqli_fetch_assoc($res_item);
$cantidad = (int)$item['cantidad'];

if ($accion === 'mas') {
    $cantidad_nueva = $cantidad + 1;
} else {
    $cantidad_nueva = $cantidad - 1;
}

if ($cantidad_nueva <= 0) {
    $sql_del = "DELETE FROM carrito_items WHERE id_item = $id_item AND id_carrito = $id_carrito";
    $ok_del = mysqli_query($conexion, $sql_del);

    if (!$ok_del) {
        http_response_code(500);
        echo json_encode(["ok" => false, "message" => "Error al eliminar el producto."]);
        exit;
    }

    echo json_encode([
        "ok" => true,
        "eliminado" => true,
        "message" => "Producto eliminado del carrito."
    ]);
    exit;
}

$sql_up = "UPDATE carrito_items
           SET cantidad = $cantidad_nueva
           WHERE id_item = $id_item AND id_carrito = $id_carrito";
$ok_up = mysqli_query($conexion, $sql_up);

if (!$ok_up) {
    http_response_code(500);
    echo json_encode(["ok" => false, "message" => "Error al actualizar la cantidad."]);
    exit;
}

$precio = 0;
if (!empty($item['id_recambio'])) {
    $precio = (float)$item['precio_recambio'];
} elseif (!empty($item['id_mueble'])) {
    $precio = (float)$item['precio_mueble'];
}
$subtotal = $precio * $cantidad_nueva;

echo json_encode([
    "ok" => true,
    "eliminado" => false,
    "cantidad" => $cantidad_nueva,
    "subtotal" => $subtotal,
    "message" => "Cantidad actualizada."
]);
