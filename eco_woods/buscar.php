<?php
require_once "conexion.php";

// Recoger parámetros de búsqueda (GET)
$q          = $_GET['q'] ?? '';
$ubicacion  = $_GET['ubicacion'] ?? '';

// Limpiar espacios
$q         = trim($q);
$ubicacion = trim($ubicacion);

// Si no se ha metido nada, no buscamos
if ($q === '' && $ubicacion === '') {
    $mensaje = "Introduce al menos una palabra clave o una ubicación para buscar.";
    $hay_resultados = false;
} else {

    // Empezamos a construir la consulta
    $sql = "SELECT * FROM muebles WHERE 1=1";

    // Buscar por palabra clave en título o descripción
    if ($q !== '') {
        $q_like = "%" . mysqli_real_escape_string($conexion, $q) . "%";
        $sql .= " AND (titulo LIKE '$q_like' OR descripcion LIKE '$q_like')";
    }

    // Buscar por provincia o localidad
    if ($ubicacion !== '') {
        $u_like = "%" . mysqli_real_escape_string($conexion, $ubicacion) . "%";
        $sql .= " AND (provincia LIKE '$u_like' OR localidad LIKE '$u_like')";
    }

    $sql .= " ORDER BY fecha_publicacion DESC";

    $resultado = mysqli_query($conexion, $sql);

    if (!$resultado) {
        die("Error en la búsqueda: " . mysqli_error($conexion));
    }

    if (mysqli_num_rows($resultado) > 0) {
        $hay_resultados = true;
        $mensaje = "Resultados de la búsqueda.";
    } else {
        $hay_resultados = false;
        $mensaje = "No se han encontrado muebles con esos criterios.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Buscar muebles</title>
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

<h1>Búsqueda de muebles</h1>

<p><a href="index.php">Volver al inicio</a></p>
<p><a href="muebles.php">Ver todos los muebles</a></p>

<h2>Criterios de búsqueda</h2>

<ul>
    <li>Palabra clave: <strong><?php echo htmlspecialchars($q); ?></strong></li>
    <li>Ubicación: <strong><?php echo htmlspecialchars($ubicacion); ?></strong></li>
</ul>

<p><strong><?php echo $mensaje; ?></strong></p>

<?php
if ($hay_resultados) {

    while ($fila = mysqli_fetch_assoc($resultado)) {

		echo '<div class="tarjeta">';
        echo "<h3>" . $fila['titulo'] . "</h3>";
        echo "<p><strong>Descripción:</strong> " . $fila['descripcion'] . "</p>";
        echo "<p><strong>Precio:</strong> " . $fila['precio'] . " €</p>";
        echo "<p><strong>Ubicación:</strong> " . $fila['provincia'] . " - " . $fila['localidad'] . "</p>";
        echo "<p><strong>Estado:</strong> " . $fila['estado'] . "</p>";
        echo "<hr>";
        echo "</div>";
		
		echo "<p><strong>Estado:</strong> " . $fila['estado'] . "</p>";
		echo '<p><a href="ver_mueble.php?id_mueble=' . $fila['id_mueble'] . '">Ver detalles y reseñas</a></p>';
		echo "<hr>";
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
