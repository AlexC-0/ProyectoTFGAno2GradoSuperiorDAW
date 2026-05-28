<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/validaciones_anuncios.php';
require 'conexion.php';

ew_require_admin('index.php');

$id_recambio = (int)($_POST['id_recambio'] ?? ($_GET['id_recambio'] ?? 0));
$errores = [];

if ($id_recambio <= 0) {
    header('Location: admin.php?seccion=recambios');
    exit;
}

$stmt = mysqli_prepare(
    $conexion,
    "SELECT id_recambio, nombre, descripcion, tipo, compatible_con, precio
     FROM recambios3d
     WHERE id_recambio = ?
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, 'i', $id_recambio);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$recambio = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($stmt);

if (!$recambio) {
    header('Location: admin.php?seccion=recambios');
    exit;
}

$nombre = $recambio['nombre'];
$descripcion = $recambio['descripcion'];
$tipo = $recambio['tipo'];
$compatible_con = $recambio['compatible_con'];
$precio = (string)$recambio['precio'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errores[] = "Sesion expirada. Recarga la pagina e intentalo de nuevo.";
    }

    $nombre = ew_normalize_text_input($_POST['nombre'] ?? '');
    $descripcion = ew_normalize_text_input($_POST['descripcion'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');
    $compatible_con = trim($_POST['compatible_con'] ?? '');
    $precio = trim($_POST['precio'] ?? '');

    if ($nombre === '' || $descripcion === '' || $tipo === '' || $compatible_con === '' || $precio === '') {
        $errores[] = "Todos los campos marcados con * son obligatorios.";
    }

    if (!ew_valid_title_text($nombre, 6, 120)) {
        $errores[] = "El nombre del recambio debe tener entre 6 y 120 caracteres, incluir al menos dos palabras y describir la pieza de forma comprensible.";
    }

    if (!ew_valid_description_text($descripcion)) {
        $errores[] = "La descripcion del recambio debe tener entre 40 y 1000 caracteres y explicar la pieza con frases legibles.";
    }

    if (!in_array($tipo, $tipos_recambio, true)) {
        $errores[] = "Debes seleccionar un tipo de recambio valido.";
    }

    if (!in_array($compatible_con, $compatibles_recambio, true)) {
        $errores[] = "Debes seleccionar una compatibilidad valida.";
    }

    if (!ew_valid_decimal_price($precio)) {
        $errores[] = "El precio debe ser un numero positivo con un maximo de dos decimales.";
    } else {
        $precio = str_replace(',', '.', $precio);
    }

    if (empty($errores)) {
        $stmt_upd = mysqli_prepare(
            $conexion,
            "UPDATE recambios3d
             SET nombre = ?, descripcion = ?, tipo = ?, compatible_con = ?, precio = ?
             WHERE id_recambio = ?"
        );
        $precio_float = (float)$precio;
        mysqli_stmt_bind_param($stmt_upd, 'ssssdi', $nombre, $descripcion, $tipo, $compatible_con, $precio_float, $id_recambio);
        $ok = mysqli_stmt_execute($stmt_upd);
        mysqli_stmt_close($stmt_upd);

        if ($ok) {
            header('Location: admin.php?seccion=recambios&editado=1');
            exit;
        }

        $errores[] = "No se pudo actualizar el recambio.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar recambio - ECO & WOODS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php ew_render_header(['active' => 'admin']); ?>

<main>
    <div class="contenedor publish-shell">
        <section class="section-head-card">
            <p class="section-head-kicker">Edicion de recambio</p>
            <h1>Editar recambio 3D</h1>
            <p>Modifica la informacion del recambio sin cambiar las imagenes ya subidas.</p>
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
            <form action="editar_recambio.php" method="post" class="formulario">
                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="id_recambio" value="<?php echo (int)$id_recambio; ?>">

                <p>
                    <label for="nombre">Nombre del recambio*:</label><br>
                    <input type="text" name="nombre" id="nombre" required minlength="6" maxlength="120" value="<?php echo e($nombre); ?>">
                </p>

                <p>
                    <label for="tipo">Tipo*:</label><br>
                    <select name="tipo" id="tipo" required>
                        <option value="">Selecciona un tipo</option>
                        <?php foreach ($tipos_recambio as $tipo_opcion): ?>
                            <option value="<?php echo e($tipo_opcion); ?>" <?php echo ($tipo_opcion === $tipo) ? 'selected' : ''; ?>>
                                <?php echo e($tipo_opcion); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>

                <p>
                    <label for="compatible_con">Compatible con*:</label><br>
                    <select name="compatible_con" id="compatible_con" required>
                        <option value="">Selecciona compatibilidad</option>
                        <?php foreach ($compatibles_recambio as $compatible_opcion): ?>
                            <option value="<?php echo e($compatible_opcion); ?>" <?php echo ($compatible_opcion === $compatible_con) ? 'selected' : ''; ?>>
                                <?php echo e($compatible_opcion); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>

                <p>
                    <label for="precio">Precio (€)*:</label><br>
                    <input type="number" step="0.01" min="0.01" name="precio" id="precio" required value="<?php echo e((string)$precio); ?>">
                </p>

                <p>
                    <label for="descripcion">Descripcion*:</label><br>
                    <textarea name="descripcion" id="descripcion" rows="5" cols="50" required minlength="40" maxlength="1000"><?php echo e($descripcion); ?></textarea>
                </p>

                <p>
                    <button type="submit">Guardar cambios</button>
                    <a class="btn-link-reset" href="admin.php?seccion=recambios">Cancelar</a>
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
