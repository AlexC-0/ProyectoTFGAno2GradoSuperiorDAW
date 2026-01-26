<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>ECO & WOODS - Inicio</title>
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

            <!-- Carrito como icono -->
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

                <span class="saludo">
                    Hola, <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?>
                </span>
                <a href="mi_perfil.php">Mi perfil</a>
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

        <h2>Plataforma de compra y venta de muebles de segunda mano</h2>
        <p>
            ECO & WOODS es una plataforma orientada a dar una segunda vida a los muebles,
            fomentando la reutilización y la sostenibilidad. Además, ofrece recambios
            impresos en 3D para reparar y prolongar la vida útil de tus muebles.
        </p>

        <h2>Buscar muebles</h2>
        <form action="buscar.php" method="get">
            <p>
                <label>Palabra clave (mesa, armario, silla...):<br>
                    <input type="text" name="q">
                </label>
            </p>

            <p>
                <label>Ubicación (provincia o localidad, ej. Vizcaya):<br>
                    <input type="text" name="ubicacion">
                </label>
            </p>

            <p>
                <button type="submit">Buscar</button>
            </p>
        </form>

        <h2>Accesos rápidos</h2>
        <ul>
            <li><a href="muebles.php">Ver listado de muebles</a></li>
            <li><a href="publicar.php">Publicar un mueble</a></li>
            <li><a href="recambios.php">Ver recambios 3D</a></li>
            <li><a href="ver_carrito.php">Ver carrito de recambios</a></li>
        </ul>

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
