<?php
/*
DOCUMENTACION_EXPLICATIVA_TFG
Que hace: Borra una reseña reportada o no valida.
Por que se hizo asi: Se ejecuta con permisos de admin y con entrada tipada para evitar borrados incorrectos.
Para que sirve: Ayuda a mantener calidad y respeto en comentarios.
*/
require_once __DIR__ . '/includes/bootstrap.php';
require 'conexion.php';

if (!isset($_SESSION['es_admin']) || (int)$_SESSION['es_admin'] !== 1) {
    die("Acceso denegado");
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin.php?seccion=resenas");
    exit;
}
if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    header("Location: admin.php?seccion=resenas");
    exit;
}
if (!isset($_POST['id_resena'])) {
    header("Location: admin.php?seccion=resenas");
    exit;
}

$id_resena = (int)$_POST['id_resena'];

$stmt_check = mysqli_prepare($conexion, "SELECT id_resena FROM resenas WHERE id_resena = ? LIMIT 1");
if (!$stmt_check) {
    header("Location: admin.php?seccion=resenas");
    exit;
}
mysqli_stmt_bind_param($stmt_check, 'i', $id_resena);
mysqli_stmt_execute($stmt_check);
$res = mysqli_stmt_get_result($stmt_check);
mysqli_stmt_close($stmt_check);

if (!$res || mysqli_num_rows($res) === 0) {
    header("Location: admin.php?seccion=resenas");
    exit;
}

$stmt_del = mysqli_prepare($conexion, "DELETE FROM resenas WHERE id_resena = ?");
if ($stmt_del) {
    mysqli_stmt_bind_param($stmt_del, 'i', $id_resena);
    mysqli_stmt_execute($stmt_del);
    mysqli_stmt_close($stmt_del);
}

header("Location: admin.php?seccion=resenas");
exit;

