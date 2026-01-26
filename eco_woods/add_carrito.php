<?php
session_start();
require_once "conexion.php";

header('Content-Type: application/json; charset=utf-8');

// Usuario por sesión. Si no hay sesión, NO permitimos carrito.
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode([
        "ok" => false,
        "message" => "Debes iniciar sesión para añadir al carrito."
    ]);
    exit;
}

$id_usuario = (int)$_SESSION['usuario_id'];

/* ============================
   FUNCIONES AUXILIARES
   ============================ */
function columnExists($conexion, $tabla, $columna) {
    $tabla_esc = mysqli_real_escape_string($conexion, $tabla);
    $col_esc = mysqli_real_escape_string($conexion, $columna);
    $sql = "SHOW COLUMNS FROM `$tabla_esc` LIKE '$col_esc'";
    $res = mysqli_query($conexion, $sql);
    return ($res && mysqli_num_rows($res) > 0);
}

/* ============================
   DETERMINAR TIPO DE PRODUCTO
   ============================ */
$tipo = null;
$id_producto = 0;

if (isset($_GET['id_recambio'])) {
    $tipo = 'recambio';
    $id_producto = (int)$_GET['id_recambio'];
} elseif (isset($_GET['id_mueble'])) {
    $tipo = 'mueble';
    $id_producto = (int)$_GET['id_mueble'];
} else {
    http_response_code(400);
    echo json_encode([
        "ok" => false,
        "message" => "Producto no especificado."
    ]);
    exit;
}

if ($id_producto <= 0) {
    http_response_code(400);
    echo json_encode([
        "ok" => false,
        "message" => "Producto no válido."
    ]);
    exit;
}

/* ============================
   VALIDAR PRODUCTO EN BD
   ============================ */
if ($tipo === 'recambio') {

    $sql_check = "SELECT id_recambio FROM recambios3d WHERE id_recambio = $id_producto LIMIT 1";
    $res_check = mysqli_query($conexion, $sql_check);

    if (!$res_check || mysqli_num_rows($res_check) == 0) {
        http_response_code(404);
        echo json_encode([
            "ok" => false,
            "message" => "El recambio no existe."
        ]);
        exit;
    }

} else { // mueble

    if (!columnExists($conexion, 'carrito_items', 'id_mueble')) {
        http_response_code(501);
        echo json_encode([
            "ok" => false,
            "message" => "Tu base de datos aún no está preparada para añadir muebles al carrito (falta la columna id_mueble en carrito_items)."
        ]);
        exit;
    }

    $sql_check = "SELECT id_mueble FROM muebles WHERE id_mueble = $id_producto LIMIT 1";
    $res_check = mysqli_query($conexion, $sql_check);

    if (!$res_check || mysqli_num_rows($res_check) == 0) {
        http_response_code(404);
        echo json_encode([
            "ok" => false,
            "message" => "El mueble no existe."
        ]);
        exit;
    }
}

/* ============================
   1) BUSCAR / CREAR CARRITO ACTIVO
   ============================ */
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
        http_response_code(500);
        echo json_encode([
            "ok" => false,
            "message" => "Error al crear el carrito."
        ]);
        exit;
    }

    $id_carrito = (int)mysqli_insert_id($conexion);
}

/* ============================
   2) INSERT / UPDATE ITEM
   ============================ */
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
            http_response_code(500);
            echo json_encode([
                "ok" => false,
                "message" => "Error al actualizar la cantidad."
            ]);
            exit;
        }

        echo json_encode([
            "ok" => true,
            "message" => "Cantidad actualizada en el carrito."
        ]);
        exit;

    } else {

        $sql_insert = "INSERT INTO carrito_items (id_carrito, id_recambio, id_mueble, cantidad)
                       VALUES ($id_carrito, $id_producto, NULL, 1)";

        $ok_insert = mysqli_query($conexion, $sql_insert);

        if (!$ok_insert) {
            http_response_code(500);
            echo json_encode([
                "ok" => false,
                "message" => "Error al añadir el recambio al carrito."
            ]);
            exit;
        }

        echo json_encode([
            "ok" => true,
            "message" => "Recambio añadido al carrito."
        ]);
        exit;
    }

} else { // mueble

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
            http_response_code(500);
            echo json_encode([
                "ok" => false,
                "message" => "Error al actualizar la cantidad."
            ]);
            exit;
        }

        echo json_encode([
            "ok" => true,
            "message" => "Cantidad actualizada en el carrito."
        ]);
        exit;

    } else {

        $sql_insert = "INSERT INTO carrito_items (id_carrito, id_recambio, id_mueble, cantidad)
                       VALUES ($id_carrito, NULL, $id_producto, 1)";

        $ok_insert = mysqli_query($conexion, $sql_insert);

        if (!$ok_insert) {
            http_response_code(500);
            echo json_encode([
                "ok" => false,
                "message" => "Error al añadir el mueble al carrito."
            ]);
            exit;
        }

        echo json_encode([
            "ok" => true,
            "message" => "Mueble añadido al carrito."
        ]);
        exit;
    }
}
