<?php
require_once __DIR__ . '/includes/bootstrap.php';
require 'conexion.php';

// Solo admin
if (!isset($_SESSION['es_admin']) || (int)$_SESSION['es_admin'] !== 1) {
    die("Acceso denegado");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin.php?seccion=usuarios");
    exit;
}

if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    header("Location: admin.php?seccion=usuarios");
    exit;
}

// Comprobar que llega el usuario
if (!isset($_POST['id_usuario'])) {
    header("Location: admin.php?seccion=usuarios");
    exit;
}

$id_usuario = (int)$_POST['id_usuario'];

// Opcional: evitar eliminar al admin principal (id 1)
if ($id_usuario === 1) {
    header("Location: admin.php?seccion=usuarios");
    exit;
}

// 1) Comprobar que el usuario existe
$sql_user = "SELECT id_usuario
             FROM usuarios
             WHERE id_usuario = $id_usuario
             LIMIT 1";

$res_user = mysqli_query($conexion, $sql_user);

if (!$res_user || mysqli_num_rows($res_user) === 0) {
    header("Location: admin.php?seccion=usuarios");
    exit;
}

// 2) Buscar todos los muebles del usuario
$sql_muebles = "SELECT id_mueble
                FROM muebles
                WHERE id_usuario = $id_usuario";

$res_muebles = mysqli_query($conexion, $sql_muebles);

if ($res_muebles && mysqli_num_rows($res_muebles) > 0) {
    while ($m = mysqli_fetch_assoc($res_muebles)) {
        $id_mueble = (int)$m['id_mueble'];

        // Eliminar reseñas y favoritos de cada mueble
        $sql_del_resenas   = "DELETE FROM resenas WHERE id_mueble = $id_mueble";
        $sql_del_favoritos = "DELETE FROM favoritos WHERE id_mueble = $id_mueble";
        $sql_del_mueble    = "DELETE FROM muebles  WHERE id_mueble = $id_mueble";

        mysqli_query($conexion, $sql_del_resenas);
        mysqli_query($conexion, $sql_del_favoritos);
        mysqli_query($conexion, $sql_del_mueble);
    }
}

// 3) Eliminar reseñas escritas por el usuario en muebles de otros
$sql_del_resenas_usuario = "DELETE FROM resenas WHERE id_usuario = $id_usuario";
mysqli_query($conexion, $sql_del_resenas_usuario);

// 4) Eliminar favoritos del usuario
$sql_del_favoritos_usuario = "DELETE FROM favoritos WHERE id_usuario = $id_usuario";
mysqli_query($conexion, $sql_del_favoritos_usuario);

// 5) Eliminar el propio usuario
$sql_del_usuario = "DELETE FROM usuarios WHERE id_usuario = $id_usuario";
mysqli_query($conexion, $sql_del_usuario);

// 6) Volver al panel admin, sección usuarios
header("Location: admin.php?seccion=usuarios");
exit;
