<?php
/*
DOCUMENTACION_EXPLICATIVA_TFG
Que hace: Guarda un mensaje entre usuarios dentro de la plataforma.
Por que se hizo asi: Se valida remitente, destinatario y contenido antes de insertar.
Para que sirve: Habilita comunicacion segura para cerrar compras o resolver dudas.
*/
/*
DOCUMENTACION_PASO4
Formulario para contactar con vendedor desde un anuncio.
*/
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require 'conexion.php';

ew_require_login('login.php');
$id_usuario_actual = (int)$_SESSION['usuario_id'];

if (!isset($_GET['id_mueble'])) {
    header("Location: muebles.php");
    exit;
}
$id_mueble = (int)$_GET['id_mueble'];

function ew_stmt_result(mysqli $conexion, string $sql, string $types = '', array $params = [])
{
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return false;
    }
    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return false;
    }
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

function ew_stmt_execute(mysqli $conexion, string $sql, string $types, array $params): bool
{
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

$res_mueble = ew_stmt_result(
    $conexion,
    "SELECT m.id_mueble, m.titulo, m.id_usuario AS id_vendedor, u.nombre AS nombre_vendedor
     FROM muebles m
     JOIN usuarios u ON m.id_usuario = u.id_usuario
     WHERE m.id_mueble = ?
     LIMIT 1",
    'i',
    [$id_mueble]
);

if (!$res_mueble || mysqli_num_rows($res_mueble) === 0) {
    die("El mueble no existe.");
}

$datos_mueble = mysqli_fetch_assoc($res_mueble);
$id_vendedor = (int)$datos_mueble['id_vendedor'];

if ($id_vendedor === $id_usuario_actual) {
    header("Location: ver_mueble.php?id_mueble=$id_mueble");
    exit;
}

$errores = [];
$exito = '';
$asunto = '';
$cuerpo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errores[] = "Sesion expirada. Recarga la pagina e intentalo de nuevo.";
    }

    $asunto = trim((string)($_POST['asunto'] ?? ''));
    $cuerpo = trim((string)($_POST['cuerpo'] ?? ''));

    if ($asunto === '' || $cuerpo === '') {
        $errores[] = "Debes rellenar asunto y mensaje.";
    }

    if (empty($errores)) {
        $ok = ew_stmt_execute(
            $conexion,
            "INSERT INTO mensajes (id_remitente, id_destinatario, id_mueble, asunto, cuerpo)
             VALUES (?, ?, ?, ?, ?)",
            'iiiss',
            [$id_usuario_actual, $id_vendedor, $id_mueble, $asunto, $cuerpo]
        );

        if ($ok) {
            $exito = "Mensaje enviado correctamente.";
            $asunto = '';
            $cuerpo = '';
        } else {
            $errores[] = "Error al enviar el mensaje. Intentalo de nuevo.";
        }
    }
}

$asunto_por_defecto = "Consulta sobre: " . $datos_mueble['titulo'];
if ($asunto === '') {
    $asunto = $asunto_por_defecto;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Enviar mensaje - ECO & WOODS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php ew_render_header(['active' => 'perfil']); ?>

<main>
    <div class="contenedor">
        <p><a href="ver_mueble.php?id_mueble=<?php echo (int)$id_mueble; ?>">Volver al mueble</a></p>

        <h1>Enviar mensaje al vendedor</h1>
        <p>
            <strong>Mueble:</strong> <?php echo e($datos_mueble['titulo']); ?><br>
            <strong>Vendedor:</strong> <?php echo e($datos_mueble['nombre_vendedor']); ?>
        </p>

        <?php if (!empty($errores)): ?>
            <div class="mensaje error">
                <ul>
                    <?php foreach ($errores as $e): ?>
                        <li><?php echo e($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($exito !== ""): ?>
            <div class="mensaje exito"><?php echo e($exito); ?></div>
        <?php endif; ?>

        <form action="enviar_mensaje.php?id_mueble=<?php echo (int)$id_mueble; ?>" method="post" class="formulario">
            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
            <p>
                <label for="asunto">Asunto</label><br>
                <input type="text" name="asunto" id="asunto" required value="<?php echo e($asunto); ?>">
            </p>
            <p>
                <label for="cuerpo">Mensaje</label><br>
                <textarea name="cuerpo" id="cuerpo" rows="6" required><?php echo e($cuerpo); ?></textarea>
            </p>
            <p><button type="submit">Enviar mensaje</button></p>
        </form>
    </div>
</main>

<?php ew_render_footer(); ?>
<button id="btnTop" onclick="scrollToTop()">â–²</button>
<script src="js/app.js"></script>

</body>
</html>

