<?php
// Dependencias comunes: conexión, salida JSON y validadores de entrada.
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../includes/http.php';
require_once __DIR__ . '/../includes/validators.php';

// Validación obligatoria: sin id_mueble no se puede acotar la consulta de reseñas.
$id_mueble = ew_get_int('id_mueble');
if ($id_mueble <= 0) {
    ew_json(['ok' => false, 'resenas' => [], 'error' => 'Parametro id_mueble obligatorio'], 400);
}

// Consulta de reseñas con nombre de autor para pintar el bloque social del producto.
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

// Se normalizan campos enteros para un contrato API estable y predecible.
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

// Siempre devolvemos la misma estructura para simplificar el consumo en frontend.
ew_json(['ok' => true, 'resenas' => $resenas, 'error' => '']);
