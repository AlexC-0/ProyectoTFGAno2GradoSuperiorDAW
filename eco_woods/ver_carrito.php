<?php
session_start();
require_once "conexion.php";

$carrito_vacio = true;

if (isset($_SESSION['usuario_id'])) {
    $id_usuario = (int) $_SESSION['usuario_id'];

    // Buscar carrito activo de ese usuario
    $sql_carrito = "SELECT id_carrito FROM carritos
                    WHERE id_usuario = $id_usuario AND estado = 'activo'
                    LIMIT 1";

    $res_carrito = mysqli_query($conexion, $sql_carrito);

    if ($res_carrito && mysqli_num_rows($res_carrito) > 0) {
        $carrito_vacio = false;
        $fila_carrito = mysqli_fetch_assoc($res_carrito);
        $id_carrito = (int)$fila_carrito['id_carrito'];

        // Recuperar items del carrito con datos del recambio
        $sql_items = "SELECT ci.id_item, ci.cantidad,
                             r.nombre, r.precio
                      FROM carrito_items ci
                      JOIN recambios3d r ON ci.id_recambio = r.id_recambio
                      WHERE ci.id_carrito = $id_carrito";

        $res_items = mysqli_query($conexion, $sql_items);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carrito de recambios 3D</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>
    <div class="contenedor">
        <h1>ECO & WOODS</h1>
        <nav>
            <a href="index.php">Inicio</a>
            <a href="muebles.php">Muebles</a>
            <a href="recambios.php">Recambios 3D</a>
            <a href="ver_carrito.php">Carrito</a>
            <a href="publicar.php">Publicar mueble</a>

            <?php if (isset($_SESSION['usuario_id'])): ?>

                <?php if (!empty($_SESSION['es_admin']) && $_SESSION['es_admin'] == 1): ?>
                    <a href="admin.php">Panel Admin</a>
                <?php endif; ?>

                <a href="mi_perfil.php">Mi perfil</a>
                <span class="saludo">
                    Hola, <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?>
                </span>
                <a href="logout.php">Cerrar sesión</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="registro.php">Registro</a>
            <?php endif; ?>

        </nav>
    </div>
</header>

<main>
    <div class="contenedor">

        <h1>Carrito de recambios 3D</h1>

        <p><a href="index.php">Volver al inicio</a></p>
        <p><a href="recambios.php">Seguir viendo recambios</a></p>

        <?php
        if ($carrito_vacio) {
            echo "<p>No tienes ningún carrito activo o está vacío.</p>";
        } else {

            if (!$res_items || mysqli_num_rows($res_items) == 0) {
                echo "<p>El carrito está vacío.</p>";
            } else {

                $total = 0;

                echo "<table border='1' cellpadding='5' cellspacing='0'>";
                echo "<tr><th>Recambio</th><th>Precio</th><th>Cantidad</th><th>Subtotal</th></tr>";

                while ($fila = mysqli_fetch_assoc($res_items)) {

                    $subtotal = $fila['precio'] * $fila['cantidad'];
                    $total += $subtotal;

                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($fila['nombre']) . "</td>";
                    echo "<td>" . htmlspecialchars($fila['precio']) . " €</td>";
                    echo "<td>" . htmlspecialchars($fila['cantidad']) . "</td>";
                    echo "<td>" . htmlspecialchars($subtotal) . " €</td>";
                    echo "</tr>";
                }

                echo "<tr><td colspan='3'><strong>Total</strong></td><td><strong>$total €</strong></td></tr>";
                echo "</table>";
            }
        }
        ?>

    </div>
</main>

<footer>
    <div class="contenedor">
        ECO & WOODS - Proyecto Trabajo Fin de Grado
    </div>
</footer>

<button id="btnTop" onclick="scrollToTop()">▲</button>
<script src="js/app.js"></script>

</body>
</html>
