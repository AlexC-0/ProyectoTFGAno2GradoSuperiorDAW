<?php
session_start();
require 'conexion.php';

// Solo usuarios logueados pueden usar favoritos
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id_mueble'])) {
    header('Location: muebles.php');
    exit;
}

$id_usuario = (int) $_SESSION['usuario_id'];
$id_mueble  = (int) $_GET['id_mueble'];

// ¿Ya está en favoritos?
$sql_check = "SELECT id_favorito 
              FROM favoritos 
              WHERE id_usuario = $id_usuario AND id_mueble = $id_mueble
              LIMIT 1";
$res_check = mysqli_query($conexion, $sql_check);

if ($res_check && mysqli_num_rows($res_check) > 0) {
    // Ya existe -> lo quitamos
    $row = mysqli_fetch_assoc($res_check);
    $id_favorito = (int) $row['id_favorito'];

    $sql_del = "DELETE FROM favoritos WHERE id_favorito = $id_favorito";
    mysqli_query($conexion, $sql_del);

} else {
    // No existe -> lo añadimos
    $sql_ins = "INSERT INTO favoritos (id_usuario, id_mueble)
                VALUES ($id_usuario, $id_mueble)";
    mysqli_query($conexion, $sql_ins);
}

// Volver a la página de donde venimos
$destino = $_SERVER['HTTP_REFERER'] ?? 'muebles.php';
header("Location: $destino");
exit;
