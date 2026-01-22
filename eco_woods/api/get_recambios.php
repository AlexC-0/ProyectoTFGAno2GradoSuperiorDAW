<?php
require '../conexion.php';

// Indicamos que la respuesta es JSON
header('Content-Type: application/json; charset=utf-8');

$respuesta = [
    'ok'        => false,
    'recambios' => []
];

$sql = "SELECT id_recambio,
               nombre,
               descripcion,
               tipo,
               compatible_con,
               precio
        FROM recambios3d
        ORDER BY id_recambio DESC";

$resultado = mysqli_query($conexion, $sql);

if ($resultado && mysqli_num_rows($resultado) > 0) {
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $fila['id_recambio'] = (int)$fila['id_recambio'];
        $fila['precio']      = (float)$fila['precio'];

        $respuesta['recambios'][] = $fila;
    }

    $respuesta['ok'] = true;
} else {
    $respuesta['ok'] = true;
    $respuesta['recambios'] = [];
}

echo json_encode($respuesta);
