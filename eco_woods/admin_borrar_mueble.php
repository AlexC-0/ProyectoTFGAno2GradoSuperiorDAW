<?php
/*
DOCUMENTACION_EXPLICATIVA_TFG
Que hace: Elimina un mueble desde el panel admin.
Por que se hizo asi: Se hace con comprobaciones previas y transaccion para no dejar datos a medias.
Para que sirve: Permite limpieza de contenido con consistencia en base de datos.
*/
require_once __DIR__ . '/includes/bootstrap.php';
require 'conexion.php';

/*BORRAR*/

if (!isset($_SESSION['es_admin']) || (int)$_SESSION['es_admin'] !== 1) {
    die("Acceso denegado");
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin.php?seccion=muebles");
    exit;
}
if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    header("Location: admin.php?seccion=muebles");
    exit;
}
if (!isset($_POST['id_mueble'])) {
    header("Location: admin.php?seccion=muebles");
    exit;
}

$id_mueble = (int)$_POST['id_mueble'];

$stmt_check = mysqli_prepare($conexion, "SELECT id_mueble FROM muebles WHERE id_mueble = ? LIMIT 1");
if (!$stmt_check) {
    header("Location: admin.php?seccion=muebles");
    exit;
}
mysqli_stmt_bind_param($stmt_check, 'i', $id_mueble);
mysqli_stmt_execute($stmt_check);
$res_mueble = mysqli_stmt_get_result($stmt_check);
mysqli_stmt_close($stmt_check);

if (!$res_mueble || mysqli_num_rows($res_mueble) === 0) {
    header("Location: admin.php?seccion=muebles");
    exit;
}

mysqli_begin_transaction($conexion);
try {
    $stmt1 = mysqli_prepare($conexion, "DELETE FROM resenas WHERE id_mueble = ?");
    $stmt2 = mysqli_prepare($conexion, "DELETE FROM favoritos WHERE id_mueble = ?");
    $stmt3 = mysqli_prepare($conexion, "DELETE FROM muebles WHERE id_mueble = ?");
    if (!$stmt1 || !$stmt2 || !$stmt3) {
        throw new RuntimeException('prepare');
    }

    mysqli_stmt_bind_param($stmt1, 'i', $id_mueble);
    mysqli_stmt_bind_param($stmt2, 'i', $id_mueble);
    mysqli_stmt_bind_param($stmt3, 'i', $id_mueble);

    if (!mysqli_stmt_execute($stmt1) || !mysqli_stmt_execute($stmt2) || !mysqli_stmt_execute($stmt3)) {
        throw new RuntimeException('execute');
    }

    mysqli_stmt_close($stmt1);
    mysqli_stmt_close($stmt2);
    mysqli_stmt_close($stmt3);
    mysqli_commit($conexion);
} catch (Throwable $e) {
    if (isset($stmt1) && $stmt1 instanceof mysqli_stmt) { mysqli_stmt_close($stmt1); }
    if (isset($stmt2) && $stmt2 instanceof mysqli_stmt) { mysqli_stmt_close($stmt2); }
    if (isset($stmt3) && $stmt3 instanceof mysqli_stmt) { mysqli_stmt_close($stmt3); }
    mysqli_rollback($conexion);
}

header("Location: admin.php?seccion=muebles");
exit;

