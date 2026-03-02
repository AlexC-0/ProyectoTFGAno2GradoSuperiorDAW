<?php
// Bootstrap/layout para sesion y rendering comun.
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
require_once 'conexion.php';

// Parametros de busqueda libre (texto + ubicacion).
$q = trim($_GET['q'] ?? '');
$ubicacion = trim($_GET['ubicacion'] ?? '');

if ($q === '' && $ubicacion === '') {
    $mensaje = 'Introduce al menos una palabra clave o una ubicacion para buscar.';
    $hay_resultados = false;
    $resultado = null;
} else {
    // Consulta incremental segun filtros activos.
    $sql = "SELECT * FROM muebles WHERE 1=1";

    if ($q !== '') {
        $q_like = '%' . mysqli_real_escape_string($conexion, $q) . '%';
        $sql .= " AND (titulo LIKE '$q_like' OR descripcion LIKE '$q_like')";
    }

    if ($ubicacion !== '') {
        $u_like = '%' . mysqli_real_escape_string($conexion, $ubicacion) . '%';
        $sql .= " AND (provincia LIKE '$u_like' OR localidad LIKE '$u_like')";
    }

    $sql .= ' ORDER BY fecha_publicacion DESC';
    $resultado = mysqli_query($conexion, $sql);

    if (!$resultado) {
        die('Error en la busqueda: ' . mysqli_error($conexion));
    }

    if (mysqli_num_rows($resultado) > 0) {
        $hay_resultados = true;
        $mensaje = 'Resultados de la busqueda.';
    } else {
        $hay_resultados = false;
        $mensaje = 'No se han encontrado muebles con esos criterios.';
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
<?php ew_render_header(['active' => 'muebles', 'brand_alt' => 'ECO & WOODS']); ?>

<main>
    <div class="contenedor">
        <h1>Busqueda de muebles</h1>

        <p><a href="index.php">Volver al inicio</a></p>
        <p><a href="muebles.php">Ver todos los muebles</a></p>

        <h2>Criterios de busqueda</h2>
        <ul>
            <li>Palabra clave: <strong><?php echo e($q); ?></strong></li>
            <li>Ubicacion: <strong><?php echo e($ubicacion); ?></strong></li>
        </ul>

        <p><strong><?php echo e($mensaje); ?></strong></p>

        <?php if ($hay_resultados && $resultado): ?>
            <?php while ($fila = mysqli_fetch_assoc($resultado)): ?>
                <div class="tarjeta">
                    <h3><?php echo e($fila['titulo']); ?></h3>
                    <p><strong>Descripcion:</strong> <?php echo e($fila['descripcion']); ?></p>
                    <p><strong>Precio:</strong> <?php echo e((string)$fila['precio']); ?> EUR</p>
                    <p><strong>Ubicacion:</strong> <?php echo e($fila['provincia']); ?> - <?php echo e($fila['localidad']); ?></p>
                    <p><strong>Estado:</strong> <?php echo e($fila['estado']); ?></p>
                    <p><a href="ver_mueble.php?id_mueble=<?php echo (int)$fila['id_mueble']; ?>">Ver detalles y resenas</a></p>
                </div>
                <hr>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</main>

<?php ew_render_footer(); ?>

<button id="btnTop" onclick="scrollToTop()">^</button>
<script src="js/app.js"></script>
</body>
</html>

