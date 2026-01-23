<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id_resena'])) {
    header("Location: mi_perfil.php");
    exit;
}

$id_usuario = (int)$_SESSION['usuario_id'];
$id_resena  = (int)$_GET['id_resena'];

$sql = "DELETE FROM resenas
        WHERE id_resena = $id_resena
          AND id_usuario = $id_usuario
        LIMIT 1";

mysqli_query($conexion, $sql);

header("Location: mi_perfil.php");
exit;
