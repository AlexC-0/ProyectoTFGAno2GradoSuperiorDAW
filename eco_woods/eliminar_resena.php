<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';

// Endpoint privado: requiere usuario autenticado.
ew_require_login('login.php');
require 'conexion.php';

$id_usuario_sesion = (int)$_SESSION['usuario_id'];
$es_admin          = ew_is_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    header("Location: index.php");
    exit;
}

// Comprobar que llega la reseña
if (!isset($_POST['id_resena'])) {
    header("Location: index.php");
    exit;
}

$id_resena = (int)$_POST['id_resena'];

// 1) Buscar la reseña para saber de quién es y a qué mueble pertenece
$sql_resena = "SELECT id_resena, id_usuario, id_mueble
               FROM resenas
               WHERE id_resena = $id_resena
               LIMIT 1";

$res_resena = mysqli_query($conexion, $sql_resena);

if (!$res_resena || mysqli_num_rows($res_resena) === 0) {
    header("Location: index.php");
    exit;
}

$res = mysqli_fetch_assoc($res_resena);
$id_autor   = (int)$res['id_usuario'];
$id_mueble  = (int)$res['id_mueble'];

// 2) Comprobar permisos: admin o autor
if (!$es_admin && $id_autor !== $id_usuario_sesion) {
    header("Location: index.php");
    exit;
}

// 3) Eliminar la reseña
$sql_del = "DELETE FROM resenas WHERE id_resena = $id_resena";
mysqli_query($conexion, $sql_del);

// 4) Volver a la ficha del mueble
header("Location: ver_mueble.php?id_mueble=" . $id_mueble);
exit;
