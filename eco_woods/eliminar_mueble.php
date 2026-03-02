<?php
/*
DOCUMENTACION_PASO4
Eliminacion de mueble con control de permisos.
- Solo permite accion por POST autenticado con CSRF.
- Autoriza solo propietario o administrador.
- Limpia datos relacionados para mantener integridad.
*/
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

// Comprobar que llega el mueble
if (!isset($_POST['id_mueble'])) {
    header("Location: index.php");
    exit;
}

$id_mueble = (int)$_POST['id_mueble'];

// 1) Buscar el mueble para saber de quién es
$sql_mueble = "SELECT id_mueble, id_usuario
               FROM muebles
               WHERE id_mueble = $id_mueble
               LIMIT 1";

$res_mueble = mysqli_query($conexion, $sql_mueble);

if (!$res_mueble || mysqli_num_rows($res_mueble) === 0) {
    // Mueble no existe
    header("Location: index.php");
    exit;
}

$mueble = mysqli_fetch_assoc($res_mueble);
$id_propietario = (int)$mueble['id_usuario'];

// 2) Comprobar permisos: admin o dueño
if (!$es_admin && $id_propietario !== $id_usuario_sesion) {
    // Sin permisos
    header("Location: index.php");
    exit;
}

// 3) Eliminar datos relacionados (reseñas, favoritos, etc.)
$sql_del_resenas    = "DELETE FROM resenas WHERE id_mueble = $id_mueble";
$sql_del_favoritos  = "DELETE FROM favoritos WHERE id_mueble = $id_mueble";
$sql_del_mueble     = "DELETE FROM muebles WHERE id_mueble = $id_mueble";

mysqli_query($conexion, $sql_del_resenas);
mysqli_query($conexion, $sql_del_favoritos);
mysqli_query($conexion, $sql_del_mueble);

// 4) Redirigir según quién ha hecho la acción
if ($es_admin) {
    header("Location: admin.php?seccion=muebles");
} else {
    header("Location: mi_perfil.php");
}
exit;

