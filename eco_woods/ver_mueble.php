codigo de ver_mueble.php

<?php
session_start();
require 'conexion.php';

// Comprobar que llega el id del mueble
if (!isset($_GET['id_mueble'])) {
    die("Mueble no especificado.");
}

$id_mueble = (int) $_GET['id_mueble'];

// 1) Cargar datos del mueble (incluye imagen, imagen2, imagen3, imagen4, imagen5)
$sql_mueble = "SELECT m.*, u.nombre AS nombre_vendedor
               FROM muebles m
               JOIN usuarios u ON m.id_usuario = u.id_usuario
               WHERE m.id_mueble = $id_mueble
               LIMIT 1";

$res_mueble = mysqli_query($conexion, $sql_mueble);

if (!$res_mueble || mysqli_num_rows($res_mueble) == 0) {
    die("El mueble no existe.");
}

$mueble = mysqli_fetch_assoc($res_mueble);

// ID del vendedor
$id_vendedor = isset($mueble['id_usuario']) ? (int)$mueble['id_usuario'] : 0;

// 3 BIS) Cargar recambios 3D compatibles con la categoría del mueble
$recambios_compatibles = null;

if (!empty($mueble['categoria'])) {
    $categoria = mysqli_real_escape_string($conexion, $mueble['categoria']);

    $sql_recambios_compat = "SELECT * 
                             FROM recambios3d
                             WHERE compatible_con LIKE '%$categoria%'
                             ORDER BY precio ASC";

    $res_recambios_compat = mysqli_query($conexion, $sql_recambios_compat);

    if ($res_recambios_compat && mysqli_num_rows($res_recambios_compat) > 0) {
        $recambios_compatibles = $res_recambios_compat;
    }
}

// 2) Si llega el formulario de reseña (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!isset($_SESSION['usuario_id'])) {
        header("Location: login.php");
        exit;
    }

    $id_usuario = (int) $_SESSION['usuario_id'];

    $puntuacion = $_POST['puntuacion'] ?? '';
    $comentario = $_POST['comentario'] ?? '';

    $puntuacion = (int) $puntuacion;
    $comentario = trim($comentario);

    if ($puntuacion >= 1 && $puntuacion <= 5 && $comentario != '') {

        $comentario_esc = mysqli_real_escape_string($conexion, $comentario);

        $sql_resena = "INSERT INTO resenas (id_usuario, id_mueble, puntuacion, comentario)
                       VALUES ($id_usuario, $id_mueble, $puntuacion, '$comentario_esc')";

        $ok_resena = mysqli_query($conexion, $sql_resena);

        if ($ok_resena) {
            $mensaje_resena = "Reseña guardada correctamente.";
        } else {
            $mensaje_resena = "Error al guardar la reseña: " . mysqli_error($conexion);
        }

    } else {
        $mensaje_resena = "Debes indicar una puntuación entre 1 y 5 y escribir un comentario.";
    }
}

// 3) Cargar reseñas del mueble
$sql_lista_resenas = "SELECT r.*, u.nombre AS nombre_usuario
                      FROM resenas r
                      JOIN usuarios u ON r.id_usuario = u.id_usuario
                      WHERE r.id_mueble = $id_mueble
                      ORDER BY r.fecha_resena DESC";

$res_resenas = mysqli_query($conexion, $sql_lista_resenas);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del mueble - ECO & WOODS</title>
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

            <!-- Carrito como icono -->
            <a href="ver_carrito.php" class="nav-icon" aria-label="Carrito">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M7 4h-2l-1 2v2h2l3.6 7.59-1.35 2.44A2 2 0 0 0 10 23h10v-2H10l1.1-2h7.45a2 2 0 0 0 1.8-1.1l3.58-6.49A1 1 0 0 0 23 9H7.42L7 8H4V6h2l1-2Z" fill="currentColor"/>
                </svg>
            </a>

            <?php if (isset($_SESSION['usuario_id'])): ?>

                <?php if (!empty($_SESSION['es_admin']) && $_SESSION['es_admin'] == 1): ?>
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
    <div class="contenedor">

        <p><a href="index.php">Volver al inicio</a></p>
        <p><a href="muebles.php">Volver al listado de muebles</a></p>

        <!-- Toast / mensaje flotante -->
        <div id="toastCarrito" class="toast-carrito" style="display:none;"></div>

        <!-- Tarjeta principal del mueble -->
        <article class="tarjeta">

            <?php if (!empty($mueble['imagen'])): ?>
                <img
                    src="uploads/<?php echo htmlspecialchars($mueble['imagen']); ?>"
                    alt="<?php echo htmlspecialchars($mueble['titulo']); ?>"
                    class="imagen-mueble"
                >
            <?php endif; ?>

            <h1><?php echo htmlspecialchars($mueble['titulo']); ?></h1>

            <p><strong>Descripción:</strong> <?php echo nl2br(htmlspecialchars($mueble['descripcion'])); ?></p>
            <p><strong>Precio:</strong>
                <?php
                $precio_mueble = (float)$mueble['precio'];
                echo number_format($precio_mueble, 2, ',', '.');
                ?> €
            </p>
            <p><strong>Ubicación:</strong>
                <?php echo htmlspecialchars($mueble['provincia']); ?>
                -
                <?php echo htmlspecialchars($mueble['localidad']); ?>
            </p>
            <p><strong>Estado:</strong> <?php echo htmlspecialchars($mueble['estado']); ?></p>
            <?php if (!empty($mueble['categoria'])): ?>
                <p><strong>Categoría:</strong> <?php echo htmlspecialchars($mueble['categoria']); ?></p>
            <?php endif; ?>
            <p><strong>Vendedor:</strong> <?php echo htmlspecialchars($mueble['nombre_vendedor']); ?></p>
            <p><strong>Fecha de publicación:</strong> <?php echo htmlspecialchars($mueble['fecha_publicacion']); ?></p>

            <?php if (isset($_SESSION['usuario_id'])): ?>
                <div class="tarjeta-footer">
                    <!-- Botón icono carrito para el MUEBLE -->
                    <button type="button"
                            class="btn-carrito-icono btn-carrito-mueble"
                            data-id="<?php echo (int)$id_mueble; ?>"
                            aria-label="Añadir mueble al carrito">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M7 4h-2l-1 2v2h2l3.6 7.59-1.35 2.44A2 2 0 0 0 10 23h10v-2H10l1.1-2h7.45a2 2 0 0 0 1.8-1.1l3.58-6.49A1 1 0 0 0 23 9H7.42L7 8H4V6h2l1-2Z" fill="currentColor"/>
                        </svg>
                    </button>
                </div>
            <?php endif; ?>

            <?php
            if (isset($_SESSION['usuario_id'])) {
                $id_usuario_sesion = (int)$_SESSION['usuario_id'];

                if ($id_usuario_sesion !== $id_vendedor) {
                    ?>
                    <p>
                        <a class="btn-ver"
                           href="enviar_mensaje.php?id_mueble=<?php echo (int)$id_mueble; ?>">
                            Contactar con el vendedor
                        </a>
                    </p>
                    <?php
                }
            } else {
                ?>
                <p>
                    <strong>Inicia sesión para contactar con el vendedor.</strong><br>
                    <a href="login.php">Ir a login</a>
                </p>
                <?php
            }
            ?>

            <?php
            $imagenes_extra = [];

            if (!empty($mueble['imagen2'])) { $imagenes_extra[] = $mueble['imagen2']; }
            if (!empty($mueble['imagen3'])) { $imagenes_extra[] = $mueble['imagen3']; }
            if (!empty($mueble['imagen4'])) { $imagenes_extra[] = $mueble['imagen4']; }
            if (!empty($mueble['imagen5'])) { $imagenes_extra[] = $mueble['imagen5']; }

            if (!empty($imagenes_extra)): ?>
                <hr>
                <h2>Más fotos del mueble</h2>
                <div class="galeria-mueble">
                    <?php foreach ($imagenes_extra as $idx => $imgNombre): ?>
                        <img
                            src="uploads/<?php echo htmlspecialchars($imgNombre); ?>"
                            alt="<?php echo htmlspecialchars($mueble['titulo']); ?> - foto <?php echo ($idx + 2); ?>"
                            class="imagen-mueble"
                        >
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </article>

        <hr>

        <h2>Reseñas</h2>

        <?php
        if (!empty($mensaje_resena)) {
            echo '<p><strong>' . htmlspecialchars($mensaje_resena) . '</strong></p>';
        }
        ?>

        <?php if ($res_resenas && mysqli_num_rows($res_resenas) > 0): ?>
            <div class="listado-tarjetas">
                <?php while ($res = mysqli_fetch_assoc($res_resenas)): ?>
                    <article class="tarjeta">
                        <p>
                            <strong><?php echo htmlspecialchars($res['nombre_usuario']); ?></strong>
                            — Puntuación:
                            <?php echo (int)$res['puntuacion']; ?>/5
                        </p>
                        <p><?php echo nl2br(htmlspecialchars($res['comentario'])); ?></p>
                        <p>
                            <small>Fecha: <?php echo htmlspecialchars($res['fecha_resena']); ?></small>
                        </p>
                    </article>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>Este mueble aún no tiene reseñas.</p>
        <?php endif; ?>

        <?php if (isset($_SESSION['usuario_id'])): ?>

            <h3>Escribe una reseña</h3>

            <form action="ver_mueble.php?id_mueble=<?php echo $id_mueble; ?>" method="post" class="formulario">
                <p>
                    <label>Puntuación (1 a 5):<br>
                        <select name="puntuacion">
                            <option value="1">1 - Muy mal</option>
                            <option value="2">2 - Mejorable</option>
                            <option value="3" selected>3 - Correcto</option>
                            <option value="4">4 - Muy bien</option>
                            <option value="5">5 - Excelente</option>
                        </select>
                    </label>
                </p>

                <p>
                    <label>Comentario:<br>
                        <textarea name="comentario" rows="4" cols="40"></textarea>
                    </label>
                </p>

                <p>
                    <button type="submit">Enviar reseña</button>
                </p>
            </form>

        <?php else: ?>

            <p><strong>Debes iniciar sesión para escribir una reseña.</strong></p>
            <p><a href="login.php">Iniciar sesión</a></p>

        <?php endif; ?>

        <hr>

        <h2>Recambios 3D compatibles con este mueble</h2>

        <?php if ($recambios_compatibles): ?>
            <div class="listado-tarjetas">
                <?php while ($rec = mysqli_fetch_assoc($recambios_compatibles)): ?>
                    <article class="tarjeta">

                        <div class="tarjeta-header">
                            <h3 class="tarjeta-titulo">
                                <?php echo htmlspecialchars($rec['nombre']); ?>
                            </h3>
                            <p class="tarjeta-precio">
                                <?php
                                $precio_rec = (float)$rec['precio'];
                                echo number_format($precio_rec, 2, ',', '.');
                                ?> €
                            </p>
                        </div>

                        <p class="tarjeta-descripcion">
                            <?php echo htmlspecialchars($rec['descripcion']); ?>
                        </p>

                        <div class="tarjeta-tags">
                            <?php if (!empty($rec['tipo'])): ?>
                                <span class="badge badge-categoria">
                                    <?php echo htmlspecialchars($rec['tipo']); ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($rec['compatible_con'])): ?>
                                <span class="badge badge-ubicacion">
                                    Compatibles: <?php echo htmlspecialchars($rec['compatible_con']); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="tarjeta-footer">
                            <button type="button"
                                    class="btn-carrito-icono btn-carrito-recambio"
                                    data-id="<?php echo (int)$rec['id_recambio']; ?>"
                                    aria-label="Añadir recambio al carrito">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M7 4h-2l-1 2v2h2l3.6 7.59-1.35 2.44A2 2 0 0 0 10 23h10v-2H10l1.1-2h7.45a2 2 0 0 0 1.8-1.1l3.58-6.49A1 1 0 0 0 23 9H7.42L7 8H4V6h2l1-2Z" fill="currentColor"/>
                                </svg>
                            </button>
                        </div>

                    </article>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>De momento no hay recambios 3D específicos para este tipo de mueble.</p>
        <?php endif; ?>

    </div>
</main>

<footer>
    <div class="contenedor">
        ECO & WOODS - Proyecto Trabajo Fin de Grado
    </div>
</footer>

<button id="btnTop" onclick="scrollToTop()">▲</button>
<script src="js/app.js"></script>

<script>
(function () {
    const toast = document.getElementById('toastCarrito');

    function showToast(text, ok=true) {
        toast.textContent = text;
        toast.style.display = 'block';
        toast.classList.remove('ok', 'error');
        toast.classList.add(ok ? 'ok' : 'error');

        clearTimeout(window.__toastTimer);
        window.__toastTimer = setTimeout(() => {
            toast.style.display = 'none';
        }, 2200);
    }

    async function addToCarrito(url) {
        try {
            const resp = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await resp.json().catch(() => null);

            if (!resp.ok || !data || data.ok !== true) {
                const msg = (data && data.message) ? data.message : 'No se pudo añadir al carrito.';
                showToast(msg, false);
            } else {
                showToast(data.message, true);
            }

        } catch (e) {
            showToast('Error de conexión al añadir al carrito.', false);
        }
    }

    const botonesMueble = document.querySelectorAll('.btn-carrito-mueble');
    botonesMueble.forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.getAttribute('data-id');
            if (!id) return;
            btn.disabled = true;
            await addToCarrito('add_carrito.php?id_mueble=' + encodeURIComponent(id));
            btn.disabled = false;
        });
    });

    const botonesRecambio = document.querySelectorAll('.btn-carrito-recambio');
    botonesRecambio.forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.getAttribute('data-id');
            if (!id) return;
            btn.disabled = true;
            await addToCarrito('add_carrito.php?id_recambio=' + encodeURIComponent(id));
            btn.disabled = false;
        });
    });
})();
</script>

</body>
</html>