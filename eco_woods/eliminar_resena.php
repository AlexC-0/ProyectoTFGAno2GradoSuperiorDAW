<?php
/*
DOCUMENTACION_EXPLICATIVA_TFG
Que hace: Elimina una reseña en el flujo previsto de la app.
Por que se hizo asi: Se controla el permiso y se evita eliminar contenido no autorizado.
Para que sirve: Mantiene la moderacion y limpieza de opiniones.
*/
/*
DOCUMENTACION_PASO4
Eliminacion de resena con reglas de autorizacion.
- Requiere login, POST y CSRF.
- Permite accion al autor de la resena o a un admin.
- Redirige al contexto del mueble tras completar operacion.
*/
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';

ew_require_login('login.php');
require 'conexion.php';

$id_usuario_sesion = (int)$_SESSION['usuario_id'];
$es_admin = ew_is_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}
if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    header("Location: index.php");
    exit;
}
if (!isset($_POST['id_resena'])) {
    header("Location: index.php");
    exit;
}

$id_resena = (int)$_POST['id_resena'];

$stmt_resena = mysqli_prepare($conexion, "SELECT id_resena, id_usuario, id_mueble FROM resenas WHERE id_resena = ? LIMIT 1");
if (!$stmt_resena) {
    header("Location: index.php");
    exit;
}
mysqli_stmt_bind_param($stmt_resena, 'i', $id_resena);
mysqli_stmt_execute($stmt_resena);
$res_resena = mysqli_stmt_get_result($stmt_resena);
mysqli_stmt_close($stmt_resena);

if (!$res_resena || mysqli_num_rows($res_resena) === 0) {
    header("Location: index.php");
    exit;
}

$res = mysqli_fetch_assoc($res_resena);
$id_autor = (int)$res['id_usuario'];
$id_mueble = (int)$res['id_mueble'];

if (!$es_admin && $id_autor !== $id_usuario_sesion) {
    header("Location: index.php");
    exit;
}

$stmt_del = mysqli_prepare($conexion, "DELETE FROM resenas WHERE id_resena = ?");
if ($stmt_del) {
    mysqli_stmt_bind_param($stmt_del, 'i', $id_resena);
    mysqli_stmt_execute($stmt_del);
    mysqli_stmt_close($stmt_del);
}

header("Location: ver_mueble.php?id_mueble=" . $id_mueble);
exit;

