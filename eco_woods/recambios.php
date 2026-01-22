<?php
session_start();
require_once "conexion.php";

$sql = "SELECT * FROM recambios3d ORDER BY id_recambio DESC";
$resultado = mysqli_query($conexion, $sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recambios 3D ECO & WOODS</title>
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

        <p><a href="index.php">Volver al inicio</a></p>

        <h1>Recambios 3D ECO & WOODS</h1>

        <p>
            Aquí encontrarás los <strong>recambios oficiales</strong> que ECO & WOODS pone a disposición
            para complementar los muebles de segunda mano:
            bisagras, topes, piezas específicas y otros componentes impresos en 3D.
        </p>

        <hr>

        <?php
        if (mysqli_num_rows($resultado) > 0) {

            while ($fila = mysqli_fetch_assoc($resultado)) {

                echo '<div class="tarjeta">';
                echo "<h3>" . htmlspecialchars($fila['nombre']) . "</h3>";
                echo "<p><strong>Descripción:</strong> " . htmlspecialchars($fila['descripcion']) . "</p>";
                echo "<p><strong>Tipo:</strong> " . htmlspecialchars($fila['tipo']) . "</p>";
                echo "<p><strong>Compatible con:</strong> " . htmlspecialchars($fila['compatible_con']) . "</p>";
                echo "<p><strong>Precio:</strong> " . htmlspecialchars($fila['precio']) . " €</p>";

                // Enlace para añadir al carrito (se mantiene igual)
                echo '<p><a href="add_carrito.php?id_recambio=' . (int)$fila['id_recambio'] . '">Añadir al carrito</a></p>';

                echo "</div>";
            }

        } else {
            echo "<p>De momento no hay recambios 3D disponibles en el catálogo.</p>";
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
