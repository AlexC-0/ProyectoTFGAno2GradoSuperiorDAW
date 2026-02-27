<?php
require_once __DIR__ . '/includes/bootstrap.php';
require 'conexion.php';

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

if (!isset($_POST['id_resena'])) {
    header("Location: mi_perfil.php");
    exit;
}

$id_usuario = (int)$_SESSION['usuario_id'];
$id_resena  = (int)$_POST['id_resena'];

$sql = "DELETE FROM resenas
        WHERE id_resena = $id_resena
          AND id_usuario = $id_usuario
        LIMIT 1";

mysqli_query($conexion, $sql);

header("Location: mi_perfil.php");
exit;
