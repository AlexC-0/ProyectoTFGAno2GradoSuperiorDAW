<?php
session_start();

// Solo usuarios logueados pueden entrar aquí
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

require 'conexion.php';

$id_usuario = (int) $_SESSION['usuario_id'];

// 1) Datos del usuario
$sql_usuario = "SELECT nombre, email, telefono, provincia, localidad, fecha_registro
                FROM usuarios
                WHERE id_usuario = $id_usuario
                LIMIT 1";

$res_usuario = mysqli_query($conexion, $sql_usuario);
$usuario = mysqli_fetch_assoc($res_usuario);

// 2) Muebles publicados por este usuario
$sql_muebles = "SELECT id_mueble, titulo, precio, provincia, localidad, estado, fecha_publicacion, categoria, imagen
                FROM muebles
                WHERE id_usuario = $id_usuario
                ORDER BY fecha_publicacion DESC";

$res_muebles = mysqli_query($conexion, $sql_muebles);

// 3) Reseñas hechas por este usuario
$sql_resenas = "SELECT r.*, m.titulo AS titulo_mueble
                FROM resenas r
                JOIN muebles m ON r.id_mueble = m.id_mueble
                WHERE r.id_usuario = $id_usuario
                ORDER BY r.fecha_resena DESC";

$res_resenas = mysqli_query($conexion, $sql_resenas);

// 4) Muebles favoritos de este usuario
$sql_favoritos = "SELECT f.fecha_guardado,
                         m.id_mueble, m.titulo, m.precio, m.provincia,
                         m.localidad, m.estado, m.categoria, m.imagen
                  FROM favoritos f
                  JOIN muebles m ON f.id_mueble = m.id_mueble
                  WHERE f.id_usuario = $id_usuario
                  ORDER BY f.fecha_guardado DESC";

$res_favoritos = mysqli_query($conexion, $sql_favoritos);

// 5) Mensajes (recibidos y enviados)
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

// Contadores de mensajes (por si los quieres usar)
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

					<span class="saludo">
						Hola, <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?>
					</span>
					<a href="mi_perfil.php">Mi perfil</a>
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

        <h1>Mi perfil</h1>

        <?php if ($usuario): ?>
            <section class="bloque-perfil">
                <h2>Datos de la cuenta</h2>
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
            </section>
        <?php else: ?>
            <p>No se han encontrado tus datos de usuario.</p>
        <?php endif; ?>

        <hr>

        <!-- MIS MUEBLES PUBLICADOS -->
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
                                <a class="btn-eliminar"
                                   href="eliminar_mueble.php?id_mueble=<?php echo (int)$m['id_mueble']; ?>"
                                   onclick="return confirm('¿Seguro que quieres eliminar este mueble y sus reseñas/favoritos?');">
                                    Eliminar
                                </a>
                            </div>

                        </article>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p>Todavía no has publicado ningún mueble.</p>
            <?php endif; ?>
        </section>

        <hr>

        <!-- MIS FAVORITOS -->
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
                                <a class="btn-fav es-favorito"
                                   href="toggle_favorito.php?id_mueble=<?php echo (int)$fav['id_mueble']; ?>">
                                    ★ Quitar de favoritos
                                </a>
                            </div>

                        </article>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p>Todavía no tienes muebles marcados como favoritos.</p>
            <?php endif; ?>
        </section>

        <hr>

        <!-- MIS RESEÑAS -->
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
                                <a class="btn-eliminar"
                                   href="eliminar_resena.php?id_resena=<?php echo (int)$r['id_resena']; ?>"
                                   onclick="return confirm('¿Seguro que quieres eliminar esta reseña?');">
                                    Eliminar reseña
                                </a>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p>Todavía no has escrito reseñas.</p>
            <?php endif; ?>
        </section>

        <hr>

        <!-- MIS MENSAJES -->
        <section class="bloque-mis-mensajes">
            <h2>Mis mensajes</h2>

            <!-- MENSAJES RECIBIDOS -->
            <h3>Recibidos (<?php echo $num_recibidos; ?>)</h3>
            <?php if ($res_recibidos && mysqli_num_rows($res_recibidos) > 0): ?>
                <ul class="lista-mensajes">
                    <?php while ($msg = mysqli_fetch_assoc($res_recibidos)): ?>
                        <li class="mensaje-item">
                            <strong>De:</strong> <?php echo htmlspecialchars($msg['nombre_remitente']); ?>
                            — <small><?php echo htmlspecialchars($msg['fecha_envio']); ?></small>

                            <br>

                            <?php if (!empty($msg['titulo_mueble'])): ?>
                                <span class="mensaje-mueble">
                                    Sobre: "<?php echo htmlspecialchars($msg['titulo_mueble']); ?>"
                                </span>
                                <br>
                            <?php endif; ?>

                            <a class="btn-ver" href="ver_mensaje.php?id=<?php echo (int)$msg['id_mensaje']; ?>">
                                Ver mensaje
                            </a>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No tienes mensajes recibidos.</p>
            <?php endif; ?>

            <hr>

            <!-- MENSAJES ENVIADOS -->
            <h3>Enviados (<?php echo $num_enviados; ?>)</h3>
            <?php if ($res_enviados && mysqli_num_rows($res_enviados) > 0): ?>
                <ul class="lista-mensajes">
                    <?php while ($msg = mysqli_fetch_assoc($res_enviados)): ?>
                        <li class="mensaje-item">
                            <strong>Para:</strong> <?php echo htmlspecialchars($msg['nombre_destinatario']); ?>
                            — <small><?php echo htmlspecialchars($msg['fecha_envio']); ?></small>

                            <br>

                            <?php if (!empty($msg['titulo_mueble'])): ?>
                                <span class="mensaje-mueble">
                                    Sobre: "<?php echo htmlspecialchars($msg['titulo_mueble']); ?>"
                                </span>
                                <br>
                            <?php endif; ?>

                            <a class="btn-ver" href="ver_mensaje.php?id=<?php echo (int)$msg['id_mensaje']; ?>">
                                Ver mensaje
                            </a>
                        </li>
                    <?php endwhile; ?>
                </ul>
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
