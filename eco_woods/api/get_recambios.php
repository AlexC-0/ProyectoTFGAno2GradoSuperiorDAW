<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../includes/http.php';

$sql = "SELECT id_recambio,
               nombre,
               descripcion,
               tipo,
               compatible_con,
               precio
        FROM recambios3d
        ORDER BY id_recambio DESC";
$resultado = mysqli_query($conexion, $sql);

$recambios = [];
if ($resultado && mysqli_num_rows($resultado) > 0) {
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $fila['id_recambio'] = (int)$fila['id_recambio'];
        $fila['precio'] = (float)$fila['precio'];
        $recambios[] = $fila;
    }
}

ew_json(['ok' => true, 'recambios' => $recambios]);
