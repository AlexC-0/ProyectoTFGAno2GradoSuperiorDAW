<?php














require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/validaciones_anuncios.php';


ew_require_login('login.php');
require 'conexion.php';

function columnExists($conexion, $tabla, $columna) {
    $stmt = mysqli_prepare(
        $conexion,
        "SELECT 1
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
           AND COLUMN_NAME = ?
         LIMIT 1"
    );
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, 'ss', $tabla, $columna);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return false;
    }
    $res = mysqli_stmt_get_result($stmt);
    $ok = ($res && mysqli_num_rows($res) > 0);
    mysqli_stmt_close($stmt);
    return $ok;
}

function ew_is_valid_uploaded_image(string $tmpName, string $extension, int $size, array $allowedExt, array $allowedMime, int $maxBytes): bool
{
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        return false;
    }
    if ($size <= 0 || $size > $maxBytes) {
        return false;
    }
    if (!in_array($extension, $allowedExt, true)) {
        return false;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? (string)finfo_file($finfo, $tmpName) : '';
    if ($finfo) {
        finfo_close($finfo);
    }
    if (!in_array($mime, $allowedMime, true)) {
        return false;
    }

    return getimagesize($tmpName) !== false;
}

function ew_stmt_execute(mysqli $conexion, string $sql, string $types, array $params): bool
{
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return false;
    }

    $bind = [];
    $bind[] = $types;
    foreach ($params as $k => $v) {
        $bind[] = &$params[$k];
    }

    if (!call_user_func_array([$stmt, 'bind_param'], $bind)) {
        mysqli_stmt_close($stmt);
        return false;
    }

    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

$errores = [];
$exito = "";

$es_admin = (!empty($_SESSION['es_admin']) && $_SESSION['es_admin'] == 1);

$tipo_publicacion = 'mueble';
if ($es_admin) {
    $tipo_publicacion = trim($_POST['tipo_publicacion'] ?? ($_GET['tipo_publicacion'] ?? 'mueble'));
    if ($tipo_publicacion !== 'mueble' && $tipo_publicacion !== 'recambio') {
        $tipo_publicacion = 'mueble';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errores[] = "Sesion expirada. Recarga la pagina e intentalo de nuevo.";
    }

    $id_usuario = (int) $_SESSION['usuario_id'];

    if (!$es_admin) {
        $tipo_publicacion = 'mueble';
    }

    if ($es_admin && !isset($_POST['titulo']) && !isset($_POST['nombre'])) {
    } elseif ($tipo_publicacion === 'mueble') {

        $titulo      = ew_normalize_text_input($_POST['titulo'] ?? '');
        $descripcion = ew_normalize_text_input($_POST['descripcion'] ?? '');
        $precio      = trim($_POST['precio'] ?? '');
        $provincia   = ew_normalize_text_input($_POST['provincia'] ?? '');
        $localidad   = ew_normalize_text_input($_POST['localidad'] ?? '');
        $estado      = trim($_POST['estado'] ?? '');
        $categoria   = trim($_POST['categoria'] ?? 'Otro');

        if ($titulo === '' || $descripcion === '' || $precio === '' || $provincia === '' || $localidad === '' || $estado === '') {
            $errores[] = "Todos los campos marcados con * son obligatorios.";
        }

        if (!ew_valid_title_text($titulo, 6, 120)) {
            $errores[] = "El tÃ­tulo debe tener entre 6 y 120 caracteres, incluir al menos dos palabras y describir el mueble de forma comprensible.";
        }

        if (!ew_valid_description_text($descripcion)) {
            $errores[] = "La descripciÃ³n debe tener entre 40 y 1000 caracteres y explicar el anuncio con frases legibles.";
        }

        if (!ew_valid_place_text($provincia) || !ew_valid_place_text($localidad)) {
            $errores[] = "La provincia y la localidad deben contener nombres reales, sin nÃºmeros ni texto sin sentido.";
        }

        if (!ew_valid_decimal_price($precio)) {
            $errores[] = "El precio debe ser un nÃºmero positivo con un mÃ¡ximo de dos decimales.";
        } else {
            $precio = str_replace(',', '.', $precio);
        }

        if (!in_array($estado, $estados_mueble, true)) {
            $errores[] = "Debes seleccionar un estado vÃ¡lido para el mueble.";
        }

        if (!in_array($categoria, $categorias, true)) {
            $categoria = 'Otro';
        }

        if (empty($errores)) {

            $maxImagenes = 5;
            $permitidas  = ['jpg', 'jpeg', 'png', 'webp'];
            $mimesPermitidos = ['image/jpeg', 'image/png', 'image/webp'];
            $maxBytesImagen = 5 * 1024 * 1024;
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
                    $tamArchivo     = (int)($_FILES['imagenes']['size'][$i] ?? 0);

                    $info      = pathinfo($nombreOriginal);
                    $extension = strtolower($info['extension'] ?? '');

                    if (!ew_is_valid_uploaded_image($tmpName, $extension, $tamArchivo, $permitidas, $mimesPermitidos, $maxBytesImagen)) {
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

            $sql = "INSERT INTO muebles
                    (id_usuario, titulo, descripcion, precio, provincia, localidad, estado, categoria,
                     imagen, imagen2, imagen3, imagen4, imagen5, fecha_publicacion)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $ok = ew_stmt_execute(
                $conexion,
                $sql,
                'issdsssssssss',
                [
                    $id_usuario,
                    $titulo,
                    $descripcion,
                    (float)$precio,
                    $provincia,
                    $localidad,
                    $estado,
                    $categoria,
                    $imagenesGuardadas[0],
                    $imagenesGuardadas[1],
                    $imagenesGuardadas[2],
                    $imagenesGuardadas[3],
                    $imagenesGuardadas[4],
                ]
            );

            if ($ok) {
                $exito = "Mueble publicado correctamente.";
                $titulo = $descripcion = $precio = $provincia = $localidad = $estado = '';
                $categoria = 'Otro';
            } else {
                $errores[] = "Error al publicar el mueble: " . mysqli_error($conexion);
            }
        }

    } else { 

        if (!$es_admin) {
            $errores[] = "No tienes permisos para publicar recambios.";
        } else {

            $nombre         = ew_normalize_text_input($_POST['nombre'] ?? '');
            $descripcion_r  = ew_normalize_text_input($_POST['descripcion_recambio'] ?? '');
            $tipo_r         = trim($_POST['tipo'] ?? '');
            $compatible_con = trim($_POST['compatible_con'] ?? '');
            $precio_r       = trim($_POST['precio_recambio'] ?? '');

            if ($nombre === '' || $descripcion_r === '' || $tipo_r === '' || $compatible_con === '' || $precio_r === '') {
                $errores[] = "Todos los campos marcados con * son obligatorios.";
            }

            if (!ew_valid_title_text($nombre, 6, 120)) {
                $errores[] = "El nombre del recambio debe tener entre 6 y 120 caracteres, incluir al menos dos palabras y describir la pieza de forma comprensible.";
            }

            if (!ew_valid_description_text($descripcion_r)) {
                $errores[] = "La descripciÃ³n del recambio debe tener entre 40 y 1000 caracteres y explicar la pieza con frases legibles.";
            }

            if (!in_array($tipo_r, $tipos_recambio, true)) {
                $errores[] = "Debes seleccionar un tipo de recambio vÃ¡lido.";
            }

            if (!in_array($compatible_con, $compatibles_recambio, true)) {
                $errores[] = "Debes seleccionar una compatibilidad vÃ¡lida.";
            }

            if (!ew_valid_decimal_price($precio_r)) {
                $errores[] = "El precio debe ser un nÃºmero positivo con un mÃ¡ximo de dos decimales.";
            } else {
                $precio_r = str_replace(',', '.', $precio_r);
            }

            if (empty($errores)) {

                $maxImagenes = 5;
                $permitidas  = ['jpg', 'jpeg', 'png', 'webp'];
                $mimesPermitidos = ['image/jpeg', 'image/png', 'image/webp'];
                $maxBytesImagen = 5 * 1024 * 1024;
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
                        $tamArchivo     = (int)($_FILES['imagenes_recambio']['size'][$i] ?? 0);

                        $info      = pathinfo($nombreOriginal);
                        $extension = strtolower($info['extension'] ?? '');

                        if (!ew_is_valid_uploaded_image($tmpName, $extension, $tamArchivo, $permitidas, $mimesPermitidos, $maxBytesImagen)) {
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

                $col1 = columnExists($conexion, 'recambios3d', 'imagen');
                $col2 = columnExists($conexion, 'recambios3d', 'imagen2');
                $col3 = columnExists($conexion, 'recambios3d', 'imagen3');
                $col4 = columnExists($conexion, 'recambios3d', 'imagen4');
                $col5 = columnExists($conexion, 'recambios3d', 'imagen5');

                $campos = ["nombre", "descripcion", "tipo", "compatible_con", "precio"];
                $valores = [$nombre, $descripcion_r, $tipo_r, $compatible_con, (float)$precio_r];
                $types = "ssssd";

                if ($col1) { $campos[] = "imagen";  $valores[] = $imagenesGuardadasRec[0]; $types .= 's'; }
                if ($col2) { $campos[] = "imagen2"; $valores[] = $imagenesGuardadasRec[1]; $types .= 's'; }
                if ($col3) { $campos[] = "imagen3"; $valores[] = $imagenesGuardadasRec[2]; $types .= 's'; }
                if ($col4) { $campos[] = "imagen4"; $valores[] = $imagenesGuardadasRec[3]; $types .= 's'; }
                if ($col5) { $campos[] = "imagen5"; $valores[] = $imagenesGuardadasRec[4]; $types .= 's'; }

                $placeholders = implode(", ", array_fill(0, count($campos), "?"));
                $sql = "INSERT INTO recambios3d (" . implode(", ", $campos) . ") VALUES (" . $placeholders . ")";
                $ok = ew_stmt_execute($conexion, $sql, $types, $valores);

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

<?php ew_render_header(['active' => 'publicar']); ?>

<main>
    <div class="contenedor publish-shell">
        <section class="section-head-card">
            <p class="section-head-kicker">Publicacion de contenido</p>
            <?php if ($es_admin): ?>
                <h1>Publicar</h1>
                <p>Desde aqui puedes dar de alta muebles y tambien recambios 3D.</p>
            <?php else: ?>
                <h1>Publicar un mueble</h1>
                <p>Completa los datos del anuncio y deja toda la informacion clara antes de publicarlo.</p>
            <?php endif; ?>
        </section>

        <?php if (!empty($errores)): ?>
            <section class="section-content-card">
                <div class="mensaje error">
                    <ul>
                        <?php foreach ($errores as $e): ?>
                            <li><?php echo htmlspecialchars($e); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($exito !== ""): ?>
            <section class="section-content-card">
                <div class="mensaje exito">
                    <?php echo htmlspecialchars($exito); ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($es_admin): ?>
            <section class="section-form-card">
                <form action="publicar.php" method="post" class="formulario">
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                    <p>
                        <label for="tipo_publicacion"><strong>Â¿QuÃ© quieres publicar?</strong></label><br>
                        <select name="tipo_publicacion" id="tipo_publicacion" onchange="this.form.submit()">
                            <option value="mueble" <?php echo ($tipo_publicacion === 'mueble') ? 'selected' : ''; ?>>Mueble</option>
                            <option value="recambio" <?php echo ($tipo_publicacion === 'recambio') ? 'selected' : ''; ?>>Recambio 3D</option>
                        </select>
                    </p>
                </form>
            </section>
        <?php endif; ?>

        <?php if (!$es_admin || $tipo_publicacion === 'mueble'): ?>
            <section class="section-form-card">
                <form action="publicar.php" method="post" class="formulario formulario-dos-columnas" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

                    <?php if ($es_admin): ?>
                        <input type="hidden" name="tipo_publicacion" value="mueble">
                    <?php endif; ?>

                    <div class="form-grid">

                        <div class="form-columna">
                            <p>
                                <label>ImÃ¡genes del mueble (mÃ¡x. 5):<br>
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
                                <label for="titulo">TÃ­tulo del anuncio*:</label><br>
                                <input type="text" name="titulo" id="titulo" required minlength="6" maxlength="120"
                                       value="<?php echo htmlspecialchars($titulo ?? ''); ?>">
                            </p>

                            <p>
                                <label for="precio">Precio (â‚¬)*:</label><br>
                                <input type="number" step="0.01" min="0.01" name="precio" id="precio" required
                                       value="<?php echo htmlspecialchars($precio ?? ''); ?>">
                            </p>

                            <p>
                                <label for="categoria">CategorÃ­a del mueble*:</label><br>
                                <select name="categoria" id="categoria" required>
                                    <?php
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
                                <input type="text" name="provincia" id="provincia" required minlength="2" maxlength="80"
                                       pattern="[A-Za-zÃÃ‰ÃÃ“ÃšÃœÃ‘Ã¡Ã©Ã­Ã³ÃºÃ¼Ã±\s.'-]+"
                                       value="<?php echo htmlspecialchars($provincia ?? ''); ?>">
                            </p>

                            <p>
                                <label for="localidad">Localidad*:</label><br>
                                <input type="text" name="localidad" id="localidad" required minlength="2" maxlength="80"
                                       pattern="[A-Za-zÃÃ‰ÃÃ“ÃšÃœÃ‘Ã¡Ã©Ã­Ã³ÃºÃ¼Ã±\s.'-]+"
                                       value="<?php echo htmlspecialchars($localidad ?? ''); ?>">
                            </p>

                            <p>
                                <label for="estado">Estado del mueble*:</label><br>
                                <select name="estado" id="estado" required>
                                    <option value="">Selecciona un estado</option>
                                    <?php
                                    $estado_actual = $estado ?? '';

                                    foreach ($estados_mueble as $estado_opcion) {
                                        $selected = ($estado_opcion === $estado_actual) ? 'selected' : '';
                                        echo "<option value=\"" . htmlspecialchars($estado_opcion) . "\" $selected>" . htmlspecialchars($estado_opcion) . "</option>";
                                    }
                                    ?>
                                </select>
                            </p>

                            <p>
                                <label for="descripcion">DescripciÃ³n*:</label><br>
                                <textarea name="descripcion" id="descripcion" rows="5" cols="50" required minlength="40" maxlength="1000"><?php
                                    echo htmlspecialchars($descripcion ?? '');
                                ?></textarea>
                            </p>
                        </div>

                    </div>

                    <p>
                        <button type="submit">Publicar mueble</button>
                    </p>

                </form>
            </section>

        <?php else: ?>
            <section class="section-head-card">
                <p class="section-head-kicker">Recambios 3D</p>
                <h2>Publicar recambio 3D</h2>
                <p>Usa este bloque para registrar piezas de reparacion con informacion clara y compatible.</p>
            </section>

            <section class="section-form-card">
                <form action="publicar.php" method="post" class="formulario" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

                    <input type="hidden" name="tipo_publicacion" value="recambio">

                    <p>
                        <label>ImÃ¡genes del recambio (mÃ¡x. 5):<br>
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
                        <input type="text" name="nombre" id="nombre" required minlength="6" maxlength="120"
                               value="<?php echo htmlspecialchars($nombre ?? ''); ?>">
                    </p>

                    <p>
                        <label for="tipo">Tipo*:</label><br>
                        <select name="tipo" id="tipo" required>
                            <option value="">Selecciona un tipo</option>
                            <?php
                            $tipo_actual = $tipo_r ?? '';

                            foreach ($tipos_recambio as $tipo_opcion) {
                                $selected = ($tipo_opcion === $tipo_actual) ? 'selected' : '';
                                echo "<option value=\"" . htmlspecialchars($tipo_opcion) . "\" $selected>" . htmlspecialchars($tipo_opcion) . "</option>";
                            }
                            ?>
                        </select>
                    </p>

                    <p>
                        <label for="compatible_con">Compatible con*:</label><br>
                        <select name="compatible_con" id="compatible_con" required>
                            <option value="">Selecciona compatibilidad</option>
                            <?php
                            $compatible_actual = $compatible_con ?? '';

                            foreach ($compatibles_recambio as $compatible_opcion) {
                                $selected = ($compatible_opcion === $compatible_actual) ? 'selected' : '';
                                echo "<option value=\"" . htmlspecialchars($compatible_opcion) . "\" $selected>" . htmlspecialchars($compatible_opcion) . "</option>";
                            }
                            ?>
                        </select>
                    </p>

                    <p>
                        <label for="precio_recambio">Precio (â‚¬)*:</label><br>
                        <input type="number" step="0.01" min="0.01" name="precio_recambio" id="precio_recambio" required
                               value="<?php echo htmlspecialchars($precio_r ?? ''); ?>">
                    </p>

                    <p>
                        <label for="descripcion_recambio">DescripciÃ³n*:</label><br>
                        <textarea name="descripcion_recambio" id="descripcion_recambio" rows="5" cols="50" required minlength="40" maxlength="1000"><?php
                            echo htmlspecialchars($descripcion_r ?? '');
                        ?></textarea>
                    </p>

                    <p>
                        <button type="submit">Publicar recambio</button>
                    </p>

                </form>
            </section>

        <?php endif; ?>

    </div>
</main>

<footer>
    <div class="contenedor">
        GR-Inn - Proyecto Trabajo Fin de Grado
    </div>
</footer>

<button id="btnTop" onclick="scrollToTop()">â†‘</button>
<script src="js/app.js"></script>

</body>
</html>


