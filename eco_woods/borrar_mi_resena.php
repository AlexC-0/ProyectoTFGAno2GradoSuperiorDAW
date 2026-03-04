<?php
/*
DOCUMENTACION_EXPLICATIVA_TFG
Que hace: Permite al usuario eliminar una reseña propia.
Por que se hizo asi: Se comprueba propiedad del recurso antes de borrar.
Para que sirve: Da control al usuario sobre su propio contenido.
*/
require_once __DIR__ . '/includes/bootstrap.php';
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

/*BORRAR*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: mi_perfil.php");
    exit;
}

if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    header("Location: mi_perfil.php");
    exit;
}

if (!isset($_POST['id_resena'])) {
    header("Location: mi_perfil.php");
    exit;
}

$id_usuario = (int)$_SESSION['usuario_id'];
$id_resena  = (int)$_POST['id_resena'];

$stmt = mysqli_prepare(
    $conexion,
    "DELETE FROM resenas
     WHERE id_resena = ? AND id_usuario = ?
     LIMIT 1"
);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'ii', $id_resena, $id_usuario);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

header("Location: mi_perfil.php");
exit;

