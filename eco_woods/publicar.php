<?php
/*
DOCUMENTACION_PASO4
Pantalla de publicacion de muebles y recambios.
- Requiere login y valida formularios con CSRF.
- Permite rol admin para publicar tambien recambios 3D.
- Gestiona carga de imagenes, validacion y guardado en base de datos.
*/
// Arranque común de sesión/utilidades + control de acceso.
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

// Solo usuarios autenticados pueden publicar.
ew_require_login('login.php');
require 'conexion.php';

function columnExists($conexion, $tabla, $columna) {
    $tabla_esc = mysqli_real_escape_string($conexion, $tabla);
    $col_esc = mysqli_real_escape_string($conexion, $columna);
    $sql = "SHOW COLUMNS FROM `$tabla_esc` LIKE '$col_esc'";
    $res = mysqli_query($conexion, $sql);
    return ($res && mysqli_num_rows($res) > 0);
}

$errores = [];
$exito = "";

$es_admin = (!empty($_SESSION['es_admin']) && $_SESSION['es_admin'] == 1);

// Por defecto:
// - usuario normal: mueble
// - admin: mueble (hasta que elija recambio)
$tipo_publicacion = 'mueble';
if ($es_admin) {
    $tipo_publicacion = trim($_POST['tipo_publicacion'] ?? ($_GET['tipo_publicacion'] ?? 'mueble'));
    if ($tipo_publicacion !== 'mueble' && $tipo_publicacion !== 'recambio') {
        $tipo_publicacion = 'mueble';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Protege formularios de alta de contenido frente a envíos externos.
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errores[] = "Sesion expirada. Recarga la pagina e intentalo de nuevo.";
    }

    $id_usuario = (int) $_SESSION['usuario_id'];

    if (!$es_admin) {
        $tipo_publicacion = 'mueble';
    }

    if ($tipo_publicacion === 'mueble') {

        $titulo      = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $precio      = trim($_POST['precio'] ?? '');
        $provincia   = trim($_POST['provincia'] ?? '');
        $localidad   = trim($_POST['localidad'] ?? '');
        $estado      = trim($_POST['estado'] ?? '');
        $categoria   = trim($_POST['categoria'] ?? 'Otro');

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

            $titulo_esc      = mysqli_real_escape_string($conexion, $titulo);
            $descripcion_esc = mysqli_real_escape_string($conexion, $descripcion);
            $provincia_esc   = mysqli_real_escape_string($conexion, $provincia);
            $localidad_esc   = mysqli_real_escape_string($conexion, $localidad);
            $estado_esc      = mysqli_real_escape_string($conexion, $estado);
            $categoria_esc   = mysqli_real_escape_string($conexion, $categoria);
            $precio_num      = (float) $precio;

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

    } else { // recambio (solo admin)

        if (!$es_admin) {
            $errores[] = "No tienes permisos para publicar recambios.";
        } else {

            $nombre         = trim($_POST['nombre'] ?? '');
            $descripcion_r  = trim($_POST['descripcion_recambio'] ?? '');
            $tipo_r         = trim($_POST['tipo'] ?? '');
            $compatible_con = trim($_POST['compatible_con'] ?? '');
            $precio_r       = trim($_POST['precio_recambio'] ?? '');

            if ($nombre === '' || $descripcion_r === '' || $tipo_r === '' || $compatible_con === '' || $precio_r === '') {
                $errores[] = "Todos los campos marcados con * son obligatorios.";
            }

            if (!is_numeric($precio_r) || $precio_r < 0) {
                $errores[] = "El precio debe ser un número positivo.";
            }

            if (empty($errores)) {

                $maxImagenes = 5;
                $permitidas  = ['jpg', 'jpeg', 'png', 'webp'];
                $rutaUploads = __DIR__ . '/uploads';

                $imagenesGuardadasRec = [null, null, null, null, null];

                if (!is_dir($rutaUploads)) {
                    mkdir($rutaUploads, 0775, true);
                }

                if (!empty($_FILES['imagenes_recambio']) && is_array($_FILES['imagenes_recambio']['name'])) {

                    $totalFiles = count($_FILES['imagenes_recambio']['name']);

                    for ($i = 0, $j = 0; $i < $totalFiles && $j < $maxImagenes; $i++) {

                        $errorArchivo = $_FILES['imagenes_recambio']['error'][$i];

                        if ($errorArchivo === UPLOAD_ERR_NO_FILE) {
                            continue;
                        }

                        if ($errorArchivo !== UPLOAD_ERR_OK) {
                            continue;
                        }

                        $tmpName        = $_FILES['imagenes_recambio']['tmp_name'][$i];
                        $nombreOriginal = $_FILES['imagenes_recambio']['name'][$i];

                        $info      = pathinfo($nombreOriginal);
                        $extension = strtolower($info['extension'] ?? '');

                        if (!in_array($extension, $permitidas)) {
                            continue;
                        }

                        $nuevoNombre = uniqid('recambio_', true) . '.' . $extension;
                        $rutaDestino = $rutaUploads . '/' . $nuevoNombre;

                        if (move_uploaded_file($tmpName, $rutaDestino)) {
                            $imagenesGuardadasRec[$j] = $nuevoNombre;
                            $j++;
                        }
                    }
                }

                $nombre_esc         = mysqli_real_escape_string($conexion, $nombre);
                $descripcion_r_esc  = mysqli_real_escape_string($conexion, $descripcion_r);
                $tipo_r_esc         = mysqli_real_escape_string($conexion, $tipo_r);
                $compatible_con_esc = mysqli_real_escape_string($conexion, $compatible_con);
                $precio_r_num       = (float)$precio_r;

                $col1 = columnExists($conexion, 'recambios3d', 'imagen');
                $col2 = columnExists($conexion, 'recambios3d', 'imagen2');
                $col3 = columnExists($conexion, 'recambios3d', 'imagen3');
                $col4 = columnExists($conexion, 'recambios3d', 'imagen4');
                $col5 = columnExists($conexion, 'recambios3d', 'imagen5');

                $img1r = $imagenesGuardadasRec[0] ? mysqli_real_escape_string($conexion, $imagenesGuardadasRec[0]) : null;
                $img2r = $imagenesGuardadasRec[1] ? mysqli_real_escape_string($conexion, $imagenesGuardadasRec[1]) : null;
                $img3r = $imagenesGuardadasRec[2] ? mysqli_real_escape_string($conexion, $imagenesGuardadasRec[2]) : null;
                $img4r = $imagenesGuardadasRec[3] ? mysqli_real_escape_string($conexion, $imagenesGuardadasRec[3]) : null;
                $img5r = $imagenesGuardadasRec[4] ? mysqli_real_escape_string($conexion, $imagenesGuardadasRec[4]) : null;

                $campos = ["nombre", "descripcion", "tipo", "compatible_con", "precio"];
                $valores = ["'$nombre_esc'", "'$descripcion_r_esc'", "'$tipo_r_esc'", "'$compatible_con_esc'", "$precio_r_num"];

                if ($col1) { $campos[] = "imagen";  $valores[] = ($img1r ? "'$img1r'" : "NULL"); }
                if ($col2) { $campos[] = "imagen2"; $valores[] = ($img2r ? "'$img2r'" : "NULL"); }
                if ($col3) { $campos[] = "imagen3"; $valores[] = ($img3r ? "'$img3r'" : "NULL"); }
                if ($col4) { $campos[] = "imagen4"; $valores[] = ($img4r ? "'$img4r'" : "NULL"); }
                if ($col5) { $campos[] = "imagen5"; $valores[] = ($img5r ? "'$img5r'" : "NULL"); }

                $sql = "INSERT INTO recambios3d (" . implode(", ", $campos) . ")
                        VALUES (" . implode(", ", $valores) . ")";

                $ok = mysqli_query($conexion, $sql);

                if ($ok) {
                    $exito = "Recambio 3D publicado correctamente.";
                    $nombre = $descripcion_r = $tipo_r = $compatible_con = $precio_r = '';
                } else {
                    $errores[] = "Error al publicar el recambio: " . mysqli_error($conexion);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo $es_admin ? "Publicar - ECO & WOODS" : "Publicar mueble - ECO & WOODS"; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>
    <div class="contenedor">

    <h1 style="display:flex; align-items:center;">
        <img src="uploads/Verde.png"
            alt="ECO & WOODS"
            style="height:180px; width:auto; object-fit:contain; display:block;">
    </h1>

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

                <?php if ($es_admin): ?>
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
    <div class="contenedor publish-shell">

        <div class="landing-acciones cart-links">
            <a href="index.php" class="btn-ver">Volver al inicio</a>
        </div>

        <?php if ($es_admin): ?>
            <h1>Publicar</h1>
        <?php else: ?>
            <h1>Publicar un mueble</h1>
        <?php endif; ?>

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

        <?php if ($es_admin): ?>
            <form action="publicar.php" method="post" class="formulario">
                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                <p>
                    <label for="tipo_publicacion"><strong>¿Qué quieres publicar?</strong></label><br>
                    <select name="tipo_publicacion" id="tipo_publicacion" onchange="this.form.submit()">
                        <option value="mueble" <?php echo ($tipo_publicacion === 'mueble') ? 'selected' : ''; ?>>Mueble</option>
                        <option value="recambio" <?php echo ($tipo_publicacion === 'recambio') ? 'selected' : ''; ?>>Recambio 3D</option>
                    </select>
                </p>
            </form>
        <?php endif; ?>

        <?php if (!$es_admin || $tipo_publicacion === 'mueble'): ?>

            <form action="publicar.php" method="post" class="formulario formulario-dos-columnas" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

                <?php if ($es_admin): ?>
                    <input type="hidden" name="tipo_publicacion" value="mueble">
                <?php endif; ?>

                <div class="form-grid">

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

        <?php else: ?>

            <h2>Publicar recambio 3D</h2>

            <form action="publicar.php" method="post" class="formulario" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

                <input type="hidden" name="tipo_publicacion" value="recambio">

                <p>
                    <label>Imágenes del recambio (máx. 5):<br>
                        <input
                            type="file"
                            name="imagenes_recambio[]"
                            accept="image/jpeg,image/png,image/webp"
                            multiple
                        >
                    </label>
                    <small>Puedes seleccionar varias a la vez (Ctrl+clic / Shift+clic).</small>
                </p>

                <p>
                    <label for="nombre">Nombre del recambio*:</label><br>
                    <input type="text" name="nombre" id="nombre" required
                           value="<?php echo htmlspecialchars($nombre ?? ''); ?>">
                </p>

                <p>
                    <label for="tipo">Tipo*:</label><br>
                    <input type="text" name="tipo" id="tipo" required
                           placeholder="Ej: bisagra, tope, pieza..."
                           value="<?php echo htmlspecialchars($tipo_r ?? ''); ?>">
                </p>

                <p>
                    <label for="compatible_con">Compatible con*:</label><br>
                    <input type="text" name="compatible_con" id="compatible_con" required
                           placeholder="Ej: Mesa, Armario, Silla..."
                           value="<?php echo htmlspecialchars($compatible_con ?? ''); ?>">
                </p>

                <p>
                    <label for="precio_recambio">Precio (€)*:</label><br>
                    <input type="number" step="0.01" min="0" name="precio_recambio" id="precio_recambio" required
                           value="<?php echo htmlspecialchars($precio_r ?? ''); ?>">
                </p>

                <p>
                    <label for="descripcion_recambio">Descripción*:</label><br>
                    <textarea name="descripcion_recambio" id="descripcion_recambio" rows="5" cols="50" required><?php
                        echo htmlspecialchars($descripcion_r ?? '');
                    ?></textarea>
                </p>

                <p>
                    <button type="submit">Publicar recambio</button>
                </p>

            </form>

        <?php endif; ?>

    </div>
</main>

<footer>
    <div class="contenedor">
        GR-Inn - Proyecto Trabajo Fin de Grado
    </div>
</footer>

<button id="btnTop" onclick="scrollToTop()">▲</button>
<script src="js/app.js"></script>

</body>
</html>

