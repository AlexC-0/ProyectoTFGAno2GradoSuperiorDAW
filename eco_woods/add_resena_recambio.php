<?php
/*
DOCUMENTACION_PASO4
Endpoint para crear resenas de recambios.
- Exige sesion activa y validacion CSRF.
- Valida puntuacion y comentario antes de insertar.
- Responde en JSON para integracion dinamica en pantalla.
*/
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/http.php';
require_once __DIR__ . '/includes/validators.php';
require_once 'conexion.php';

// Endpoint AJAX para publicar resena de recambio.
// Se aplica POST + sesion + CSRF para evitar envios forzados.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ew_json_error('Metodo no permitido.', 405);
}

if (!ew_is_logged_in()) {
    ew_json_error('Debes iniciar sesion.', 401);
}

if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    ew_json_error('Sesion expirada. Recarga la pagina e intentalo de nuevo.', 419);
}

$id_usuario = (int)$_SESSION['usuario_id'];
$id_recambio = ew_get_int('id_recambio');
$puntuacion = ew_post_int('puntuacion');
$comentario = ew_post_string('comentario');

if ($id_recambio <= 0) {
    ew_json_error('Recambio no especificado.', 400);
}

if ($puntuacion < 1 || $puntuacion > 5 || $comentario === '') {
    ew_json_error('Puntuacion (1-5) y comentario obligatorio.', 400);
}

$comentario_esc = mysqli_real_escape_string($conexion, $comentario);
$sql = "INSERT INTO resenas_recambios (id_usuario, id_recambio, puntuacion, comentario)
        VALUES ($id_usuario, $id_recambio, $puntuacion, '$comentario_esc')";
$ok = mysqli_query($conexion, $sql);

if (!$ok) {
    ew_json_error('Error al guardar la resena: ' . mysqli_error($conexion), 500);
}

$nombre_usuario = (string)($_SESSION['usuario_nombre'] ?? 'Usuario');
// Se devuelve payload listo para pintar la nueva resena en frontend
// sin recargar la pagina completa.
ew_json_ok('Resena guardada correctamente.', [
    'resena' => [
        'nombre_usuario' => $nombre_usuario,
        'puntuacion' => $puntuacion,
        'comentario' => $comentario,
        'fecha_resena' => date('Y-m-d H:i:s'),
    ],
]);

