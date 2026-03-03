<?php
/*
DOCUMENTACION_EXPLICATIVA_TFG
Que hace: Guarda una resena que un usuario escribe sobre un recambio.
Por que se hizo asi: Solo deja enviar reseñas validas y protege la escritura con validaciones y SQL preparado.
Para que sirve: Aporta confianza al catalogo mostrando opiniones reales y trazables.
*/
/*
DOCUMENTACION_PASO4
Endpoint para crear resenas de recambios.
*/
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/http.php';
require_once __DIR__ . '/includes/validators.php';
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ew_json_error('Metodo no permitido.', 405);
}
if (!ew_is_logged_in()) {
    ew_json_error('Debes iniciar sesion.', 401);
}
if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    ew_json_error('Sesion expirada. Recarga la pagina e intentalo de nuevo.', 419);
}

function ew_stmt_execute(mysqli $conexion, string $sql, string $types, array $params): bool
{
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
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

$ok = ew_stmt_execute(
    $conexion,
    "INSERT INTO resenas_recambios (id_usuario, id_recambio, puntuacion, comentario)
     VALUES (?, ?, ?, ?)",
    'iiis',
    [$id_usuario, $id_recambio, $puntuacion, $comentario]
);

if (!$ok) {
    ew_json_error('Error al guardar la resena.', 500);
}

$nombre_usuario = (string)($_SESSION['usuario_nombre'] ?? 'Usuario');
ew_json_ok('Resena guardada correctamente.', [
    'resena' => [
        'nombre_usuario' => $nombre_usuario,
        'puntuacion' => $puntuacion,
        'comentario' => $comentario,
        'fecha_resena' => date('Y-m-d H:i:s'),
    ],
]);

