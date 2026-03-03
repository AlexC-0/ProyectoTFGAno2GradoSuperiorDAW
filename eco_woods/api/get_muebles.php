<?php
/*
DOCUMENTACION_EXPLICATIVA_TFG
Que hace: Entrega listado de muebles en JSON.
Por que se hizo asi: Centraliza la consulta de salida para que el formato sea estable.
Para que sirve: Sirve como base de consumo para frontend o terceros.
*/
/*
DOCUMENTACION_PASO4
API de listado de muebles.
*/
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../includes/http.php';

function ew_stmt_result(mysqli $conexion, string $sql)
{
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return false;
    }
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return false;
    }
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

$resultado = ew_stmt_result(
    $conexion,
    "SELECT m.id_mueble,
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
     ORDER BY m.fecha_publicacion DESC"
);

$muebles = [];
if ($resultado && mysqli_num_rows($resultado) > 0) {
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $fila['id_mueble'] = (int)$fila['id_mueble'];
        $fila['precio'] = (float)$fila['precio'];
        $muebles[] = $fila;
    }
}

ew_json(['ok' => true, 'muebles' => $muebles]);

