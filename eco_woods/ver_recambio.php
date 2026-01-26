codigo de ver_recambio.php

<?php
session_start();
require_once "conexion.php";

function columnExists($conexion, $tabla, $columna) {
    $tabla_esc = mysqli_real_escape_string($conexion, $tabla);
    $col_esc = mysqli_real_escape_string($conexion, $columna);
    $sql = "SHOW COLUMNS FROM `$tabla_esc` LIKE '$col_esc'";
    $res = mysqli_query($conexion, $sql);
    return ($res && mysqli_num_rows($res) > 0);
}

if (!isset($_GET['id_recambio'])) {
    die("Recambio no especificado.");
}

$id_recambio = (int)$_GET['id_recambio'];

// Columnas de imágenes disponibles en recambios3d
$col_img1 = columnExists($conexion, 'recambios3d', 'imagen');
$col_img2 = columnExists($conexion, 'recambios3d', 'imagen2');
$col_img3 = columnExists($conexion, 'recambios3d', 'imagen3');
$col_img4 = columnExists($conexion, 'recambios3d', 'imagen4');
$col_img5 = columnExists($conexion, 'recambios3d', 'imagen5');

// 1) Cargar datos del recambio
$sql_rec = "SELECT * FROM recambios3d WHERE id_recambio = $id_recambio LIMIT 1";
$res_rec = mysqli_query($conexion, $sql_rec);

if (!$res_rec || mysqli_num_rows($res_rec) == 0) {
    die("El recambio no existe.");
}

$recambio = mysqli_fetch_assoc($res_rec);

// 3) Listar reseñas
$sql_lista = "SELECT rr.*, u.nombre AS nombre_usuario
              FROM resenas_recambios rr
              JOIN usuarios u ON rr.id_usuario = u.id_usuario
              WHERE rr.id_recambio = $id_recambio
              ORDER BY rr.fecha_resena DESC";

$res_resenas = mysqli_query($conexion, $sql_lista);

// 4) Construir array de imágenes del recambio
$imagenes = [];

if ($col_img1 && !empty($recambio['imagen'])) {
    $imgRaw = trim($recambio['imagen']);
    if (strpos($imgRaw, ';') !== false) {
        $partes = array_filter(array_map('trim', explode(';', $imgRaw)));
        foreach ($partes as $p) $imagenes[] = $p;
    } else {
        $imagenes[] = $imgRaw;
    }
}

if ($col_img2 && !empty($recambio['imagen2'])) $imagenes[] = trim($recambio['imagen2']);
if ($col_img3 && !empty($recambio['imagen3'])) $imagenes[] = trim($recambio['imagen3']);
if ($col_img4 && !empty($recambio['imagen4'])) $imagenes[] = trim($recambio['imagen4']);
if ($col_img5 && !empty($recambio['imagen5'])) $imagenes[] = trim($recambio['imagen5']);

$imagenes = array_values(array_unique(array_filter($imagenes)));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del recambio - ECO & WOODS</title>
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
        <p><a href="recambios.php">Volver al listado de recambios</a></p>

        <article class="tarjeta">

            <?php if (!empty($imagenes)): ?>
                <img
                    src="uploads/<?php echo htmlspecialchars($imagenes[0]); ?>"
                    alt="<?php echo htmlspecialchars($recambio['nombre']); ?>"
                    class="imagen-mueble"
                >

                <?php if (count($imagenes) > 1): ?>
                    <hr>
                    <h2>Más fotos del recambio</h2>
                    <div class="galeria-mueble">
                        <?php for ($i = 1; $i < count($imagenes); $i++): ?>
                            <img
                                src="uploads/<?php echo htmlspecialchars($imagenes[$i]); ?>"
                                alt="<?php echo htmlspecialchars($recambio['nombre']); ?> - foto <?php echo ($i + 1); ?>"
                                class="imagen-mueble"
                            >
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <h1><?php echo htmlspecialchars($recambio['nombre']); ?></h1>

            <p><strong>Descripción:</strong> <?php echo nl2br(htmlspecialchars($recambio['descripcion'])); ?></p>
            <p><strong>Tipo:</strong> <?php echo htmlspecialchars($recambio['tipo']); ?></p>
            <p><strong>Compatible con:</strong> <?php echo htmlspecialchars($recambio['compatible_con']); ?></p>
            <p><strong>Precio:</strong>
                <?php
                $precio = (float)$recambio['precio'];
                echo number_format($precio, 2, ',', '.');
                ?> €
            </p>

        </article>

        <hr>

        <h2>Reseñas</h2>

        <div id="toastResena" class="toast-carrito" style="display:none;"></div>

        <div id="listaResenas">
            <?php if ($res_resenas && mysqli_num_rows($res_resenas) > 0): ?>
                <div class="listado-tarjetas">
                    <?php while ($r = mysqli_fetch_assoc($res_resenas)): ?>
                        <article class="tarjeta">
                            <p>
                                <strong><?php echo htmlspecialchars($r['nombre_usuario']); ?></strong>
                                — Puntuación: <?php echo (int)$r['puntuacion']; ?>/5
                            </p>
                            <p><?php echo nl2br(htmlspecialchars($r['comentario'])); ?></p>
                            <p><small>Fecha: <?php echo htmlspecialchars($r['fecha_resena']); ?></small></p>
                        </article>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p id="sinResenas">Este recambio aún no tiene reseñas.</p>
            <?php endif; ?>
        </div>

        <?php if (isset($_SESSION['usuario_id'])): ?>

            <h3>Escribe una reseña</h3>

            <form id="formResena" action="add_resena_recambio.php?id_recambio=<?php echo $id_recambio; ?>" method="post" class="formulario">
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
                        <textarea name="comentario" rows="4" cols="40" required></textarea>
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
    const toast = document.getElementById('toastResena');

    function showToast(text, ok=true) {
        toast.textContent = text;
        toast.style.display = 'block';
        toast.classList.remove('ok', 'error');
        toast.classList.add(ok ? 'ok' : 'error');

        clearTimeout(window.__toastTimerResena);
        window.__toastTimerResena = setTimeout(() => {
            toast.style.display = 'none';
        }, 2200);
    }

    const form = document.getElementById('formResena');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const url = form.getAttribute('action');
        const formData = new FormData(form);

        const btn = form.querySelector('button[type="submit"]');
        if (btn) btn.disabled = true;

        try {
            const resp = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await resp.json().catch(() => null);

            if (!resp.ok || !data || data.ok !== true) {
                const msg = (data && data.message) ? data.message : 'No se pudo guardar la reseña.';
                showToast(msg, false);
                return;
            }

            showToast(data.message, true);

            const sinResenas = document.getElementById('sinResenas');
            if (sinResenas) sinResenas.remove();

            let cont = document.querySelector('#listaResenas .listado-tarjetas');
            if (!cont) {
                cont = document.createElement('div');
                cont.className = 'listado-tarjetas';
                document.getElementById('listaResenas').appendChild(cont);
            }

            const r = data.resena;

            const art = document.createElement('article');
            art.className = 'tarjeta';

            const fecha = r && r.fecha_resena ? r.fecha_resena : '';

            const nombreUsuario = r && r.nombre_usuario ? r.nombre_usuario : 'Usuario';
            const puntuacion = r && r.puntuacion ? r.puntuacion : '';

            const comentario = r && r.comentario ? r.comentario : '';

            art.innerHTML = `
                <p><strong>${escapeHtml(nombreUsuario)}</strong> — Puntuación: ${escapeHtml(String(puntuacion))}/5</p>
                <p>${escapeHtml(comentario).replace(/\\n/g, '<br>')}</p>
                <p><small>Fecha: ${escapeHtml(fecha)}</small></p>
            `;

            cont.prepend(art);

            form.reset();

        } catch (err) {
            showToast('Error de conexión al enviar la reseña.', false);
        } finally {
            if (btn) btn.disabled = false;
        }
    });

    function escapeHtml(str) {
        return String(str)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }
})();
</script>

</body>
</html>