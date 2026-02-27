<?php
require_once __DIR__ . '/includes/bootstrap.php';
require 'conexion.php';

// Solo admin
if (!isset($_SESSION['es_admin']) || (int)$_SESSION['es_admin'] !== 1) {
    die("Acceso denegado");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin.php?seccion=resenas");
    exit;
}

if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    header("Location: admin.php?seccion=resenas");
    exit;
}

// Comprobar que llega la reseña
if (!isset($_POST['id_resena'])) {
    header("Location: admin.php?seccion=resenas");
    exit;
}

$id_resena = (int)$_POST['id_resena'];

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
