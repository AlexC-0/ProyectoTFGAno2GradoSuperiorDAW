<?php
session_start();
require_once "conexion.php";

// Siempre vamos a responder en JSON (para no redirigir nunca)
header('Content-Type: application/json; charset=utf-8');

// Usuario por sesión. Si no hay sesión, NO permitimos carrito.
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode([
        "ok" => false,
        "message" => "Debes iniciar sesión para añadir al carrito."
    ]);
    exit;
}

$id_usuario = (int)$_SESSION['usuario_id'];

// Comprobar que llega el id del recambio
if (!isset($_GET['id_recambio'])) {
    http_response_code(400);
    echo json_encode([
        "ok" => false,
        "message" => "Recambio no especificado."
    ]);
    exit;
}

$id_recambio = (int)$_GET['id_recambio'];
if ($id_recambio <= 0) {
    http_response_code(400);
    echo json_encode([
        "ok" => false,
        "message" => "Recambio no válido."
    ]);
    exit;
}

// (Opcional pero recomendado) Comprobar que el recambio existe
$sql_check = "SELECT id_recambio FROM recambios3d WHERE id_recambio = $id_recambio LIMIT 1";
$res_check = mysqli_query($conexion, $sql_check);
if (!$res_check || mysqli_num_rows($res_check) == 0) {
    http_response_code(404);
    echo json_encode([
        "ok" => false,
        "message" => "El recambio no existe."
    ]);
    exit;
}

// 1) Buscar carrito activo del usuario
$sql_carrito = "SELECT id_carrito FROM carritos
                WHERE id_usuario = $id_usuario AND estado = 'activo'
                LIMIT 1";

$res_carrito = mysqli_query($conexion, $sql_carrito);

if ($res_carrito && mysqli_num_rows($res_carrito) > 0) {
    $fila_carrito = mysqli_fetch_assoc($res_carrito);
    $id_carrito = (int)$fila_carrito['id_carrito'];
} else {
    // No hay carrito activo → crear uno
    $sql_nuevo = "INSERT INTO carritos (id_usuario, estado) VALUES ($id_usuario, 'activo')";
    $ok_nuevo = mysqli_query($conexion, $sql_nuevo);

    if (!$ok_nuevo) {
        http_response_code(500);
        echo json_encode([
            "ok" => false,
            "message" => "Error al crear el carrito."
        ]);
        exit;
    }

    $id_carrito = (int)mysqli_insert_id($conexion);
}

// 2) ¿Ya existe este recambio en el carrito?
$sql_item = "SELECT id_item, cantidad FROM carrito_items
             WHERE id_carrito = $id_carrito AND id_recambio = $id_recambio
             LIMIT 1";

$res_item = mysqli_query($conexion, $sql_item);

if ($res_item && mysqli_num_rows($res_item) > 0) {
    // Ya existe → sumamos 1 a la cantidad
    $fila_item = mysqli_fetch_assoc($res_item);
    $nueva_cantidad = (int)$fila_item['cantidad'] + 1;

    $id_item = (int)$fila_item['id_item'];

    $sql_update = "UPDATE carrito_items
                   SET cantidad = $nueva_cantidad
                   WHERE id_item = $id_item";

    $ok_update = mysqli_query($conexion, $sql_update);

    if (!$ok_update) {
        http_response_code(500);
        echo json_encode([
            "ok" => false,
            "message" => "Error al actualizar la cantidad."
        ]);
        exit;
    }

    echo json_encode([
        "ok" => true,
        "message" => "Cantidad actualizada en el carrito."
    ]);
    exit;

} else {
    // No existe → lo insertamos
    $sql_insert = "INSERT INTO carrito_items (id_carrito, id_recambio, cantidad)
                   VALUES ($id_carrito, $id_recambio, 1)";

    $ok_insert = mysqli_query($conexion, $sql_insert);

    if (!$ok_insert) {
        http_response_code(500);
        echo json_encode([
            "ok" => false,
            "message" => "Error al añadir el recambio al carrito."
        ]);
        exit;
    }

    echo json_encode([
        "ok" => true,
        "message" => "Recambio añadido al carrito."
    ]);
    exit;
}
