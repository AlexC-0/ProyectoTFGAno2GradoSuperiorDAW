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

if ($id_item <= 0) {
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

$sql_del = "DELETE FROM carrito_items
            WHERE id_item = $id_item AND id_carrito = $id_carrito";
$ok_del = mysqli_query($conexion, $sql_del);

if (!$ok_del) {
    http_response_code(500);
    echo json_encode(["ok" => false, "message" => "Error al eliminar el producto."]);
    exit;
}

if (mysqli_affected_rows($conexion) === 0) {
    http_response_code(404);
    echo json_encode(["ok" => false, "message" => "El item no existe en tu carrito."]);
    exit;
}

echo json_encode(["ok" => true, "message" => "Producto eliminado del carrito."]);
