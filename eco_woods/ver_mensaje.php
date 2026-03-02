<?php
/*
DOCUMENTACION_PASO4
Lectura de mensaje individual y respuesta en hilo.
- Solo participantes del mensaje pueden acceder.
- Marca como leido cuando corresponde al destinatario.
- Permite responder por POST con validacion CSRF.
*/
// Vista de mensaje + respuesta:
// - acceso autenticado
// - solo remitente/destinatario puede leer
// - respuesta por POST protegido con CSRF
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/auth.php';

ew_require_login('login.php');
require 'conexion.php';

$id_usuario_actual = (int)$_SESSION['usuario_id'];
$errores = [];
$exito = '';

$id_mensaje = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_mensaje <= 0) {
    header('Location: mi_perfil.php');
    exit;
}

$sql = "SELECT msg.*,
               ur.nombre AS nombre_remitente,
               ud.nombre AS nombre_destinatario,
               m.titulo AS titulo_mueble,
               m.id_mueble AS id_mueble
        FROM mensajes msg
        JOIN usuarios ur ON msg.id_remitente = ur.id_usuario
        JOIN usuarios ud ON msg.id_destinatario = ud.id_usuario
        LEFT JOIN muebles m ON msg.id_mueble = m.id_mueble
        WHERE msg.id_mensaje = $id_mensaje
        LIMIT 1";
$res = mysqli_query($conexion, $sql);

if (!$res || mysqli_num_rows($res) === 0) {
    header('Location: mi_perfil.php');
    exit;
}

$mensaje = mysqli_fetch_assoc($res);
$id_remitente = (int)$mensaje['id_remitente'];
$id_destinatario = (int)$mensaje['id_destinatario'];

// ACL estricta: solo participantes del hilo pueden visualizarlo.
if ($id_usuario_actual !== $id_remitente && $id_usuario_actual !== $id_destinatario) {
    header('Location: mi_perfil.php');
    exit;
}

// Marcado de leido solo cuando el receptor abre el mensaje.
if ($id_usuario_actual === $id_destinatario && (int)$mensaje['leido'] === 0) {
    $sql_leido = "UPDATE mensajes SET leido = 1 WHERE id_mensaje = $id_mensaje LIMIT 1";
    mysqli_query($conexion, $sql_leido);
}

$id_destinatario_respuesta = ($id_usuario_actual === $id_remitente) ? $id_destinatario : $id_remitente;
$id_mueble_asociado = !empty($mensaje['id_mueble']) ? (int)$mensaje['id_mueble'] : null;

$asunto_original = (string)$mensaje['asunto'];
$asunto_respuesta = (strpos($asunto_original, 'RE: ') === 0) ? $asunto_original : ('RE: ' . $asunto_original);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errores[] = 'Sesion expirada. Recarga la pagina e intentalo de nuevo.';
    }

    $cuerpo = trim($_POST['cuerpo'] ?? '');
    if ($cuerpo === '') {
        $errores[] = 'El mensaje de respuesta no puede estar vacio.';
    }

    if (empty($errores)) {
        $cuerpo_esc = mysqli_real_escape_string($conexion, $cuerpo);
        $asunto_esc = mysqli_real_escape_string($conexion, $asunto_respuesta);
        $id_destino = (int)$id_destinatario_respuesta;

        $sql_resp = "INSERT INTO mensajes
                     (id_remitente, id_destinatario, id_mueble, asunto, cuerpo)
                     VALUES
                     ($id_usuario_actual, $id_destino, " .
                     ($id_mueble_asociado !== null ? $id_mueble_asociado : "NULL") . ",
                     '$asunto_esc', '$cuerpo_esc')";

        $ok = mysqli_query($conexion, $sql_resp);
        if ($ok) {
            $exito = 'Respuesta enviada correctamente.';
        } else {
            $errores[] = 'Error al enviar la respuesta. Intentalo de nuevo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mensaje - ECO & WOODS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php ew_render_header(['active' => 'perfil']); ?>

<main>
    <div class="contenedor">
        <div style="margin-bottom:20px;">
            <a href="mi_perfil.php" class="btn">Volver a mi perfil</a>
        </div>

        <article class="tarjeta">
            <h1><?php echo e($mensaje['asunto']); ?></h1>
            <p>
                <strong>De:</strong> <?php echo e($mensaje['nombre_remitente']); ?><br>
                <strong>Para:</strong> <?php echo e($mensaje['nombre_destinatario']); ?><br>
                <strong>Fecha:</strong> <?php echo e($mensaje['fecha_envio']); ?>
            </p>

            <?php if (!empty($mensaje['titulo_mueble']) && !empty($mensaje['id_mueble'])): ?>
                <p>
                    <strong>Mueble relacionado:</strong>
                    <a href="ver_mueble.php?id_mueble=<?php echo (int)$mensaje['id_mueble']; ?>">
                        <?php echo e($mensaje['titulo_mueble']); ?>
                    </a>
                </p>
            <?php endif; ?>

            <hr>
            <p><?php echo nl2br(e($mensaje['cuerpo'])); ?></p>
        </article>

        <hr>

        <h2>Responder</h2>

        <?php if (!empty($errores)): ?>
            <div class="mensaje error">
                <ul>
                    <?php foreach ($errores as $e): ?>
                        <li><?php echo e($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($exito !== ''): ?>
            <div class="mensaje exito"><?php echo e($exito); ?></div>
        <?php endif; ?>

        <form action="ver_mensaje.php?id=<?php echo (int)$id_mensaje; ?>" method="post" class="formulario">
            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

            <p>
                <label>Asunto</label><br>
                <input type="text" value="<?php echo e($asunto_respuesta); ?>" disabled>
            </p>

            <p>
                <label for="cuerpo">Mensaje</label><br>
                <textarea name="cuerpo" id="cuerpo" rows="6" required></textarea>
            </p>

            <p>
                <button type="submit">Enviar respuesta</button>
            </p>
        </form>
    </div>
</main>

<?php ew_render_footer(); ?>

<button id="btnTop" onclick="scrollToTop()">▲</button>
<script src="js/app.js"></script>

</body>
</html>

