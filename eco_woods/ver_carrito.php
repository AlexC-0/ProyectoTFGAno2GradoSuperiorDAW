<?php
session_start();
require_once "conexion.php";

$carrito_vacio = true;
$res_items = null;

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

$hay_id_mueble = columnExists($conexion, 'carrito_items', 'id_mueble');
$hay_id_recambio = columnExists($conexion, 'carrito_items', 'id_recambio');

if (isset($_SESSION['usuario_id'])) {
    $id_usuario = (int) $_SESSION['usuario_id'];

    $sql_carrito = "SELECT id_carrito FROM carritos
                    WHERE id_usuario = $id_usuario AND estado = 'activo'
                    LIMIT 1";

    $res_carrito = mysqli_query($conexion, $sql_carrito);

    if ($res_carrito && mysqli_num_rows($res_carrito) > 0) {
        $carrito_vacio = false;
        $fila_carrito = mysqli_fetch_assoc($res_carrito);
        $id_carrito = (int)$fila_carrito['id_carrito'];

        // Construcción dinámica según columnas disponibles
        if ($hay_id_mueble && $hay_id_recambio) {

            $sql_items = "SELECT 
                                ci.id_item,
                                ci.cantidad,
                                ci.id_recambio,
                                ci.id_mueble,
                                r.nombre AS nombre_recambio,
                                r.precio AS precio_recambio,
                                m.titulo AS titulo_mueble,
                                m.precio AS precio_mueble
                          FROM carrito_items ci
                          LEFT JOIN recambios3d r ON ci.id_recambio = r.id_recambio
                          LEFT JOIN muebles m ON ci.id_mueble = m.id_mueble
                          WHERE ci.id_carrito = $id_carrito";

        } elseif ($hay_id_recambio) {

            // Solo recambios (modo antiguo)
            $sql_items = "SELECT 
                                ci.id_item,
                                ci.cantidad,
                                ci.id_recambio,
                                r.nombre AS nombre_recambio,
                                r.precio AS precio_recambio
                          FROM carrito_items ci
                          JOIN recambios3d r ON ci.id_recambio = r.id_recambio
                          WHERE ci.id_carrito = $id_carrito";

        } elseif ($hay_id_mueble) {

            // Solo muebles (por si lo tienes así)
            $sql_items = "SELECT 
                                ci.id_item,
                                ci.cantidad,
                                ci.id_mueble,
                                m.titulo AS titulo_mueble,
                                m.precio AS precio_mueble
                          FROM carrito_items ci
                          JOIN muebles m ON ci.id_mueble = m.id_mueble
                          WHERE ci.id_carrito = $id_carrito";

        } else {
            $sql_items = null;
        }

        if ($sql_items) {
            $res_items = mysqli_query($conexion, $sql_items);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carrito de productos - ECO & WOODS</title>
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

            <a href="ver_carrito.php" class="nav-icon" aria-label="Carrito">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M7 4h-2l-1 2v2h2l3.6 7.59-1.35 2.44A2 2 0 0 0 10 23h10v-2H10l1.1-2h7.45a2 2 0 0 0 1.8-1.1l3.58-6.49A1 1 0 0 0 23 9H7.42L7 8H4V6h2l1-2Z" fill="currentColor"/>
                </svg>
            </a>

            <?php if (isset($_SESSION['usuario_id'])): ?>

                <?php if (!empty($_SESSION['es_admin']) && $_SESSION['es_admin'] == 1): ?>
                    <a href="publicar.php">Publicar</a>
                    <a href="admin.php">Panel Admin</a>
                <?php else: ?>
                    <a href="publicar.php">Publicar mueble</a>
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

        <h1>Carrito de productos</h1>

        <p><a href="index.php">Volver al inicio</a></p>
        <p><a href="muebles.php">Seguir viendo productos</a></p>

        <?php
        if (!isset($_SESSION['usuario_id'])) {

            echo "<p>Debes iniciar sesión para ver tu carrito.</p>";

        } else if ($carrito_vacio) {

            echo "<p>No tienes ningún carrito activo o está vacío.</p>";

        } else {

            if (!$res_items) {
                echo "<p>Ha ocurrido un error al cargar el carrito.</p>";
            } else if (mysqli_num_rows($res_items) == 0) {
                echo "<p>El carrito está vacío.</p>";
            } else {

                $total = 0;

                echo "<table border='1' cellpadding='5' cellspacing='0'>";
                echo "<tr><th>Tipo</th><th>Producto</th><th>Precio</th><th>Cantidad</th><th>Subtotal</th></tr>";

                while ($fila = mysqli_fetch_assoc($res_items)) {

                    $tipo = '';
                    $nombre = '';
                    $precio = 0;

                    // Caso mixto (tiene ambos campos)
                    if (isset($fila['id_recambio']) && !empty($fila['id_recambio'])) {
                        $tipo = 'Recambio 3D';
                        $nombre = $fila['nombre_recambio'] ?? '';
                        $precio = (float)($fila['precio_recambio'] ?? 0);

                    } elseif (isset($fila['id_mueble']) && !empty($fila['id_mueble'])) {
                        $tipo = 'Mueble';
                        $nombre = $fila['titulo_mueble'] ?? '';
                        $precio = (float)($fila['precio_mueble'] ?? 0);

                    } else {
                        continue;
                    }

                    $cantidad = (int)($fila['cantidad'] ?? 0);
                    if ($cantidad <= 0) $cantidad = 1;

                    $subtotal = $precio * $cantidad;
                    $total += $subtotal;

                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($tipo) . "</td>";
                    echo "<td>" . htmlspecialchars($nombre) . "</td>";
                    echo "<td>" . number_format($precio, 2, ',', '.') . " €</td>";
                    echo "<td>" . $cantidad . "</td>";
                    echo "<td>" . number_format($subtotal, 2, ',', '.') . " €</td>";
                    echo "</tr>";
                }

                echo "<tr><td colspan='4'><strong>Total</strong></td><td><strong>" . number_format($total, 2, ',', '.') . " €</strong></td></tr>";
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
