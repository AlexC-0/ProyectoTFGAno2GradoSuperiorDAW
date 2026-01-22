<?php
require '../conexion.php';

// Indicamos que la respuesta es JSON
header('Content-Type: application/json; charset=utf-8');

$respuesta = [
    'ok'     => false,
    'mueble' => null,
    'error'  => ''
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

// Consulta del mueble (un solo registro)
$sql = "SELECT m.id_mueble,
               m.id_usuario,
               m.titulo,
               m.descripcion,
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
               u.nombre   AS nombre_vendedor,
               u.email    AS email_vendedor,
               u.telefono AS telefono_vendedor
        FROM muebles m
        JOIN usuarios u ON m.id_usuario = u.id_usuario
        WHERE m.id_mueble = $id_mueble
        LIMIT 1";

$resultado = mysqli_query($conexion, $sql);

if ($resultado && mysqli_num_rows($resultado) === 1) {
    $fila = mysqli_fetch_assoc($resultado);

    // Conversión de tipos básicos
    $fila['id_mueble']   = (int)$fila['id_mueble'];
    $fila['id_usuario']  = (int)$fila['id_usuario'];
    $fila['precio']      = (float)$fila['precio'];

    $respuesta['ok']     = true;
    $respuesta['mueble'] = $fila;
} else {
    $respuesta['ok']    = false;
    $respuesta['error'] = 'Mueble no encontrado';
}

echo json_encode($respuesta);
