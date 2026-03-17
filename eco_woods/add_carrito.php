<?php
/*
DOCUMENTACION_EXPLICATIVA_TFG
Que hace: Recibe una peticion para meter un mueble o recambio en el carrito.
Por que se hizo asi: Se valida sesion y token para evitar acciones no autorizadas y se usa SQL preparado para impedir inyecciones.
Para que sirve: Permite que el usuario construya su pedido de forma segura y sin errores de duplicados.
*/
/*
DOCUMENTACION_PASO4
Endpoint para anadir muebles o recambios al carrito.
- Requiere login, metodo POST y token CSRF valido.
- Reutiliza o crea carrito activo segun sesion del usuario.
- Inserta o incrementa cantidad y responde en JSON.
*/
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

/*BORRAR*/

$id_usuario = (int)$_SESSION['usuario_id'];

function columnExists($conexion, $tabla, $columna): bool
{
    $stmt = mysqli_prepare(
        $conexion,
        "SELECT 1
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
           AND COLUMN_NAME = ?
         LIMIT 1"
    );
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, 'ss', $tabla, $columna);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return false;
    }
    $res = mysqli_stmt_get_result($stmt);
    $ok = ($res && mysqli_num_rows($res) > 0);
    mysqli_stmt_close($stmt);
    return $ok;
}

function ew_stmt_result(mysqli $conexion, string $sql, string $types = '', array $params = [])
{
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return false;
    }

    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return false;
    }

    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

function ew_stmt_execute(mysqli $conexion, string $sql, string $types, array $params): bool
{
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
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
    $res_check = ew_stmt_result(
        $conexion,
        "SELECT id_recambio FROM recambios3d WHERE id_recambio = ? LIMIT 1",
        'i',
        [$id_producto]
    );
    if (!$res_check || mysqli_num_rows($res_check) === 0) {
        ew_json_error('El recambio no existe.', 404);
    }
} else {
    if (!columnExists($conexion, 'carrito_items', 'id_mueble')) {
        ew_json_error('Tu base de datos aun no esta preparada para anadir muebles al carrito (falta la columna id_mueble en carrito_items).', 501);
    }
    $res_check = ew_stmt_result(
        $conexion,
        "SELECT id_mueble FROM muebles WHERE id_mueble = ? LIMIT 1",
        'i',
        [$id_producto]
    );
    if (!$res_check || mysqli_num_rows($res_check) === 0) {
        ew_json_error('El mueble no existe.', 404);
    }
}

$res_carrito = ew_stmt_result(
    $conexion,
    "SELECT id_carrito FROM carritos WHERE id_usuario = ? AND estado = 'activo' LIMIT 1",
    'i',
    [$id_usuario]
);

if ($res_carrito && mysqli_num_rows($res_carrito) > 0) {
    $fila_carrito = mysqli_fetch_assoc($res_carrito);
    $id_carrito = (int)$fila_carrito['id_carrito'];
} else {
    $ok_nuevo = ew_stmt_execute(
        $conexion,
        "INSERT INTO carritos (id_usuario, estado) VALUES (?, 'activo')",
        'i',
        [$id_usuario]
    );
    if (!$ok_nuevo) {
        ew_json_error('Error al crear el carrito.', 500);
    }
    $id_carrito = (int)mysqli_insert_id($conexion);
}

if ($tipo === 'recambio') {
    $res_item = ew_stmt_result(
        $conexion,
        "SELECT id_item, cantidad FROM carrito_items
         WHERE id_carrito = ? AND id_recambio = ? AND (id_mueble IS NULL OR id_mueble = 0)
         LIMIT 1",
        'ii',
        [$id_carrito, $id_producto]
    );

    if ($res_item && mysqli_num_rows($res_item) > 0) {
        $fila_item = mysqli_fetch_assoc($res_item);
        $id_item = (int)$fila_item['id_item'];
        $nueva_cantidad = (int)$fila_item['cantidad'] + 1;

        $ok_update = ew_stmt_execute(
            $conexion,
            "UPDATE carrito_items SET cantidad = ? WHERE id_item = ?",
            'ii',
            [$nueva_cantidad, $id_item]
        );
        if (!$ok_update) {
            ew_json_error('Error al actualizar la cantidad.', 500);
        }
        ew_json_ok('Cantidad actualizada en el carrito.');
    }

    $ok_insert = ew_stmt_execute(
        $conexion,
        "INSERT INTO carrito_items (id_carrito, id_recambio, id_mueble, cantidad)
         VALUES (?, ?, NULL, 1)",
        'ii',
        [$id_carrito, $id_producto]
    );
    if (!$ok_insert) {
        ew_json_error('Error al anadir el recambio al carrito.', 500);
    }
    ew_json_ok('Recambio anadido al carrito.');
}

$res_item = ew_stmt_result(
    $conexion,
    "SELECT id_item, cantidad FROM carrito_items
     WHERE id_carrito = ? AND id_mueble = ? AND (id_recambio IS NULL OR id_recambio = 0)
     LIMIT 1",
    'ii',
    [$id_carrito, $id_producto]
);

if ($res_item && mysqli_num_rows($res_item) > 0) {
    $fila_item = mysqli_fetch_assoc($res_item);
    $id_item = (int)$fila_item['id_item'];
    $nueva_cantidad = (int)$fila_item['cantidad'] + 1;

    $ok_update = ew_stmt_execute(
        $conexion,
        "UPDATE carrito_items SET cantidad = ? WHERE id_item = ?",
        'ii',
        [$nueva_cantidad, $id_item]
    );
    if (!$ok_update) {
        ew_json_error('Error al actualizar la cantidad.', 500);
    }
    ew_json_ok('Cantidad actualizada en el carrito.');
}

$ok_insert = ew_stmt_execute(
    $conexion,
    "INSERT INTO carrito_items (id_carrito, id_recambio, id_mueble, cantidad)
     VALUES (?, NULL, ?, 1)",
    'ii',
    [$id_carrito, $id_producto]
);
if (!$ok_insert) {
    ew_json_error('Error al anadir el mueble al carrito.', 500);
}

ew_json_ok('Mueble anadido al carrito.');

