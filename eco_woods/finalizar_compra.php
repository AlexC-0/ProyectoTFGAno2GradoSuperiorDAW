<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$id_usuario = (int)$_SESSION['usuario_id'];

$sql_carrito = "SELECT id_carrito FROM carritos
                WHERE id_usuario = $id_usuario AND estado = 'activo'
                LIMIT 1";
$res_carrito = mysqli_query($conexion, $sql_carrito);

$finalizado = false;

if ($res_carrito && mysqli_num_rows($res_carrito) > 0) {
    $fila = mysqli_fetch_assoc($res_carrito);
    $id_carrito = (int)$fila['id_carrito'];

    $sql_items = "SELECT id_item FROM carrito_items WHERE id_carrito = $id_carrito LIMIT 1";
    $res_items = mysqli_query($conexion, $sql_items);

    if ($res_items && mysqli_num_rows($res_items) > 0) {
        $sql_close = "UPDATE carritos SET estado = 'finalizado' WHERE id_carrito = $id_carrito";
        $ok = mysqli_query($conexion, $sql_close);

        if ($ok) {
            $finalizado = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Finalizar compra - ECO & WOODS</title>
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
            <a href="mi_perfil.php">Mi perfil</a>
            <a href="logout.php">Cerrar sesión</a>
        </nav>
    </div>
</header>

<main>
    <div class="contenedor">
        <h1>Finalizar compra</h1>

        <?php if ($finalizado): ?>
            <p><strong>Compra finalizada (modo prueba).</strong></p>
            <p>Esto es una simulación: en el futuro aquí iría el pago, dirección, etc.</p>
            <p><a href="index.php">Volver al inicio</a></p>
        <?php else: ?>
            <p>No se ha podido finalizar la compra (puede que el carrito esté vacío o no tengas carrito activo).</p>
            <p><a href="ver_carrito.php">Volver al carrito</a></p>
        <?php endif; ?>

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
