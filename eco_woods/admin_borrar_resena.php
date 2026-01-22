<?php
session_start();
require 'conexion.php';

// Solo admin
if (!isset($_SESSION['es_admin']) || (int)$_SESSION['es_admin'] !== 1) {
    die("Acceso denegado");
}

// Comprobar que llega la reseña
if (!isset($_GET['id_resena'])) {
    header("Location: admin.php?seccion=resenas");
    exit;
}

$id_resena = (int)$_GET['id_resena'];

// 1) Buscar la reseña para confirmar que existe
$sql_resena = "SELECT id_resena
               FROM resenas
               WHERE id_resena = $id_resena
               LIMIT 1";

$res = mysqli_query($conexion, $sql_resena);

if (!$res || mysqli_num_rows($res) === 0) {
    header("Location: admin.php?seccion=resenas");
    exit;
}

// 2) Eliminar la reseña
$sql_del = "DELETE FROM resenas WHERE id_resena = $id_resena";
mysqli_query($conexion, $sql_del);

// 3) Volver al panel admin
header("Location: admin.php?seccion=resenas");
exit;
