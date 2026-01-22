<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['es_admin']) || (int)$_SESSION['es_admin'] !== 1) {
    die("Acceso denegado");
}

if (!isset($_GET['id_recambio'])) {
    header("Location: admin.php?seccion=recambios");
    exit;
}

$id_recambio = (int)$_GET['id_recambio'];

$sql_rec = "SELECT id_recambio
            FROM recambios3d
            WHERE id_recambio = $id_recambio
            LIMIT 1";

$res_rec = mysqli_query($conexion, $sql_rec);

if (!$res_rec || mysqli_num_rows($res_rec) === 0) {
    header("Location: admin.php?seccion=recambios");
    exit;
}

$sql_del = "DELETE FROM recambios3d WHERE id_recambio = $id_recambio";
mysqli_query($conexion, $sql_del);

header("Location: admin.php?seccion=recambios");
exit;
