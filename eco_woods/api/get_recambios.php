<?php
// Carga conexión a BD y helper de respuesta JSON para mantener formato homogéneo.
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../includes/http.php';

// Endpoint de catálogo de recambios: devuelve datos básicos para tarjetas/listados.
$sql = "SELECT id_recambio,
               nombre,
               descripcion,
               tipo,
               compatible_con,
               precio
        FROM recambios3d
        ORDER BY id_recambio DESC";
$resultado = mysqli_query($conexion, $sql);

// Convertimos tipos numéricos de salida para no depender del casting implícito en JS.
$recambios = [];
if ($resultado && mysqli_num_rows($resultado) > 0) {
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $fila['id_recambio'] = (int)$fila['id_recambio'];
        $fila['precio'] = (float)$fila['precio'];
        $recambios[] = $fila;
    }
}

// Respuesta consistente: flag de éxito + colección (vacía si no hay resultados).
ew_json(['ok' => true, 'recambios' => $recambios]);
