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
    echo json_encode(['ok' => false, 'message' => 'Debes iniciar sesión.']);
    exit;
}

if (!isset($_GET['id_recambio'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Recambio no especificado.']);
    exit;
}

$id_usuario  = (int)$_SESSION['usuario_id'];
$id_recambio = (int)$_GET['id_recambio'];

$puntuacion = isset($_POST['puntuacion']) ? (int)$_POST['puntuacion'] : 0;
$comentario = trim($_POST['comentario'] ?? '');

if ($puntuacion < 1 || $puntuacion > 5 || $comentario === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Puntuación (1-5) y comentario obligatorio.']);
    exit;
}

$comentario_esc = mysqli_real_escape_string($conexion, $comentario);

$sql = "INSERT INTO resenas_recambios (id_usuario, id_recambio, puntuacion, comentario)
        VALUES ($id_usuario, $id_recambio, $puntuacion, '$comentario_esc')";

$ok = mysqli_query($conexion, $sql);

if (!$ok) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Error al guardar la reseña: ' . mysqli_error($conexion)]);
    exit;
}

$nombre_usuario = $_SESSION['usuario_nombre'] ?? 'Usuario';

echo json_encode([
    'ok' => true,
    'message' => 'Reseña guardada correctamente.',
    'resena' => [
        'nombre_usuario' => $nombre_usuario,
        'puntuacion' => $puntuacion,
        'comentario' => $comentario,
        'fecha_resena' => date('Y-m-d H:i:s')
    ]
]);
