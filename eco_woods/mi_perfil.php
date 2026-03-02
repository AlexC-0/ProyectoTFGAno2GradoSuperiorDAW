<?php
/*
DOCUMENTACION_PASO4
Area personal del usuario autenticado.
- Reune datos de cuenta, publicaciones, favoritos y resenas.
- Incluye bandeja de mensajes recibidos y enviados.
- Agrupa acciones de gestion propias con controles de seguridad.
*/
// Arranque base: sesión + utilidades, layout común y control de acceso.
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/auth.php';

ew_require_login('login.php');

require 'conexion.php';

// Usuario autenticado del que se mostrará todo el panel personal.
$id_usuario = (int) $_SESSION['usuario_id'];

// Bloque de datos personales.
$sql_usuario = "SELECT nombre, email, telefono, provincia, localidad, fecha_registro
                FROM usuarios
                WHERE id_usuario = $id_usuario
                LIMIT 1";

$res_usuario = mysqli_query($conexion, $sql_usuario);
$usuario = mysqli_fetch_assoc($res_usuario);

// Publicaciones del usuario para gestión desde su perfil.
$sql_muebles = "SELECT id_mueble, titulo, precio, provincia, localidad, estado, fecha_publicacion, categoria, imagen
                FROM muebles
                WHERE id_usuario = $id_usuario
                ORDER BY fecha_publicacion DESC";

$res_muebles = mysqli_query($conexion, $sql_muebles);

// Reseñas escritas por el usuario.
$sql_resenas = "SELECT r.*, m.titulo AS titulo_mueble
                FROM resenas r
                JOIN muebles m ON r.id_mueble = m.id_mueble
                WHERE r.id_usuario = $id_usuario
                ORDER BY r.fecha_resena DESC";

$res_resenas = mysqli_query($conexion, $sql_resenas);

// Favoritos guardados por el usuario.
$sql_favoritos = "SELECT f.fecha_guardado,
                         m.id_mueble, m.titulo, m.precio, m.provincia,
                         m.localidad, m.estado, m.categoria, m.imagen
                  FROM favoritos f
                  JOIN muebles m ON f.id_mueble = m.id_mueble
                  WHERE f.id_usuario = $id_usuario
                  ORDER BY f.fecha_guardado DESC";

$res_favoritos = mysqli_query($conexion, $sql_favoritos);

// Bandeja de entrada de mensajes del usuario.
$sql_recibidos = "SELECT msg.id_mensaje, msg.asunto, msg.cuerpo, msg.fecha_envio, msg.leido,
                         u.nombre AS nombre_remitente,
                         m.titulo AS titulo_mueble,
                         m.id_mueble AS id_mueble
                  FROM mensajes msg
                  JOIN usuarios u ON msg.id_remitente = u.id_usuario
                  LEFT JOIN muebles m ON msg.id_mueble = m.id_mueble
                  WHERE msg.id_destinatario = $id_usuario
                  ORDER BY msg.fecha_envio DESC";

$res_recibidos = mysqli_query($conexion, $sql_recibidos);

// Bandeja de salida para trazabilidad de conversaciones.
$sql_enviados = "SELECT msg.id_mensaje, msg.asunto, msg.cuerpo, msg.fecha_envio, msg.leido,
                        u.nombre AS nombre_destinatario,
                        m.titulo AS titulo_mueble,
                        m.id_mueble AS id_mueble
                 FROM mensajes msg
                 JOIN usuarios u ON msg.id_destinatario = u.id_usuario
                 LEFT JOIN muebles m ON msg.id_mueble = m.id_mueble
                 WHERE msg.id_remitente = $id_usuario
                 ORDER BY msg.fecha_envio DESC";

$res_enviados = mysqli_query($conexion, $sql_enviados);

$num_recibidos = $res_recibidos ? mysqli_num_rows($res_recibidos) : 0;
$num_enviados  = $res_enviados  ? mysqli_num_rows($res_enviados)  : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi perfil - ECO & WOODS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php ew_render_header(['active' => 'perfil']); ?>

<main>
    <div class="contenedor">

        <h1>Mi perfil</h1>

        <?php if ($usuario): ?>
            <section class="perfil-seccion">
                <h2 class="perfil-titulo">Datos de la cuenta</h2>

                <div class="perfil-card perfil-card-estrecha perfil-card-centrada">
                    <p><strong>Nombre:</strong> <?php echo htmlspecialchars($usuario['nombre']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($usuario['email']); ?></p>
                    <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?></p>
                    <p><strong>Ubicación:</strong>
                        <?php
                            echo htmlspecialchars($usuario['provincia'] ?? '');
                            if (!empty($usuario['provincia']) && !empty($usuario['localidad'])) {
                                echo " - ";
                            }
                            echo htmlspecialchars($usuario['localidad'] ?? '');
                        ?>
                    </p>
                    <p><strong>Fecha de registro:</strong> <?php echo htmlspecialchars($usuario['fecha_registro']); ?></p>
                </div>
            </section>
        <?php else: ?>
            <p>No se han encontrado tus datos de usuario.</p>
        <?php endif; ?>

        <hr>

        <section class="bloque-mis-muebles">
            <h2>Mis muebles publicados</h2>

            <?php if ($res_muebles && mysqli_num_rows($res_muebles) > 0): ?>
                <div class="listado-tarjetas">
                    <?php while ($m = mysqli_fetch_assoc($res_muebles)): ?>
                        <article class="tarjeta">

                            <?php if (!empty($m['imagen'])): ?>
                                <img
                                    src="uploads/<?php echo htmlspecialchars($m['imagen']); ?>"
                                    alt="<?php echo htmlspecialchars($m['titulo']); ?>"
                                >
                            <?php endif; ?>

                            <div class="tarjeta-header">
                                <h3 class="tarjeta-titulo">
                                    <?php echo htmlspecialchars($m['titulo']); ?>
                                </h3>
                                <p class="tarjeta-precio">
                                    <?php
                                    $precio = (float)$m['precio'];
                                    echo number_format($precio, 2, ',', '.'); ?> €
                                </p>
                            </div>

                            <p class="tarjeta-descripcion">
                                <strong>Estado:</strong>
                                <?php echo htmlspecialchars($m['estado']); ?><br>
                                <strong>Publicado:</strong>
                                <?php echo htmlspecialchars($m['fecha_publicacion']); ?>
                            </p>

                            <div class="tarjeta-tags">
                                <?php if (!empty($m['categoria'])): ?>
                                    <span class="badge badge-categoria">
                                        <?php echo htmlspecialchars($m['categoria']); ?>
                                    </span>
                                <?php endif; ?>
                                <span class="badge badge-ubicacion">
                                    <?php echo htmlspecialchars($m['provincia']); ?>
                                    -
                                    <?php echo htmlspecialchars($m['localidad']); ?>
                                </span>
                            </div>

                            <div class="tarjeta-footer">
                                <a class="btn-ver"
                                   href="ver_mueble.php?id_mueble=<?php echo (int)$m['id_mueble']; ?>">
                                    Ver mueble
                                </a>
                                <form action="eliminar_mueble.php" method="post" style="margin:0;">
                                    <input type="hidden" name="id_mueble" value="<?php echo (int)$m['id_mueble']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                    <button class="btn-eliminar" type="submit" onclick="return confirm('Confirmar eliminacion de mueble y datos relacionados?');">
                                        Eliminar
                                    </button>
                                </form>
                            </div>

                        </article>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p>Todavía no has publicado ningún mueble.</p>
            <?php endif; ?>
        </section>

        <hr>

        <section class="bloque-mis-favoritos">
            <h2>Mis muebles favoritos</h2>

            <?php if ($res_favoritos && mysqli_num_rows($res_favoritos) > 0): ?>
                <div class="listado-tarjetas">
                    <?php while ($fav = mysqli_fetch_assoc($res_favoritos)): ?>
                        <article class="tarjeta">

                            <?php if (!empty($fav['imagen'])): ?>
                                <img
                                    src="uploads/<?php echo htmlspecialchars($fav['imagen']); ?>"
                                    alt="<?php echo htmlspecialchars($fav['titulo']); ?>"
                                >
                            <?php endif; ?>

                            <div class="tarjeta-header">
                                <h3 class="tarjeta-titulo">
                                    <?php echo htmlspecialchars($fav['titulo']); ?>
                                </h3>
                                <p class="tarjeta-precio">
                                    <?php
                                    $precio = (float)$fav['precio'];
                                    echo number_format($precio, 2, ',', '.'); ?> €
                                </p>
                            </div>

                            <p class="tarjeta-descripcion">
                                <strong>Estado:</strong>
                                <?php echo htmlspecialchars($fav['estado']); ?>
                            </p>

                            <div class="tarjeta-tags">
                                <?php if (!empty($fav['categoria'])): ?>
                                    <span class="badge badge-categoria">
                                        <?php echo htmlspecialchars($fav['categoria']); ?>
                                    </span>
                                <?php endif; ?>
                                <span class="badge badge-ubicacion">
                                    <?php echo htmlspecialchars($fav['provincia']); ?>
                                    -
                                    <?php echo htmlspecialchars($fav['localidad']); ?>
                                </span>
                            </div>

                            <div class="tarjeta-footer">
                                <a class="btn-ver"
                                   href="ver_mueble.php?id_mueble=<?php echo (int)$fav['id_mueble']; ?>">
                                    Ver mueble
                                </a>
                                <form action="toggle_favorito.php" method="post" style="margin:0;">
                                    <input type="hidden" name="id_mueble" value="<?php echo (int)$fav['id_mueble']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                    <button type="submit" class="btn-fav es-favorito">Quitar de favoritos</button>
                                </form>
                            </div>

                        </article>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p>Todavía no tienes muebles marcados como favoritos.</p>
            <?php endif; ?>
        </section>

        <hr>

        <section class="bloque-mis-resenas">
            <h2>Mis reseñas</h2>

            <?php if ($res_resenas && mysqli_num_rows($res_resenas) > 0): ?>
                <div class="listado-tarjetas">
                    <?php while ($r = mysqli_fetch_assoc($res_resenas)): ?>
                        <article class="tarjeta">
                            <p><strong>Mueble:</strong> <?php echo htmlspecialchars($r['titulo_mueble']); ?></p>
                            <p><strong>Puntuación:</strong> <?php echo htmlspecialchars($r['puntuacion']); ?>/5</p>
                            <p><strong>Comentario:</strong> <?php echo htmlspecialchars($r['comentario']); ?></p>
                            <p><small>Fecha reseña: <?php echo htmlspecialchars($r['fecha_resena']); ?></small></p>

                            <div class="tarjeta-footer">
                                <form action="eliminar_resena.php" method="post" style="margin:0;">
                                    <input type="hidden" name="id_resena" value="<?php echo (int)$r['id_resena']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                    <button class="btn-eliminar" type="submit" onclick="return confirm('Confirmar eliminacion de resena?');">
                                        Eliminar resena
                                    </button>
                                </form>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p>Todavía no has escrito reseñas.</p>
            <?php endif; ?>
        </section>

        <hr>

        <section class="perfil-seccion">
            <h2 class="perfil-titulo">Mis mensajes</h2>

            <div class="perfil-grid">

                <div class="perfil-card">
                    <h3>Recibidos (<?php echo $num_recibidos; ?>)</h3>

                    <?php if ($res_recibidos && mysqli_num_rows($res_recibidos) > 0): ?>
                        <ul class="perfil-lista">
                            <?php while ($msg = mysqli_fetch_assoc($res_recibidos)): ?>
                                <li>
                                    <strong>De:</strong> <?php echo htmlspecialchars($msg['nombre_remitente']); ?>
                                    — <small><?php echo htmlspecialchars($msg['fecha_envio']); ?></small>

                                    <?php if (!empty($msg['titulo_mueble'])): ?>
                                        <br>
                                        <span>Sobre: "<?php echo htmlspecialchars($msg['titulo_mueble']); ?>"</span>
                                    <?php endif; ?>

                                    <br><br>

                                    <a class="link-accion mini" href="ver_mensaje.php?id=<?php echo (int)$msg['id_mensaje']; ?>">
                                        Ver mensaje
                                    </a>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p>No tienes mensajes recibidos.</p>
                    <?php endif; ?>
                </div>

                <div class="perfil-card">
                    <h3>Enviados (<?php echo $num_enviados; ?>)</h3>

                    <?php if ($res_enviados && mysqli_num_rows($res_enviados) > 0): ?>
                        <ul class="perfil-lista">
                            <?php while ($msg = mysqli_fetch_assoc($res_enviados)): ?>
                                <li>
                                    <strong>Para:</strong> <?php echo htmlspecialchars($msg['nombre_destinatario']); ?>
                                    — <small><?php echo htmlspecialchars($msg['fecha_envio']); ?></small>

                                    <?php if (!empty($msg['titulo_mueble'])): ?>
                                        <br>
                                        <span>Sobre: "<?php echo htmlspecialchars($msg['titulo_mueble']); ?>"</span>
                                    <?php endif; ?>

                                    <br><br>

                                    <a class="link-accion mini" href="ver_mensaje.php?id=<?php echo (int)$msg['id_mensaje']; ?>">
                                        Ver mensaje
                                    </a>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p>No has enviado mensajes todavía.</p>
                    <?php endif; ?>
                </div>

            </div>
        </section>

    </div>
</main>

<?php ew_render_footer(); ?>

<button id="btnTop" onclick="scrollToTop()">▲</button>
<script src="js/app.js"></script>

</body>
</html>







