<?php










require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../includes/http.php';

function ew_stmt_result(mysqli $conexion, string $sql)
{
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return false;
    }
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return false;
    }
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

$resultado = ew_stmt_result(
    $conexion,
    "SELECT id_recambio,
            nombre,
            descripcion,
            tipo,
            compatible_con,
            precio
     FROM recambios3d
     ORDER BY id_recambio DESC"
);

$recambios = [];
if ($resultado && mysqli_num_rows($resultado) > 0) {
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $fila['id_recambio'] = (int)$fila['id_recambio'];
        $fila['precio'] = (float)$fila['precio'];
        $recambios[] = $fila;
    }
}

ew_json(['ok' => true, 'recambios' => $recambios]);

