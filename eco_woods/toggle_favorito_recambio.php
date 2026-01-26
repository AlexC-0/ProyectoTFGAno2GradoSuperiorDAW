<?php
session_start();
require_once "conexion.php";

function tableExists($conexion, $tabla) {
    $tabla_esc = mysqli_real_escape_string($conexion, $tabla);
    $sql = "SHOW TABLES LIKE '$tabla_esc'";
    $res = mysqli_query($conexion, $sql);
    return ($res && mysqli_num_rows($res) > 0);
}

function isAjaxRequest() {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        return true;
    }
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (strpos($accept, 'application/json') !== false) {
        return true;
    }
    return false;
}

$ajax = isAjaxRequest();

if ($ajax) {
    header('Content-Type: application/json; charset=utf-8');
}

// Login obligatorio
if (!isset($_SESSION['usuario_id'])) {
    if ($ajax) {
        http_response_code(401);
        echo json_encode([
            "ok" => false,
            "message" => "Debes iniciar sesión para usar favoritos."
        ]);
        exit;
    } else {
        header("Location: login.php");
        exit;
    }
}

$id_usuario = (int)$_SESSION['usuario_id'];

// ID recambio obligatorio
if (!isset($_GET['id_recambio'])) {
    if ($ajax) {
        http_response_code(400);
        echo json_encode([
            "ok" => false,
            "message" => "Recambio no especificado."
        ]);
        exit;
    } else {
        die("Recambio no especificado.");
    }
}

$id_recambio = (int)$_GET['id_recambio'];
if ($id_recambio <= 0) {
    if ($ajax) {
        http_response_code(400);
        echo json_encode([
            "ok" => false,
            "message" => "Recambio no válido."
        ]);
        exit;
    } else {
        die("Recambio no válido.");
    }
}

// Comprobar que el recambio existe
$sql_check = "SELECT id_recambio FROM recambios3d WHERE id_recambio = $id_recambio LIMIT 1";
$res_check = mysqli_query($conexion, $sql_check);

if (!$res_check || mysqli_num_rows($res_check) == 0) {
    if ($ajax) {
        http_response_code(404);
        echo json_encode([
            "ok" => false,
            "message" => "El recambio no existe."
        ]);
        exit;
    } else {
        die("El recambio no existe.");
    }
}

// Comprobar que existe la tabla favoritos_recambios (todavía no la vas a crear)
if (!tableExists($conexion, 'favoritos_recambios')) {
    if ($ajax) {
        http_response_code(501);
        echo json_encode([
            "ok" => false,
            "message" => "La tabla favoritos_recambios aún no existe en la base de datos."
        ]);
        exit;
    } else {
        die("La tabla favoritos_recambios aún no existe en la base de datos.");
    }
}

// ¿Ya está en favoritos?
$sql_exist = "SELECT id_favorito
              FROM favoritos_recambios
              WHERE id_usuario = $id_usuario AND id_recambio = $id_recambio
              LIMIT 1";

$res_exist = mysqli_query($conexion, $sql_exist);

if ($res_exist && mysqli_num_rows($res_exist) > 0) {

    // Quitar de favoritos
    $fila = mysqli_fetch_assoc($res_exist);
    $id_favorito = (int)$fila['id_favorito'];

    $sql_del = "DELETE FROM favoritos_recambios WHERE id_favorito = $id_favorito";
    $ok_del = mysqli_query($conexion, $sql_del);

    if (!$ok_del) {
        if ($ajax) {
            http_response_code(500);
            echo json_encode([
                "ok" => false,
                "message" => "Error al quitar de favoritos."
            ]);
            exit;
        } else {
            die("Error al quitar de favoritos.");
        }
    }

    if ($ajax) {
        echo json_encode([
            "ok" => true,
            "es_favorito" => false,
            "message" => "Quitado de favoritos."
        ]);
        exit;
    } else {
        header("Location: recambios.php");
        exit;
    }

} else {

    // Añadir a favoritos
    $sql_ins = "INSERT INTO favoritos_recambios (id_usuario, id_recambio, fecha_guardado)
                VALUES ($id_usuario, $id_recambio, NOW())";

    $ok_ins = mysqli_query($conexion, $sql_ins);

    if (!$ok_ins) {
        // Si la tabla aún no tiene fecha_guardado, reintentamos sin esa columna
        $sql_ins2 = "INSERT INTO favoritos_recambios (id_usuario, id_recambio)
                     VALUES ($id_usuario, $id_recambio)";
        $ok_ins2 = mysqli_query($conexion, $sql_ins2);

        if (!$ok_ins2) {
            if ($ajax) {
                http_response_code(500);
                echo json_encode([
                    "ok" => false,
                    "message" => "Error al añadir a favoritos."
                ]);
                exit;
            } else {
                die("Error al añadir a favoritos.");
            }
        }
    }

    if ($ajax) {
        echo json_encode([
            "ok" => true,
            "es_favorito" => true,
            "message" => "Añadido a favoritos."
        ]);
        exit;
    } else {
        header("Location: recambios.php");
        exit;
    }
}
