<?php
require '../conexion.php';

// Indicamos que la respuesta es JSON
header('Content-Type: application/json; charset=utf-8');

$respuesta = [
    'ok'      => false,
    'muebles' => []
];

$sql = "SELECT m.id_mueble,
               m.titulo,
               m.precio,
               m.provincia,
               m.localidad,
               m.estado,
               m.categoria,
               m.fecha_publicacion,
               m.imagen,
               m.imagen2,
               m.imagen3,
               m.imagen4,
               m.imagen5,
               u.nombre AS nombre_vendedor
        FROM muebles m
        JOIN usuarios u ON m.id_usuario = u.id_usuario
        ORDER BY m.fecha_publicacion DESC";

$resultado = mysqli_query($conexion, $sql);

if ($resultado && mysqli_num_rows($resultado) > 0) {
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $fila['id_mueble'] = (int)$fila['id_mueble'];
        $fila['precio']    = (float)$fila['precio'];

        $respuesta['muebles'][] = $fila;
    }

    $respuesta['ok'] = true;
} else {
    $respuesta['ok'] = true;
    $respuesta['muebles'] = [];
}

echo json_encode($respuesta);
