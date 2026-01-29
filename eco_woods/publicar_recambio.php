<?php

session_start();  // Siempre lo primero

// Si NO hay usuario logueado, lo mandamos al login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

require_once "conexion.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre         = $_POST['nombre'] ?? '';
    $descripcion    = $_POST['descripcion'] ?? '';
    $precio         = $_POST['precio'] ?? '';
    $tipo           = $_POST['tipo'] ?? '';
    $compatible_con = $_POST['compatible_con'] ?? '';

    if ($nombre != '' && $precio != '') {

        $sql = "INSERT INTO recambios3d (nombre, descripcion, precio, tipo, compatible_con)
                VALUES ('$nombre', '$descripcion', $precio, '$tipo', '$compatible_con')";

        $ok = mysqli_query($conexion, $sql);

        if ($ok) {
            $mensaje = "Recambio 3D publicado correctamente.";
        } else {
            $mensaje = "Error al publicar el recambio: " . mysqli_error($conexion);
        }

    } else {
        $mensaje = "Faltan datos obligatorios (nombre o precio).";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Publicar recambio 3D</title>
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

<h1>Publicar recambio 3D</h1>

<div class="tarjeta">

<p><a href="index.php">Volver al inicio</a></p>
<p><a href="recambios.php">Ver recambios 3D</a></p>

<?php
if (!empty($mensaje)) {
    echo "<p><strong>$mensaje</strong></p>";
}
?>

<form action="publicar_recambio.php" method="post">

    <p>
        <label>Nombre del recambio:<br>
            <input type="text" name="nombre">
        </label>
    </p>

    <p>
        <label>Descripción:<br>
            <textarea name="descripcion" rows="4" cols="40"></textarea>
        </label>
    </p>

    <p>
        <label>Precio (€):<br>
            <input type="number" step="0.01" name="precio">
        </label>
    </p>

    <p>
        <label>Tipo de pieza (pata, tirador, bisagra…):<br>
            <input type="text" name="tipo">
        </label>
    </p>

    <p>
        <label>Compatible con (modelo, tipo de mueble…):<br>
            <input type="text" name="compatible_con">
        </label>
    </p>

    <p>
        <button type="submit">Publicar recambio 3D</button>
    </p>

</form>

    </div>
</main>

<footer>
    <div class="contenedor">
        GR-Inn - Proyecto Trabajo Fin de Grado
</footer>

</body>
</html>
