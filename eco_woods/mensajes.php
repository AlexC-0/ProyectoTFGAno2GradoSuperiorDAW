<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

require 'conexion.php';

$id_usuario = (int) $_SESSION['usuario_id'];

// MENSAJES RECIBIDOS
$sql_recibidos = "SELECT msg.id_mensaje, msg.asunto, msg.cuerpo, msg.fecha_envio, msg.leido,
                         u.nombre AS nombre_remitente,
                         m.titulo AS titulo_mueble
                  FROM mensajes msg
                  JOIN usuarios u ON msg.id_remitente = u.id_usuario
                  LEFT JOIN muebles m ON msg.id_mueble = m.id_mueble
                  WHERE msg.id_destinatario = $id_usuario
                  ORDER BY msg.fecha_envio DESC";

$res_recibidos = mysqli_query($conexion, $sql_recibidos);

// MENSAJES ENVIADOS
$sql_enviados = "SELECT msg.id_mensaje, msg.asunto, msg.cuerpo, msg.fecha_envio, msg.leido,
                        u.nombre AS nombre_destinatario,
                        m.titulo AS titulo_mueble
                 FROM mensajes msg
                 JOIN usuarios u ON msg.id_destinatario = u.id_usuario
                 LEFT JOIN muebles m ON msg.id_mueble = m.id_mueble
                 WHERE msg.id_remitente = $id_usuario
                 ORDER BY msg.fecha_envio DESC";

$res_enviados = mysqli_query($conexion, $sql_enviados);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis mensajes - ECO & WOODS</title>
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
                <span class="saludo">
                    Hola, <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?>
                </span>
                <a href="mi_perfil.php">Mi perfil</a>
                <a href="mensajes.php">Mensajes</a>
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

        <h1>Mis mensajes</h1>

        <section class="bloque-mensajes-recibidos">
            <h2>Recibidos</h2>

            <?php if ($res_recibidos && mysqli_num_rows($res_recibidos) > 0): ?>
                <div class="listado-tarjetas">
                    <?php while ($msg = mysqli_fetch_assoc($res_recibidos)): ?>
                        <article class="tarjeta">
                            <p>
                                <strong>De:</strong>
                                <?php echo htmlspecialchars($msg['nombre_remitente']); ?>
                            </p>
                            <p>
                                <strong>Asunto:</strong>
                                <?php echo htmlspecialchars($msg['asunto']); ?>
                                <?php if (!$msg['leido']): ?>
                                    <span style="color:#c0392b; font-size:13px;">(no leído)</span>
                                <?php endif; ?>
                            </p>
                            <?php if (!empty($msg['titulo_mueble'])): ?>
                                <p>
                                    <strong>Mueble:</strong>
                                    <?php echo htmlspecialchars($msg['titulo_mueble']); ?>
                                </p>
                            <?php endif; ?>
                            <p><?php echo nl2br(htmlspecialchars($msg['cuerpo'])); ?></p>
                            <p>
                                <small>Enviado: <?php echo htmlspecialchars($msg['fecha_envio']); ?></small>
                            </p>
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
                            <p>
                                <strong>Para:</strong>
                                <?php echo htmlspecialchars($msg['nombre_destinatario']); ?>
                            </p>
                            <p>
                                <strong>Asunto:</strong>
                                <?php echo htmlspecialchars($msg['asunto']); ?>
                            </p>
                            <?php if (!empty($msg['titulo_mueble'])): ?>
                                <p>
                                    <strong>Mueble:</strong>
                                    <?php echo htmlspecialchars($msg['titulo_mueble']); ?>
                                </p>
                            <?php endif; ?>
                            <p><?php echo nl2br(htmlspecialchars($msg['cuerpo'])); ?></p>
                            <p>
                                <small>Enviado: <?php echo htmlspecialchars($msg['fecha_envio']); ?></small>
                            </p>
                        </article>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p>No has enviado mensajes todavía.</p>
            <?php endif; ?>
        </section>

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
