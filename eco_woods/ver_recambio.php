<?php
/*
DOCUMENTACION_PASO4
Detalle completo de un recambio 3D.
- Renderiza informacion del producto y galeria de imagenes.
- Permite resenas y acciones de carrito con feedback visual.
- Incluye formularios protegidos y consumo de endpoints estables.
*/
// Bootstrap + layout para sesión, utilidades compartidas y cabecera/pie unificados.
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
require_once "conexion.php";

// Comprobación defensiva de columnas: permite tolerar esquemas con migraciones parciales.
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

// Identificador de recambio tipado para evitar inyección por querystring.
$id_recambio = (int)$_GET['id_recambio'];

// Verificación dinámica de columnas de imágenes opcionales.
$col_img1 = columnExists($conexion, 'recambios3d', 'imagen');
$col_img2 = columnExists($conexion, 'recambios3d', 'imagen2');
$col_img3 = columnExists($conexion, 'recambios3d', 'imagen3');
$col_img4 = columnExists($conexion, 'recambios3d', 'imagen4');
$col_img5 = columnExists($conexion, 'recambios3d', 'imagen5');

$sql_rec = "SELECT * FROM recambios3d WHERE id_recambio = $id_recambio LIMIT 1";
$res_rec = mysqli_query($conexion, $sql_rec);

if (!$res_rec || mysqli_num_rows($res_rec) == 0) {
    die("El recambio no existe.");
}

$recambio = mysqli_fetch_assoc($res_rec);

$sql_lista = "SELECT rr.*, u.nombre AS nombre_usuario
              FROM resenas_recambios rr
              JOIN usuarios u ON rr.id_usuario = u.id_usuario
              WHERE rr.id_recambio = $id_recambio
              ORDER BY rr.fecha_resena DESC";

$res_resenas = mysqli_query($conexion, $sql_lista);

// Se construye una colección única de imágenes combinando columna principal y extras.
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

<?php ew_render_header(); ?>

<main>
    <div class="contenedor">

        <div class="detail-nav">
            <a href="index.php" class="btn-ver">Volver al inicio</a>
            <a href="recambios.php" class="btn-ver">Volver al listado de recambios</a>
        </div>

        <div id="toastCarrito" class="toast-carrito" style="display:none;"></div>

        <article class="tarjeta detail-shell">

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

            <div class="tarjeta-footer detail-actions">

                <div class="share-panel">
                    <div class="share-title">
                        Compartir:
                    </div>

                    <div class="share-actions">
                        <button type="button"
                                class="btn-share btn-share-mail"
                                aria-label="Compartir por email"
                               >
                            ✉️ Email
                        </button>

                        <button type="button"
                                class="btn-share btn-share-whatsapp"
                                aria-label="Compartir por WhatsApp"
                               >
                            💬 WhatsApp
                        </button>

                        <button type="button"
                                class="btn-share btn-share-instagram"
                                aria-label="Compartir en Instagram"
                               >
                            📷 Instagram
                        </button>
                    </div>
                </div>

                <div class="detail-cart">
                    <button type="button"
                            class="btn-carrito-icono btn-carrito-recambio-grande"
                            data-id="<?php echo (int)$id_recambio; ?>"
                            aria-label="Añadir recambio al carrito"
                           >
                        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="margin-left:-7px;">
                            <path d="M7 4h-2l-1 2v2h2l3.6 7.59-1.35 2.44A2 2 0 0 0 10 23h10v-2H10l1.1-2h7.45a2 2 0 0 0 1.8-1.1l3.58-6.49A1 1 0 0 0 23 9H7.42L7 8H4V6h2l1-2Z" fill="currentColor"/>
                        </svg>
                    </button>
                </div>

            </div>

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
                <!-- Token CSRF exigido por add_resena_recambio.php -->
                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
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

<?php ew_render_footer(); ?>

<button id="btnTop" onclick="scrollToTop()">▲</button>
<script src="js/app.js"></script>

<script>
(function () {
    // Flags y tokens exportados desde PHP para controlar UX en cliente.
    const loggedIn = <?php echo isset($_SESSION['usuario_id']) ? 'true' : 'false'; ?>;
    const csrfToken = <?php echo json_encode(csrf_token()); ?>;

    const toastCarrito = document.getElementById('toastCarrito');
    const toastResena = document.getElementById('toastResena');

    function showToast(el, text, ok=true) {
        if (!el) return;
        el.textContent = text;
        el.style.display = 'block';
        el.classList.remove('ok', 'error');
        el.classList.add(ok ? 'ok' : 'error');

        clearTimeout(el.__t);
        el.__t = setTimeout(() => {
            el.style.display = 'none';
        }, 2200);
    }

    async function addToCarrito(id) {
        try {
            // POST con CSRF y respuesta JSON unificada del backend.
            const resp = await fetch('add_carrito.php', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: 'id_recambio=' + encodeURIComponent(id) + '&csrf_token=' + encodeURIComponent(csrfToken)
            });

            const data = await resp.json().catch(() => null);

            if (!resp.ok || !data || data.ok !== true) {
                const msg = (data && data.message) ? data.message : 'No se pudo añadir al carrito.';
                showToast(toastCarrito, msg, false);
            } else {
                showToast(toastCarrito, data.message, true);
            }

        } catch (e) {
            showToast(toastCarrito, 'Error de conexión al añadir al carrito.', false);
        }
    }

    const btnCarrito = document.querySelector('.btn-carrito-recambio-grande');
    if (btnCarrito) {
        btnCarrito.addEventListener('click', async () => {
            if (!loggedIn) {
                window.location.href = 'login.php';
                return;
            }

            const id = btnCarrito.getAttribute('data-id');
            if (!id) return;

            btnCarrito.disabled = true;
            await addToCarrito(id);
            btnCarrito.disabled = false;
        });
    }

    function getShareData() {
        // Datos reutilizables para compartir en múltiples canales.
        const url = window.location.href;
        const titulo = <?php echo json_encode($recambio['nombre'] ?? 'Recambio'); ?>;
        const texto = `Mira este anuncio en ECO & WOODS: ${titulo}`;
        return { url, titulo, texto };
    }

    function openPopup(url) {
        window.open(url, '_blank', 'noopener,noreferrer');
    }

    async function copyLink(url) {
        try {
            await navigator.clipboard.writeText(url);
            showToast(toastCarrito, 'Enlace copiado. Pégalo donde quieras.', true);
            return true;
        } catch (e) {
            showToast(toastCarrito, 'No se pudo copiar el enlace.', false);
            return false;
        }
    }

    const btnMail = document.querySelector('.btn-share-mail');
    const btnWa = document.querySelector('.btn-share-whatsapp');
    const btnIg = document.querySelector('.btn-share-instagram');

    if (btnMail) {
        btnMail.addEventListener('click', () => {
            const d = getShareData();
            const subject = encodeURIComponent(d.titulo);
            const body = encodeURIComponent(d.texto + "\n\n" + d.url);
            window.location.href = `mailto:?subject=${subject}&body=${body}`;
        });
    }

    if (btnWa) {
        btnWa.addEventListener('click', () => {
            const d = getShareData();
            const text = encodeURIComponent(d.texto + " " + d.url);
            openPopup(`https://wa.me/?text=${text}`);
        });
    }

    if (btnIg) {
        btnIg.addEventListener('click', async () => {
            const d = getShareData();

            if (navigator.share) {
                // En móvil, Web Share API ofrece UX nativa.
                try {
                    await navigator.share({ title: d.titulo, text: d.texto, url: d.url });
                    return;
                } catch (e) {}
            }

            const ok = await copyLink(d.url);
            if (ok) {
                // Fallback: copia enlace y abre Instagram para continuar flujo manual.
                openPopup('https://www.instagram.com/');
            }
        });
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
            // FormData permite enviar textarea/select sin serialización manual.
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
                showToast(toastResena, msg, false);
                return;
            }

            showToast(toastResena, data.message, true);

            const sinResenas = document.getElementById('sinResenas');
            if (sinResenas) sinResenas.remove();

            let cont = document.querySelector('#listaResenas .listado-tarjetas');
            if (!cont) {
                cont = document.createElement('div');
                cont.className = 'listado-tarjetas';
                document.getElementById('listaResenas').appendChild(cont);
            }

            const r = data.resena;

            // Render inmediato en cliente para evitar recarga de página.
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
            showToast(toastResena, 'Error de conexión al enviar la reseña.', false);
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



