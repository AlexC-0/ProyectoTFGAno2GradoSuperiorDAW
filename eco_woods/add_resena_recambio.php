<?php
session_start();
require_once "conexion.php";

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Método no permitido.']);
    exit;
}

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Debes iniciar sesión para escribir una reseña.']);
    exit;
}

$id_usuario = (int)$_SESSION['usuario_id'];
$id_recambio = isset($_GET['id_recambio']) ? (int)$_GET['id_recambio'] : 0;

$puntuacion = isset($_POST['puntuacion']) ? (int)$_POST['puntuacion'] : 0;
$comentario = trim($_POST['comentario'] ?? '');

if ($id_recambio <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Recambio no válido.']);
    exit;
}

if ($puntuacion < 1 || $puntuacion > 5 || $comentario === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Debes indicar una puntuación entre 1 y 5 y escribir un comentario.']);
    exit;
}

$comentario_esc = mysqli_real_escape_string($conexion, $comentario);

$sql_ins = "INSERT INTO resenas_recambios (id_usuario, id_recambio, puntuacion, comentario)
            VALUES ($id_usuario, $id_recambio, $puntuacion, '$comentario_esc')";

$ok = mysqli_query($conexion, $sql_ins);

if (!$ok) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Error al guardar la reseña: ' . mysqli_error($conexion)]);
    exit;
}

$id_resena = (int)mysqli_insert_id($conexion);

$sql_get = "SELECT rr.id_resena, rr.puntuacion, rr.comentario, rr.fecha_resena, u.nombre AS nombre_usuario
            FROM resenas_recambios rr
            JOIN usuarios u ON rr.id_usuario = u.id_usuario
            WHERE rr.id_resena = $id_resena
            LIMIT 1";

$res_get = mysqli_query($conexion, $sql_get);
$row = ($res_get && mysqli_num_rows($res_get) > 0) ? mysqli_fetch_assoc($res_get) : null;

echo json_encode([
    'ok' => true,
    'message' => 'Reseña guardada correctamente.',
    'resena' => $row
]);
