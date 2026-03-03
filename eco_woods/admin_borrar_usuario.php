<?php
/*
DOCUMENTACION_EXPLICATIVA_TFG
Que hace: Elimina un usuario y sus datos relacionados cuando procede.
Por que se hizo asi: Usa transaccion para que todo se borre de forma coherente o no se aplique nada.
Para que sirve: Permite aplicar normas de uso y resolver incidencias de cuenta.
*/
require_once __DIR__ . '/includes/bootstrap.php';
require 'conexion.php';

if (!isset($_SESSION['es_admin']) || (int)$_SESSION['es_admin'] !== 1) {
    die("Acceso denegado");
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin.php?seccion=usuarios");
    exit;
}
if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    header("Location: admin.php?seccion=usuarios");
    exit;
}
if (!isset($_POST['id_usuario'])) {
    header("Location: admin.php?seccion=usuarios");
    exit;
}

$id_usuario = (int)$_POST['id_usuario'];
if ($id_usuario === 1) {
    header("Location: admin.php?seccion=usuarios");
    exit;
}

$stmt_user = mysqli_prepare($conexion, "SELECT id_usuario FROM usuarios WHERE id_usuario = ? LIMIT 1");
if (!$stmt_user) {
    header("Location: admin.php?seccion=usuarios");
    exit;
}
mysqli_stmt_bind_param($stmt_user, 'i', $id_usuario);
mysqli_stmt_execute($stmt_user);
$res_user = mysqli_stmt_get_result($stmt_user);
mysqli_stmt_close($stmt_user);

if (!$res_user || mysqli_num_rows($res_user) === 0) {
    header("Location: admin.php?seccion=usuarios");
    exit;
}

mysqli_begin_transaction($conexion);
try {
    $stmt_muebles = mysqli_prepare($conexion, "SELECT id_mueble FROM muebles WHERE id_usuario = ?");
    if (!$stmt_muebles) {
        throw new RuntimeException('prepare muebles');
    }
    mysqli_stmt_bind_param($stmt_muebles, 'i', $id_usuario);
    mysqli_stmt_execute($stmt_muebles);
    $res_muebles = mysqli_stmt_get_result($stmt_muebles);
    mysqli_stmt_close($stmt_muebles);

    $stmt_del_resenas_mueble = mysqli_prepare($conexion, "DELETE FROM resenas WHERE id_mueble = ?");
    $stmt_del_favoritos_mueble = mysqli_prepare($conexion, "DELETE FROM favoritos WHERE id_mueble = ?");
    $stmt_del_mueble = mysqli_prepare($conexion, "DELETE FROM muebles WHERE id_mueble = ?");
    $stmt_del_resenas_usuario = mysqli_prepare($conexion, "DELETE FROM resenas WHERE id_usuario = ?");
    $stmt_del_favoritos_usuario = mysqli_prepare($conexion, "DELETE FROM favoritos WHERE id_usuario = ?");
    $stmt_del_usuario = mysqli_prepare($conexion, "DELETE FROM usuarios WHERE id_usuario = ?");

    if (
        !$stmt_del_resenas_mueble || !$stmt_del_favoritos_mueble || !$stmt_del_mueble ||
        !$stmt_del_resenas_usuario || !$stmt_del_favoritos_usuario || !$stmt_del_usuario
    ) {
        throw new RuntimeException('prepare delete');
    }

    if ($res_muebles && mysqli_num_rows($res_muebles) > 0) {
        while ($m = mysqli_fetch_assoc($res_muebles)) {
            $id_mueble = (int)$m['id_mueble'];

            mysqli_stmt_bind_param($stmt_del_resenas_mueble, 'i', $id_mueble);
            mysqli_stmt_bind_param($stmt_del_favoritos_mueble, 'i', $id_mueble);
            mysqli_stmt_bind_param($stmt_del_mueble, 'i', $id_mueble);

            if (
                !mysqli_stmt_execute($stmt_del_resenas_mueble) ||
                !mysqli_stmt_execute($stmt_del_favoritos_mueble) ||
                !mysqli_stmt_execute($stmt_del_mueble)
            ) {
                throw new RuntimeException('execute delete mueble');
            }
        }
    }

    mysqli_stmt_bind_param($stmt_del_resenas_usuario, 'i', $id_usuario);
    mysqli_stmt_bind_param($stmt_del_favoritos_usuario, 'i', $id_usuario);
    mysqli_stmt_bind_param($stmt_del_usuario, 'i', $id_usuario);

    if (
        !mysqli_stmt_execute($stmt_del_resenas_usuario) ||
        !mysqli_stmt_execute($stmt_del_favoritos_usuario) ||
        !mysqli_stmt_execute($stmt_del_usuario)
    ) {
        throw new RuntimeException('execute delete user');
    }

    mysqli_stmt_close($stmt_del_resenas_mueble);
    mysqli_stmt_close($stmt_del_favoritos_mueble);
    mysqli_stmt_close($stmt_del_mueble);
    mysqli_stmt_close($stmt_del_resenas_usuario);
    mysqli_stmt_close($stmt_del_favoritos_usuario);
    mysqli_stmt_close($stmt_del_usuario);

    mysqli_commit($conexion);
} catch (Throwable $e) {
    if (isset($stmt_del_resenas_mueble) && $stmt_del_resenas_mueble instanceof mysqli_stmt) { mysqli_stmt_close($stmt_del_resenas_mueble); }
    if (isset($stmt_del_favoritos_mueble) && $stmt_del_favoritos_mueble instanceof mysqli_stmt) { mysqli_stmt_close($stmt_del_favoritos_mueble); }
    if (isset($stmt_del_mueble) && $stmt_del_mueble instanceof mysqli_stmt) { mysqli_stmt_close($stmt_del_mueble); }
    if (isset($stmt_del_resenas_usuario) && $stmt_del_resenas_usuario instanceof mysqli_stmt) { mysqli_stmt_close($stmt_del_resenas_usuario); }
    if (isset($stmt_del_favoritos_usuario) && $stmt_del_favoritos_usuario instanceof mysqli_stmt) { mysqli_stmt_close($stmt_del_favoritos_usuario); }
    if (isset($stmt_del_usuario) && $stmt_del_usuario instanceof mysqli_stmt) { mysqli_stmt_close($stmt_del_usuario); }
    mysqli_rollback($conexion);
}

header("Location: admin.php?seccion=usuarios");
exit;

