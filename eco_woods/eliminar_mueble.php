<?php
/*
DOCUMENTACION_EXPLICATIVA_TFG
Que hace: Elimina un mueble publicado por su propietario.
Por que se hizo asi: Usa comprobaciones de pertenencia y transaccion para proteger integridad.
Para que sirve: Facilita mantenimiento del catalogo por cada vendedor.
*/
/*
DOCUMENTACION_PASO4
Eliminacion de mueble con control de permisos.
- Solo permite accion por POST autenticado con CSRF.
- Autoriza solo propietario o administrador.
- Limpia datos relacionados para mantener integridad.
*/
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';

ew_require_login('login.php');
require 'conexion.php';

$id_usuario_sesion = (int)$_SESSION['usuario_id'];
$es_admin = ew_is_admin();


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}
if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    header("Location: index.php");
    exit;
}
if (!isset($_POST['id_mueble'])) {
    header("Location: index.php");
    exit;
}

$id_mueble = (int)$_POST['id_mueble'];

$stmt_mueble = mysqli_prepare($conexion, "SELECT id_mueble, id_usuario FROM muebles WHERE id_mueble = ? LIMIT 1");
if (!$stmt_mueble) {
    header("Location: index.php");
    exit;
}
mysqli_stmt_bind_param($stmt_mueble, 'i', $id_mueble);
mysqli_stmt_execute($stmt_mueble);
$res_mueble = mysqli_stmt_get_result($stmt_mueble);
mysqli_stmt_close($stmt_mueble);

if (!$res_mueble || mysqli_num_rows($res_mueble) === 0) {
    header("Location: index.php");
    exit;
}

$mueble = mysqli_fetch_assoc($res_mueble);
$id_propietario = (int)$mueble['id_usuario'];

if (!$es_admin && $id_propietario !== $id_usuario_sesion) {
    header("Location: index.php");
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

if ($es_admin) {
    header("Location: admin.php?seccion=muebles");
} else {
    header("Location: mi_perfil.php");
}
exit;

