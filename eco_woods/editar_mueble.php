<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/validaciones_anuncios.php';
require 'conexion.php';

ew_require_login('login.php');

$id_usuario = (int)$_SESSION['usuario_id'];
$es_admin = ew_is_admin();
$id_mueble = (int)($_POST['id_mueble'] ?? ($_GET['id_mueble'] ?? 0));
$errores = [];

if ($id_mueble <= 0) {
    header('Location: ' . ($es_admin ? 'admin.php?seccion=muebles' : 'mi_perfil.php'));
    exit;
}

$sql_mueble = "SELECT id_mueble, id_usuario, titulo, descripcion, precio, provincia, localidad, estado, categoria
               FROM muebles
               WHERE id_mueble = ?";

if (!$es_admin) {
    $sql_mueble .= " AND id_usuario = ?";
}

$sql_mueble .= " LIMIT 1";
$stmt = mysqli_prepare($conexion, $sql_mueble);
if ($es_admin) {
    mysqli_stmt_bind_param($stmt, 'i', $id_mueble);
} else {
    mysqli_stmt_bind_param($stmt, 'ii', $id_mueble, $id_usuario);
}
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$mueble = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($stmt);

if (!$mueble) {
    header('Location: ' . ($es_admin ? 'admin.php?seccion=muebles' : 'mi_perfil.php'));
    exit;
}

$titulo = $mueble['titulo'];
$descripcion = $mueble['descripcion'];
$precio = (string)$mueble['precio'];
$provincia = $mueble['provincia'];
$localidad = $mueble['localidad'];
$estado = $mueble['estado'];
$categoria = $mueble['categoria'] ?: 'Otro';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errores[] = "Sesion expirada. Recarga la pagina e intentalo de nuevo.";
    }

    $titulo = ew_normalize_text_input($_POST['titulo'] ?? '');
    $descripcion = ew_normalize_text_input($_POST['descripcion'] ?? '');
    $precio = trim($_POST['precio'] ?? '');
    $provincia = ew_normalize_text_input($_POST['provincia'] ?? '');
    $localidad = ew_normalize_text_input($_POST['localidad'] ?? '');
    $estado = trim($_POST['estado'] ?? '');
    $categoria = trim($_POST['categoria'] ?? 'Otro');

    if ($titulo === '' || $descripcion === '' || $precio === '' || $provincia === '' || $localidad === '' || $estado === '') {
        $errores[] = "Todos los campos marcados con * son obligatorios.";
    }

    if (!ew_valid_title_text($titulo, 6, 120)) {
        $errores[] = "El titulo debe tener entre 6 y 120 caracteres, incluir al menos dos palabras y describir el mueble de forma comprensible.";
    }

    if (!ew_valid_description_text($descripcion)) {
        $errores[] = "La descripcion debe tener entre 40 y 1000 caracteres y explicar el anuncio con frases legibles.";
    }

    if (!ew_valid_place_text($provincia) || !ew_valid_place_text($localidad)) {
        $errores[] = "La provincia y la localidad deben contener nombres reales, sin numeros ni texto sin sentido.";
    }

    if (!ew_valid_decimal_price($precio)) {
        $errores[] = "El precio debe ser un numero positivo con un maximo de dos decimales.";
    } else {
        $precio = str_replace(',', '.', $precio);
    }

    if (!in_array($estado, $estados_mueble, true)) {
        $errores[] = "Debes seleccionar un estado valido para el mueble.";
    }

    if (!in_array($categoria, $categorias, true)) {
        $categoria = 'Otro';
    }

    if (empty($errores)) {
        if ($es_admin) {
            $stmt_upd = mysqli_prepare(
                $conexion,
                "UPDATE muebles
                 SET titulo = ?, descripcion = ?, precio = ?, provincia = ?, localidad = ?, estado = ?, categoria = ?
                 WHERE id_mueble = ?"
            );
            $precio_float = (float)$precio;
            mysqli_stmt_bind_param($stmt_upd, 'ssdssssi', $titulo, $descripcion, $precio_float, $provincia, $localidad, $estado, $categoria, $id_mueble);
        } else {
            $stmt_upd = mysqli_prepare(
                $conexion,
                "UPDATE muebles
                 SET titulo = ?, descripcion = ?, precio = ?, provincia = ?, localidad = ?, estado = ?, categoria = ?
                 WHERE id_mueble = ? AND id_usuario = ?"
            );
            $precio_float = (float)$precio;
            mysqli_stmt_bind_param($stmt_upd, 'ssdssssii', $titulo, $descripcion, $precio_float, $provincia, $localidad, $estado, $categoria, $id_mueble, $id_usuario);
        }

        $ok = $stmt_upd && mysqli_stmt_execute($stmt_upd);
        if ($stmt_upd) {
            mysqli_stmt_close($stmt_upd);
        }

        if ($ok) {
            header('Location: ' . ($es_admin ? 'admin.php?seccion=muebles&editado=1' : 'mi_perfil.php?editado=1'));
            exit;
        }

        $errores[] = "No se pudo actualizar el mueble.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar mueble - ECO & WOODS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php ew_render_header(['active' => $es_admin ? 'admin' : 'perfil']); ?>

<main>
    <div class="contenedor publish-shell">
        <section class="section-head-card">
            <p class="section-head-kicker">Edicion de anuncio</p>
            <h1>Editar mueble</h1>
            <p>Modifica la informacion del anuncio sin cambiar las imagenes ya subidas.</p>
        </section>

        <?php if (!empty($errores)): ?>
            <section class="section-content-card">
                <div class="mensaje error">
                    <ul>
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>
        <?php endif; ?>

        <section class="section-form-card">
            <form action="editar_mueble.php" method="post" class="formulario formulario-dos-columnas">
                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="id_mueble" value="<?php echo (int)$id_mueble; ?>">

                <div class="form-grid">
                    <div class="form-columna">
                        <p>
                            <label for="titulo">Titulo del anuncio*:</label><br>
                            <input type="text" name="titulo" id="titulo" required minlength="6" maxlength="120" value="<?php echo e($titulo); ?>">
                        </p>

                        <p>
                            <label for="precio">Precio (€)*:</label><br>
                            <input type="number" step="0.01" min="0.01" name="precio" id="precio" required value="<?php echo e((string)$precio); ?>">
                        </p>

                        <p>
                            <label for="categoria">Categoria del mueble*:</label><br>
                            <select name="categoria" id="categoria" required>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo e($cat); ?>" <?php echo ($cat === $categoria) ? 'selected' : ''; ?>>
                                        <?php echo e($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                    </div>

                    <div class="form-columna">
                        <p>
                            <label for="provincia">Provincia*:</label><br>
                            <input type="text" name="provincia" id="provincia" required minlength="2" maxlength="80" pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s.'-]+" value="<?php echo e($provincia); ?>">
                        </p>

                        <p>
                            <label for="localidad">Localidad*:</label><br>
                            <input type="text" name="localidad" id="localidad" required minlength="2" maxlength="80" pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s.'-]+" value="<?php echo e($localidad); ?>">
                        </p>

                        <p>
                            <label for="estado">Estado del mueble*:</label><br>
                            <select name="estado" id="estado" required>
                                <option value="">Selecciona un estado</option>
                                <?php foreach ($estados_mueble as $estado_opcion): ?>
                                    <option value="<?php echo e($estado_opcion); ?>" <?php echo ($estado_opcion === $estado) ? 'selected' : ''; ?>>
                                        <?php echo e($estado_opcion); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </p>

                        <p>
                            <label for="descripcion">Descripcion*:</label><br>
                            <textarea name="descripcion" id="descripcion" rows="5" cols="50" required minlength="40" maxlength="1000"><?php echo e($descripcion); ?></textarea>
                        </p>
                    </div>
                </div>

                <p>
                    <button type="submit">Guardar cambios</button>
                    <a class="btn-link-reset" href="<?php echo $es_admin ? 'admin.php?seccion=muebles' : 'mi_perfil.php'; ?>">Cancelar</a>
                </p>
            </form>
        </section>
    </div>
</main>

<?php ew_render_footer(); ?>

<button id="btnTop" onclick="scrollToTop()">↑</button>
<script src="js/app.js"></script>

</body>
</html>
