<?php
/*
DOCUMENTACION_EXPLICATIVA_TFG
Que hace: Gestiona borrado de mueble en contexto de usuario propietario.
Por que se hizo asi: Comprueba autorizacion para evitar que alguien borre contenido ajeno.
Para que sirve: Asegura que cada usuario solo gestiona lo suyo.
*/
require_once __DIR__ . '/includes/bootstrap.php';
require 'conexion.php';

/*BORRAR*/

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: mi_perfil.php");
    exit;
}

if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    header("Location: mi_perfil.php");
    exit;
}

if (!isset($_POST['id_mueble'])) {
    header("Location: mi_perfil.php");
    exit;
}

$id_usuario = (int)$_SESSION['usuario_id'];
$id_mueble  = (int)$_POST['id_mueble'];

$stmt = mysqli_prepare(
    $conexion,
    "DELETE FROM muebles
     WHERE id_mueble = ? AND id_usuario = ?
     LIMIT 1"
);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'ii', $id_mueble, $id_usuario);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

header("Location: mi_perfil.php");
exit;

