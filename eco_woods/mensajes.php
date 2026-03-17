<?php
/*
DOCUMENTACION_EXPLICATIVA_TFG
Que hace: Muestra la bandeja de mensajes del usuario.
Por que se hizo asi: Organiza lectura de conversaciones para que sea simple de entender.
Para que sirve: Da seguimiento al contacto entre comprador y vendedor.
*/
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/auth.php';
require 'conexion.php';

/*BORRAR*/

ew_require_login('login.php');
$id_usuario = (int)$_SESSION['usuario_id'];

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

$res_recibidos = ew_stmt_result(
    $conexion,
    "SELECT msg.id_mensaje, msg.asunto, msg.cuerpo, msg.fecha_envio, msg.leido,
            u.nombre AS nombre_remitente,
            m.titulo AS titulo_mueble
     FROM mensajes msg
     JOIN usuarios u ON msg.id_remitente = u.id_usuario
     LEFT JOIN muebles m ON msg.id_mueble = m.id_mueble
     WHERE msg.id_destinatario = ?
     ORDER BY msg.fecha_envio DESC",
    'i',
    [$id_usuario]
);

$res_enviados = ew_stmt_result(
    $conexion,
    "SELECT msg.id_mensaje, msg.asunto, msg.cuerpo, msg.fecha_envio, msg.leido,
            u.nombre AS nombre_destinatario,
            m.titulo AS titulo_mueble
     FROM mensajes msg
     JOIN usuarios u ON msg.id_destinatario = u.id_usuario
     LEFT JOIN muebles m ON msg.id_mueble = m.id_mueble
     WHERE msg.id_remitente = ?
     ORDER BY msg.fecha_envio DESC",
    'i',
    [$id_usuario]
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis mensajes - ECO & WOODS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php ew_render_header(['active' => 'perfil']); ?>

<main>
    <div class="contenedor">
        <h1>Mis mensajes</h1>

        <section class="bloque-mensajes-recibidos">
            <h2>Recibidos</h2>
            <?php if ($res_recibidos && mysqli_num_rows($res_recibidos) > 0): ?>
                <div class="listado-tarjetas">
                    <?php while ($msg = mysqli_fetch_assoc($res_recibidos)): ?>
                        <article class="tarjeta">
                            <p><strong>De:</strong> <?php echo e($msg['nombre_remitente']); ?></p>
                            <p>
                                <strong>Asunto:</strong> <?php echo e($msg['asunto']); ?>
                                <?php if (!(int)$msg['leido']): ?>
                                    <span style="color:#c0392b; font-size:13px;">(no leido)</span>
                                <?php endif; ?>
                            </p>
                            <?php if (!empty($msg['titulo_mueble'])): ?>
                                <p><strong>Mueble:</strong> <?php echo e($msg['titulo_mueble']); ?></p>
                            <?php endif; ?>
                            <p><?php echo nl2br(e($msg['cuerpo'])); ?></p>
                            <p><small>Enviado: <?php echo e($msg['fecha_envio']); ?></small></p>
                        </article>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p>No tienes mensajes recibidos.</p>
            <?php endif; ?>
        </section>

        <hr>

        <section class="bloque-mensajes-enviados">
            <h2>Enviados</h2>
            <?php if ($res_enviados && mysqli_num_rows($res_enviados) > 0): ?>
                <div class="listado-tarjetas">
                    <?php while ($msg = mysqli_fetch_assoc($res_enviados)): ?>
                        <article class="tarjeta">
                            <p><strong>Para:</strong> <?php echo e($msg['nombre_destinatario']); ?></p>
                            <p><strong>Asunto:</strong> <?php echo e($msg['asunto']); ?></p>
                            <?php if (!empty($msg['titulo_mueble'])): ?>
                                <p><strong>Mueble:</strong> <?php echo e($msg['titulo_mueble']); ?></p>
                            <?php endif; ?>
                            <p><?php echo nl2br(e($msg['cuerpo'])); ?></p>
                            <p><small>Enviado: <?php echo e($msg['fecha_envio']); ?></small></p>
                        </article>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p>No has enviado mensajes todavia.</p>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php ew_render_footer(); ?>
<button id="btnTop" onclick="scrollToTop()">â–²</button>
<script src="js/app.js"></script>

</body>
</html>

