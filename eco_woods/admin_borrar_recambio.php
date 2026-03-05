<?php
/*
DOCUMENTACION_EXPLICATIVA_TFG
Que hace: Elimina un recambio desde administracion.
Por que se hizo asi: Se verifica el objetivo y se usa consulta preparada para borrar sin riesgo de inyeccion.
Para que sirve: Mantiene el catalogo de recambios controlado y actualizado.
*/
require_once __DIR__ . '/includes/bootstrap.php';
require 'conexion.php';

if (!isset($_SESSION['es_admin']) || (int)$_SESSION['es_admin'] !== 1) {
    die("Acceso denegado");
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin.php?seccion=recambios");
    exit;
}
if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    header("Location: admin.php?seccion=recambios");
    exit;
}
if (!isset($_POST['id_recambio'])) {
    header("Location: admin.php?seccion=recambios");
    exit;
}

$id_recambio = (int)$_POST['id_recambio'];

$stmt_rec = mysqli_prepare($conexion, "SELECT id_recambio FROM recambios3d WHERE id_recambio = ? LIMIT 1");
if (!$stmt_rec) {
    header("Location: admin.php?seccion=recambios");
    exit;
}
mysqli_stmt_bind_param($stmt_rec, 'i', $id_recambio);
mysqli_stmt_execute($stmt_rec);
$res_rec = mysqli_stmt_get_result($stmt_rec);
mysqli_stmt_close($stmt_rec);

if (!$res_rec || mysqli_num_rows($res_rec) === 0) {
    header("Location: admin.php?seccion=recambios");
    exit;
}

$stmt_del = mysqli_prepare($conexion, "DELETE FROM recambios3d WHERE id_recambio = ?");
if ($stmt_del) {
    mysqli_stmt_bind_param($stmt_del, 'i', $id_recambio);
    mysqli_stmt_execute($stmt_del);
    mysqli_stmt_close($stmt_del);
}

header("Location: admin.php?seccion=recambios");
exit;

