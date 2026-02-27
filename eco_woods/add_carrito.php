<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/http.php';
require_once __DIR__ . '/includes/validators.php';
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ew_json_error('Metodo no permitido.', 405);
}

if (!ew_is_logged_in()) {
    ew_json_error('Debes iniciar sesion para anadir al carrito.', 401);
}

if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    ew_json_error('Sesion expirada. Recarga la pagina e intentalo de nuevo.', 419);
}

$id_usuario = (int)$_SESSION['usuario_id'];

function columnExists($conexion, $tabla, $columna): bool
{
    $tabla_esc = mysqli_real_escape_string($conexion, $tabla);
    $col_esc = mysqli_real_escape_string($conexion, $columna);
    $sql = "SHOW COLUMNS FROM `$tabla_esc` LIKE '$col_esc'";
    $res = mysqli_query($conexion, $sql);
    return ($res && mysqli_num_rows($res) > 0);
}

$tipo = null;
$id_producto = 0;

if (isset($_POST['id_recambio'])) {
    $tipo = 'recambio';
    $id_producto = ew_post_int('id_recambio');
} elseif (isset($_POST['id_mueble'])) {
    $tipo = 'mueble';
    $id_producto = ew_post_int('id_mueble');
} else {
    ew_json_error('Producto no especificado.', 400);
}

if ($id_producto <= 0) {
    ew_json_error('Producto no valido.', 400);
}

if ($tipo === 'recambio') {
    $sql_check = "SELECT id_recambio FROM recambios3d WHERE id_recambio = $id_producto LIMIT 1";
    $res_check = mysqli_query($conexion, $sql_check);
    if (!$res_check || mysqli_num_rows($res_check) === 0) {
        ew_json_error('El recambio no existe.', 404);
    }
} else {
    if (!columnExists($conexion, 'carrito_items', 'id_mueble')) {
        ew_json_error('Tu base de datos aun no esta preparada para anadir muebles al carrito (falta la columna id_mueble en carrito_items).', 501);
    }

    $sql_check = "SELECT id_mueble FROM muebles WHERE id_mueble = $id_producto LIMIT 1";
    $res_check = mysqli_query($conexion, $sql_check);
    if (!$res_check || mysqli_num_rows($res_check) === 0) {
        ew_json_error('El mueble no existe.', 404);
    }
}

$sql_carrito = "SELECT id_carrito FROM carritos
                WHERE id_usuario = $id_usuario AND estado = 'activo'
                LIMIT 1";
$res_carrito = mysqli_query($conexion, $sql_carrito);

if ($res_carrito && mysqli_num_rows($res_carrito) > 0) {
    $fila_carrito = mysqli_fetch_assoc($res_carrito);
    $id_carrito = (int)$fila_carrito['id_carrito'];
} else {
    $sql_nuevo = "INSERT INTO carritos (id_usuario, estado) VALUES ($id_usuario, 'activo')";
    $ok_nuevo = mysqli_query($conexion, $sql_nuevo);

    if (!$ok_nuevo) {
        ew_json_error('Error al crear el carrito.', 500);
    }

    $id_carrito = (int)mysqli_insert_id($conexion);
}

if ($tipo === 'recambio') {
    $sql_item = "SELECT id_item, cantidad FROM carrito_items
                 WHERE id_carrito = $id_carrito AND id_recambio = $id_producto AND (id_mueble IS NULL OR id_mueble = 0)
                 LIMIT 1";
    $res_item = mysqli_query($conexion, $sql_item);

    if ($res_item && mysqli_num_rows($res_item) > 0) {
        $fila_item = mysqli_fetch_assoc($res_item);
        $nueva_cantidad = (int)$fila_item['cantidad'] + 1;
        $id_item = (int)$fila_item['id_item'];

        $sql_update = "UPDATE carrito_items
                       SET cantidad = $nueva_cantidad
                       WHERE id_item = $id_item";
        $ok_update = mysqli_query($conexion, $sql_update);

        if (!$ok_update) {
            ew_json_error('Error al actualizar la cantidad.', 500);
        }

        ew_json_ok('Cantidad actualizada en el carrito.');
    }

    $sql_insert = "INSERT INTO carrito_items (id_carrito, id_recambio, id_mueble, cantidad)
                   VALUES ($id_carrito, $id_producto, NULL, 1)";
    $ok_insert = mysqli_query($conexion, $sql_insert);

    if (!$ok_insert) {
        ew_json_error('Error al anadir el recambio al carrito.', 500);
    }

    ew_json_ok('Recambio anadido al carrito.');
}

$sql_item = "SELECT id_item, cantidad FROM carrito_items
             WHERE id_carrito = $id_carrito AND id_mueble = $id_producto AND (id_recambio IS NULL OR id_recambio = 0)
             LIMIT 1";
$res_item = mysqli_query($conexion, $sql_item);

if ($res_item && mysqli_num_rows($res_item) > 0) {
    $fila_item = mysqli_fetch_assoc($res_item);
    $nueva_cantidad = (int)$fila_item['cantidad'] + 1;
    $id_item = (int)$fila_item['id_item'];

    $sql_update = "UPDATE carrito_items
                   SET cantidad = $nueva_cantidad
                   WHERE id_item = $id_item";
    $ok_update = mysqli_query($conexion, $sql_update);

    if (!$ok_update) {
        ew_json_error('Error al actualizar la cantidad.', 500);
    }

    ew_json_ok('Cantidad actualizada en el carrito.');
}

$sql_insert = "INSERT INTO carrito_items (id_carrito, id_recambio, id_mueble, cantidad)
               VALUES ($id_carrito, NULL, $id_producto, 1)";
$ok_insert = mysqli_query($conexion, $sql_insert);

if (!$ok_insert) {
    ew_json_error('Error al anadir el mueble al carrito.', 500);
}

ew_json_ok('Mueble anadido al carrito.');
