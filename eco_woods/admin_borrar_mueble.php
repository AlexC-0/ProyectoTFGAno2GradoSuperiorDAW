<?php
session_start();
require 'conexion.php';

// Solo admin
if (!isset($_SESSION['es_admin']) || (int)$_SESSION['es_admin'] !== 1) {
    die("Acceso denegado");
}

// Comprobar que llega el mueble
if (!isset($_GET['id_mueble'])) {
    header("Location: admin.php?seccion=muebles");
    exit;
}

$id_mueble = (int)$_GET['id_mueble'];

// 1) Buscar el mueble para confirmar que existe
$sql_mueble = "SELECT id_mueble
               FROM muebles
               WHERE id_mueble = $id_mueble
               LIMIT 1";

$res_mueble = mysqli_query($conexion, $sql_mueble);

if (!$res_mueble || mysqli_num_rows($res_mueble) === 0) {
    // Mueble no existe
    header("Location: admin.php?seccion=muebles");
    exit;
}

// 2) Eliminar datos relacionados (reseñas, favoritos, etc.)
$sql_del_resenas    = "DELETE FROM resenas WHERE id_mueble = $id_mueble";
$sql_del_favoritos  = "DELETE FROM favoritos WHERE id_mueble = $id_mueble";
$sql_del_mueble     = "DELETE FROM muebles WHERE id_mueble = $id_mueble";

mysqli_query($conexion, $sql_del_resenas);
mysqli_query($conexion, $sql_del_favoritos);
mysqli_query($conexion, $sql_del_mueble);

// 3) Volver al panel admin, sección muebles
header("Location: admin.php?seccion=muebles");
exit;
