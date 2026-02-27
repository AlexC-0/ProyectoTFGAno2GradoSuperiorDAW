<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../includes/http.php';
require_once __DIR__ . '/../includes/validators.php';

$id_mueble = ew_get_int('id_mueble');
if ($id_mueble <= 0) {
    ew_json(['ok' => false, 'resenas' => [], 'error' => 'Parametro id_mueble obligatorio'], 400);
}

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
