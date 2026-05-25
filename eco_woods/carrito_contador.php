<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/http.php';
require_once 'conexion.php';

if (!ew_is_logged_in()) {
    ew_json_ok('Carrito sin sesion.', ['cart_count' => 0]);
}

$id_usuario = (int)$_SESSION['usuario_id'];

$stmt = mysqli_prepare(
    $conexion,
    "SELECT COALESCE(SUM(ci.cantidad), 0) AS total
     FROM carritos c
     JOIN carrito_items ci ON c.id_carrito = ci.id_carrito
     WHERE c.id_usuario = ? AND c.estado = 'activo'"
);

if (!$stmt) {
    ew_json_error('No se pudo consultar el carrito.', 500, ['cart_count' => 0]);
}

mysqli_stmt_bind_param($stmt, 'i', $id_usuario);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$fila = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($stmt);

$total = $fila ? (int)$fila['total'] : 0;

ew_json_ok('Contador del carrito actualizado.', ['cart_count' => $total]);
