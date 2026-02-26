<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once "conexion.php";

header('Content-Type: application/json; charset=utf-8');

function isAjaxRequest() {
    return (
        !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    );
}

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode([
        "ok" => false,
        "message" => "Debes iniciar sesión para usar favoritos."
    ]);
    exit;
}

$id_usuario = (int)$_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_validate($_POST['csrf_token'] ?? null)) {
    http_response_code(419);
    echo json_encode([
        "ok" => false,
        "message" => "Sesion expirada. Recarga la pagina e intentalo de nuevo."
    ]);
    exit;
}

$source = ($_SERVER['REQUEST_METHOD'] === 'POST') ? $_POST : $_GET;

/* ============================
   DETERMINAR TIPO FAVORITO
   ============================ */
$tipo = null;
$id_producto = 0;

if (isset($source['id_mueble'])) {
    $tipo = 'mueble';
    $id_producto = (int)$source['id_mueble'];
} elseif (isset($source['id_recambio'])) {
    $tipo = 'recambio';
    $id_producto = (int)$source['id_recambio'];
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
   VALIDAR EXISTENCIA
   ============================ */
if ($tipo === 'mueble') {
    $sql_check = "SELECT id_mueble FROM muebles WHERE id_mueble = $id_producto LIMIT 1";
} else {
    $sql_check = "SELECT id_recambio FROM recambios3d WHERE id_recambio = $id_producto LIMIT 1";
}

$res_check = mysqli_query($conexion, $sql_check);
if (!$res_check || mysqli_num_rows($res_check) === 0) {
    http_response_code(404);
    echo json_encode([
        "ok" => false,
        "message" => "El producto no existe."
    ]);
    exit;
}

/* ============================
   TOGGLE
   ============================ */
if ($tipo === 'mueble') {

    $sql_exist = "SELECT 1 FROM favoritos 
                 WHERE id_usuario = $id_usuario AND id_mueble = $id_producto
                 LIMIT 1";
    $res_exist = mysqli_query($conexion, $sql_exist);

    if ($res_exist && mysqli_num_rows($res_exist) > 0) {
        $sql_del = "DELETE FROM favoritos 
                    WHERE id_usuario = $id_usuario AND id_mueble = $id_producto
                    LIMIT 1";
        $ok = mysqli_query($conexion, $sql_del);

        if (!$ok) {
            http_response_code(500);
            echo json_encode(["ok"=>false,"message"=>"Error al quitar de favoritos."]);
            exit;
        }

        echo json_encode(["ok"=>true,"action"=>"removed","message"=>"Quitado de favoritos."]);
        exit;
    } else {
        $sql_ins = "INSERT INTO favoritos (id_usuario, id_mueble, fecha_guardado)
                    VALUES ($id_usuario, $id_producto, NOW())";
        $ok = mysqli_query($conexion, $sql_ins);

        if (!$ok) {
            http_response_code(500);
            echo json_encode(["ok"=>false,"message"=>"Error al añadir a favoritos."]);
            exit;
        }

        echo json_encode(["ok"=>true,"action"=>"added","message"=>"Añadido a favoritos."]);
        exit;
    }

} else { // recambio

    $sql_exist = "SELECT 1 FROM favoritos 
                 WHERE id_usuario = $id_usuario AND id_recambio = $id_producto
                 LIMIT 1";
    $res_exist = mysqli_query($conexion, $sql_exist);

    if ($res_exist && mysqli_num_rows($res_exist) > 0) {
        $sql_del = "DELETE FROM favoritos 
                    WHERE id_usuario = $id_usuario AND id_recambio = $id_producto
                    LIMIT 1";
        $ok = mysqli_query($conexion, $sql_del);

        if (!$ok) {
            http_response_code(500);
            echo json_encode(["ok"=>false,"message"=>"Error al quitar de favoritos."]);
            exit;
        }

        echo json_encode(["ok"=>true,"action"=>"removed","message"=>"Quitado de favoritos."]);
        exit;
    } else {
        $sql_ins = "INSERT INTO favoritos (id_usuario, id_recambio, fecha_guardado)
                    VALUES ($id_usuario, $id_producto, NOW())";
        $ok = mysqli_query($conexion, $sql_ins);

        if (!$ok) {
            http_response_code(500);
            echo json_encode(["ok"=>false,"message"=>"Error al añadir a favoritos."]);
            exit;
        }

        echo json_encode(["ok"=>true,"action"=>"added","message"=>"Añadido a favoritos."]);
        exit;
    }
}
