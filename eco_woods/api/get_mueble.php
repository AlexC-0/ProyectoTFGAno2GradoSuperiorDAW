<?php
/*
DOCUMENTACION_EXPLICATIVA_TFG
Que hace: Devuelve el detalle de un mueble en formato JSON para consumo API.
Por que se hizo asi: Se usa lectura tipada para responder rapido y evitar consultas manipulables.
Para que sirve: Facilita integraciones y posibles clientes externos.
*/
/*
DOCUMENTACION_PASO4
API de detalle individual de mueble.
- Recibe id y devuelve anuncio completo si existe.
- Incluye validaciones de entrada y salida consistente.
- Base para vistas de detalle o clientes externos.
*/
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../includes/http.php';
require_once __DIR__ . '/../includes/validators.php';

// API de detalle de mueble por id.
// Devuelve 400 si falta/invalid id y 404 si no existe en BD.
$id_mueble = ew_get_int('id_mueble');
if ($id_mueble <= 0) {
    ew_json(['ok' => false, 'mueble' => null, 'error' => 'Parametro id_mueble obligatorio'], 400);
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
               u.nombre AS nombre_vendedor,
               u.email AS email_vendedor,
               u.telefono AS telefono_vendedor
        FROM muebles m
        JOIN usuarios u ON m.id_usuario = u.id_usuario
        WHERE m.id_mueble = ?
        LIMIT 1";
$resultado = ew_stmt_result($conexion, $sql, 'i', [$id_mueble]);

if (!$resultado || mysqli_num_rows($resultado) !== 1) {
    ew_json(['ok' => false, 'mueble' => null, 'error' => 'Mueble no encontrado'], 404);
}

$fila = mysqli_fetch_assoc($resultado);
// Normalizacion minima de tipos para consumo consistente en clientes.
$fila['id_mueble'] = (int)$fila['id_mueble'];
$fila['id_usuario'] = (int)$fila['id_usuario'];
$fila['precio'] = (float)$fila['precio'];

ew_json(['ok' => true, 'mueble' => $fila, 'error' => '']);


