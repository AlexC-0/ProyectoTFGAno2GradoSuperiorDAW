<?php
require '../conexion.php';

// Indicamos que la respuesta es JSON
header('Content-Type: application/json; charset=utf-8');

$respuesta = [
    'ok'      => false,
    'resenas' => [],
    'error'   => ''
];

// Comprobar que llega id_mueble por GET
if (!isset($_GET['id_mueble'])) {
    $respuesta['error'] = 'Parámetro id_mueble obligatorio';
    echo json_encode($respuesta);
    exit;
}

$id_mueble = (int)$_GET['id_mueble'];

if ($id_mueble <= 0) {
    $respuesta['error'] = 'id_mueble no válido';
    echo json_encode($respuesta);
    exit;
}

// Consulta de reseñas del mueble
$sql = "SELECT r.id_resena,
               r.id_mueble,
               r.id_usuario,
               r.puntuacion,
               r.comentario,
               r.fecha_resena,
               u.nombre AS nombre_usuario
        FROM resenas r
        JOIN usuarios u ON r.id_usuario = u.id_usuario
        WHERE r.id_mueble = $id_mueble
        ORDER BY r.fecha_resena DESC";

$resultado = mysqli_query($conexion, $sql);

if ($resultado && mysqli_num_rows($resultado) > 0) {
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $fila['id_resena']  = (int)$fila['id_resena'];
        $fila['id_mueble']  = (int)$fila['id_mueble'];
        $fila['id_usuario'] = (int)$fila['id_usuario'];
        $fila['puntuacion'] = (int)$fila['puntuacion'];

        $respuesta['resenas'][] = $fila;
    }

    $respuesta['ok'] = true;
} else {
    // Sin reseñas no es un error como tal
    $respuesta['ok']      = true;
    $respuesta['resenas'] = [];
}

echo json_encode($respuesta);
