<?php
session_start();

// Solo usuarios logueados pueden publicar muebles
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

require 'conexion.php';

$errores = [];
$exito = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_usuario = (int) $_SESSION['usuario_id'];

    $titulo      = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio      = trim($_POST['precio'] ?? '');
    $provincia   = trim($_POST['provincia'] ?? '');
    $localidad   = trim($_POST['localidad'] ?? '');
    $estado      = trim($_POST['estado'] ?? '');
    $categoria   = trim($_POST['categoria'] ?? 'Otro');

    // Validaciones básicas
    if ($titulo === '' || $descripcion === '' || $precio === '' || $provincia === '' || $localidad === '' || $estado === '') {
        $errores[] = "Todos los campos marcados con * son obligatorios.";
    }

    if (!is_numeric($precio) || $precio < 0) {
        $errores[] = "El precio debe ser un número positivo.";
    }

    if ($categoria === '') {
        $categoria = 'Otro';
    }

    if (empty($errores)) {

        // ============================
        //   SUBIDA DE HASTA 5 IMÁGENES
        // ============================
        $maxImagenes = 5;
        $permitidas  = ['jpg', 'jpeg', 'png', 'webp'];
        $rutaUploads = __DIR__ . '/uploads';

        $imagenesGuardadas = [null, null, null, null, null];

        if (!is_dir($rutaUploads)) {
            mkdir($rutaUploads, 0775, true);
        }

        if (!empty($_FILES['imagenes']) && is_array($_FILES['imagenes']['name'])) {

            $totalFiles = count($_FILES['imagenes']['name']);

            for ($i = 0, $j = 0; $i < $totalFiles && $j < $maxImagenes; $i++) {

                $errorArchivo = $_FILES['imagenes']['error'][$i];

                if ($errorArchivo === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                if ($errorArchivo !== UPLOAD_ERR_OK) {
                    continue;
                }

                $tmpName        = $_FILES['imagenes']['tmp_name'][$i];
                $nombreOriginal = $_FILES['imagenes']['name'][$i];

                $info      = pathinfo($nombreOriginal);
                $extension = strtolower($info['extension'] ?? '');

                if (!in_array($extension, $permitidas)) {
                    continue;
                }

                $nuevoNombre = uniqid('mueble_', true) . '.' . $extension;
                $rutaDestino = $rutaUploads . '/' . $nuevoNombre;

                if (move_uploaded_file($tmpName, $rutaDestino)) {
                    $imagenesGuardadas[$j] = $nuevoNombre;
                    $j++;
                }
            }
        }

        // Escape de textos
        $titulo_esc      = mysqli_real_escape_string($conexion, $titulo);
        $descripcion_esc = mysqli_real_escape_string($conexion, $descripcion);
        $provincia_esc   = mysqli_real_escape_string($conexion, $provincia);
        $localidad_esc   = mysqli_real_escape_string($conexion, $localidad);
        $estado_esc      = mysqli_real_escape_string($conexion, $estado);
        $categoria_esc   = mysqli_real_escape_string($conexion, $categoria);
        $precio_num      = (float) $precio;

        // Mapear imágenes a columnas imagen, imagen2, ..., imagen5
        $img1 = $imagenesGuardadas[0] ? mysqli_real_escape_string($conexion, $imagenesGuardadas[0]) : null;
        $img2 = $imagenesGuardadas[1] ? mysqli_real_escape_string($conexion, $imagenesGuardadas[1]) : null;
        $img3 = $imagenesGuardadas[2] ? mysqli_real_escape_string($conexion, $imagenesGuardadas[2]) : null;
        $img4 = $imagenesGuardadas[3] ? mysqli_real_escape_string($conexion, $imagenesGuardadas[3]) : null;
        $img5 = $imagenesGuardadas[4] ? mysqli_real_escape_string($conexion, $imagenesGuardadas[4]) : null;

        $sql = "INSERT INTO muebles 
                (id_usuario, titulo, descripcion, precio, provincia, localidad, estado, categoria,
                 imagen, imagen2, imagen3, imagen4, imagen5, fecha_publicacion)
                VALUES
                (
                    $id_usuario,
                    '$titulo_esc',
                    '$descripcion_esc',
                    $precio_num,
                    '$provincia_esc',
                    '$localidad_esc',
                    '$estado_esc',
                    '$categoria_esc',
                    " . ($img1 ? "'$img1'" : "NULL") . ",
                    " . ($img2 ? "'$img2'" : "NULL") . ",
                    " . ($img3 ? "'$img3'" : "NULL") . ",
                    " . ($img4 ? "'$img4'" : "NULL") . ",
                    " . ($img5 ? "'$img5'" : "NULL") . ",
                    NOW()
                )";

        $ok = mysqli_query($conexion, $sql);

        if ($ok) {
            $exito = "Mueble publicado correctamente.";
            $titulo = $descripcion = $precio = $provincia = $localidad = $estado = '';
            $categoria = 'Otro';
        } else {
            $errores[] = "Error al publicar el mueble: " . mysqli_error($conexion);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Publicar mueble - ECO & WOODS</title>
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

        <h1>Publicar un mueble</h1>

        <?php if (!empty($errores)): ?>
            <div class="mensaje error">
                <ul>
                    <?php foreach ($errores as $e): ?>
                        <li><?php echo htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($exito !== ""): ?>
            <div class="mensaje exito">
                <?php echo htmlspecialchars($exito); ?>
            </div>
        <?php endif; ?>

        <form action="publicar.php" method="post" class="formulario formulario-dos-columnas" enctype="multipart/form-data">

            <div class="form-grid">

                <!-- Columna izquierda -->
                <div class="form-columna">
                    <p>
                        <label>Imágenes del mueble (máx. 5):<br>
                            <input
                                type="file"
                                name="imagenes[]"
                                accept="image/jpeg,image/png,image/webp"
                                multiple
                            >
                        </label>
                        <small>Puedes seleccionar varias a la vez (Ctrl+clic / Shift+clic).</small>
                    </p>

                    <p>
                        <label for="titulo">Título del anuncio*:</label><br>
                        <input type="text" name="titulo" id="titulo" required
                               value="<?php echo htmlspecialchars($titulo ?? ''); ?>">
                    </p>

                    <p>
                        <label for="precio">Precio (€)*:</label><br>
                        <input type="number" step="0.01" min="0" name="precio" id="precio" required
                               value="<?php echo htmlspecialchars($precio ?? ''); ?>">
                    </p>

                    <p>
                        <label for="categoria">Categoría del mueble*:</label><br>
                        <select name="categoria" id="categoria" required>
                            <?php
                            $categorias = ["Mesa", "Armario", "Silla", "Cama", "Estantería", "Sofá", "Otro"];
                            $categoria_actual = $categoria ?? 'Otro';

                            foreach ($categorias as $cat) {
                                $selected = ($cat === $categoria_actual) ? 'selected' : '';
                                echo "<option value=\"$cat\" $selected>$cat</option>";
                            }
                            ?>
                        </select>
                    </p>
                </div>

                <!-- Columna derecha -->
                <div class="form-columna">
                    <p>
                        <label for="provincia">Provincia*:</label><br>
                        <input type="text" name="provincia" id="provincia" required
                               value="<?php echo htmlspecialchars($provincia ?? ''); ?>">
                    </p>

                    <p>
                        <label for="localidad">Localidad*:</label><br>
                        <input type="text" name="localidad" id="localidad" required
                               value="<?php echo htmlspecialchars($localidad ?? ''); ?>">
                    </p>

                    <p>
                        <label for="estado">Estado del mueble*:</label><br>
                        <input type="text" name="estado" id="estado"
                               placeholder="Ej: Como nuevo, Buen estado, Usado..."
                               required value="<?php echo htmlspecialchars($estado ?? ''); ?>">
                    </p>

                    <p>
                        <label for="descripcion">Descripción*:</label><br>
                        <textarea name="descripcion" id="descripcion" rows="5" cols="50" required><?php
                            echo htmlspecialchars($descripcion ?? '');
                        ?></textarea>
                    </p>
                </div>

            </div>

            <p>
                <button type="submit">Publicar mueble</button>
            </p>

        </form>

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
