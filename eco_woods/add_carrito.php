<?php
require_once "conexion.php";

// Usuario fijo (de momento)
$id_usuario = 1;

// Comprobar que llega el id del recambio
if (!isset($_GET['id_recambio'])) {
    die("Recambio no especificado.");
}

$id_recambio = (int) $_GET['id_recambio'];

// 1) Buscar carrito activo del usuario
$sql_carrito = "SELECT id_carrito FROM carritos
                WHERE id_usuario = $id_usuario AND estado = 'activo'
                LIMIT 1";

$res_carrito = mysqli_query($conexion, $sql_carrito);

if (mysqli_num_rows($res_carrito) > 0) {
    $fila_carrito = mysqli_fetch_assoc($res_carrito);
    $id_carrito = $fila_carrito['id_carrito'];
} else {
    // No hay carrito activo → crear uno
    $sql_nuevo = "INSERT INTO carritos (id_usuario) VALUES ($id_usuario)";
    $ok_nuevo = mysqli_query($conexion, $sql_nuevo);

    if (!$ok_nuevo) {
        die("Error al crear el carrito: " . mysqli_error($conexion));
    }

    $id_carrito = mysqli_insert_id($conexion);
}

// 2) ¿Ya existe este recambio en el carrito?
$sql_item = "SELECT id_item, cantidad FROM carrito_items
             WHERE id_carrito = $id_carrito AND id_recambio = $id_recambio
             LIMIT 1";

$res_item = mysqli_query($conexion, $sql_item);

if (mysqli_num_rows($res_item) > 0) {
    // Ya existe → sumamos 1 a la cantidad
    $fila_item = mysqli_fetch_assoc($res_item);
    $nueva_cantidad = $fila_item['cantidad'] + 1;

    $sql_update = "UPDATE carrito_items
                   SET cantidad = $nueva_cantidad
                   WHERE id_item = " . $fila_item['id_item'];

    $ok_update = mysqli_query($conexion, $sql_update);

    if (!$ok_update) {
        die("Error al actualizar la cantidad: " . mysqli_error($conexion));
    }

    $mensaje = "Cantidad actualizada en el carrito.";

} else {
    // No existe → lo insertamos
    $sql_insert = "INSERT INTO carrito_items (id_carrito, id_recambio, cantidad)
                   VALUES ($id_carrito, $id_recambio, 1)";

    $ok_insert = mysqli_query($conexion, $sql_insert);

    if (!$ok_insert) {
        die("Error al añadir el recambio al carrito: " . mysqli_error($conexion));
    }

    $mensaje = "Recambio añadido al carrito.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carrito</title>
</head>
<body>

<h1><?php echo $mensaje; ?></h1>

<p><a href="recambios.php">Volver a recambios 3D</a></p>
<p><a href="ver_carrito.php">Ver carrito</a></p>
<p><a href="index.php">Volver al inicio</a></p>

</body>
</html>
