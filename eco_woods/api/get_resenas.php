<?php
/*
DOCUMENTACION_EXPLICATIVA_TFG
Que hace: Devuelve reseñas asociadas a un producto concreto.
Por que se hizo asi: Filtra por identificador valido y evita consultas abiertas.
Para que sirve: Muestra valoraciones de forma reutilizable en cualquier interfaz.
*/
/*
DOCUMENTACION_PASO4
API de resenas por mueble.
*/
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../includes/http.php';
require_once __DIR__ . '/../includes/validators.php';

$id_mueble = ew_get_int('id_mueble');
if ($id_mueble <= 0) {
    ew_json(['ok' => false, 'resenas' => [], 'error' => 'Parametro id_mueble obligatorio'], 400);
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

$resultado = ew_stmt_result(
    $conexion,
    "SELECT r.id_resena,
            r.id_mueble,
            r.id_usuario,
            r.puntuacion,
            r.comentario,
            r.fecha_resena,
            u.nombre AS nombre_usuario
     FROM resenas r
     JOIN usuarios u ON r.id_usuario = u.id_usuario
     WHERE r.id_mueble = ?
     ORDER BY r.fecha_resena DESC",
    'i',
    [$id_mueble]
);

$resenas = [];
if ($resultado && mysqli_num_rows($resultado) > 0) {
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $fila['id_resena'] = (int)$fila['id_resena'];
        $fila['id_mueble'] = (int)$fila['id_mueble'];
        $fila['id_usuario'] = (int)$fila['id_usuario'];
        $fila['puntuacion'] = (int)$fila['puntuacion'];
        $resenas[] = $fila;
    }
}

ew_json(['ok' => true, 'resenas' => $resenas, 'error' => '']);

