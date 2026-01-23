<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id_mueble'])) {
    header("Location: mi_perfil.php");
    exit;
}

$id_usuario = (int)$_SESSION['usuario_id'];
$id_mueble  = (int)$_GET['id_mueble'];

$sql = "DELETE FROM muebles 
        WHERE id_mueble = $id_mueble 
          AND id_usuario = $id_usuario
        LIMIT 1";

mysqli_query($conexion, $sql);

header("Location: mi_perfil.php");
exit;
