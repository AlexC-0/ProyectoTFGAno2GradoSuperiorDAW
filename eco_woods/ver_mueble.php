<?php
/*
DOCUMENTACION_EXPLICATIVA_TFG
Que hace: Muestra detalle completo de un mueble y reseñas asociadas.
Por que se hizo asi: Carga datos relacionados con consultas preparadas para detalle fiable y seguro.
Para que sirve: Da informacion suficiente para decidir la compra.
*/
/*
DOCUMENTACION_PASO4
Detalle completo de un mueble.
- Muestra datos del anuncio, galeria y vendedor.
- Permite resenas, compartir y alta en carrito.
- Controla envio de resenas con validacion de sesion y token.
*/
// Bootstrap/layout para sesion y estructura visual compartida.
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
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
               WHERE m.id_mueble = ?
               LIMIT 1";
$stmt_mueble = mysqli_prepare($conexion, $sql_mueble);
if (!$stmt_mueble) {
    die("Error al preparar la consulta del mueble.");
}
mysqli_stmt_bind_param($stmt_mueble, 'i', $id_mueble);
mysqli_stmt_execute($stmt_mueble);
$res_mueble = mysqli_stmt_get_result($stmt_mueble);

if (!$res_mueble || mysqli_num_rows($res_mueble) === 0) {
    mysqli_stmt_close($stmt_mueble);
    die("El mueble no existe.");
}

$mueble = mysqli_fetch_assoc($res_mueble);
mysqli_stmt_close($stmt_mueble);

// ID del vendedor
$id_vendedor = isset($mueble['id_usuario']) ? (int)$mueble['id_usuario'] : 0;

// 3 BIS) Cargar recambios 3D compatibles con la categoria del mueble
$recambios_compatibles = null;
if (!empty($mueble['categoria'])) {
    $categoria_like = '%' . (string)$mueble['categoria'] . '%';
    $sql_recambios_compat = "SELECT * 
                             FROM recambios3d
                             WHERE compatible_con LIKE ?
                             ORDER BY precio ASC";
    $stmt_rec = mysqli_prepare($conexion, $sql_recambios_compat);
    if ($stmt_rec) {
        mysqli_stmt_bind_param($stmt_rec, 's', $categoria_like);
        mysqli_stmt_execute($stmt_rec);
        $res_recambios_compat = mysqli_stmt_get_result($stmt_rec);
        if ($res_recambios_compat && mysqli_num_rows($res_recambios_compat) > 0) {
            $recambios_compatibles = $res_recambios_compat;
        }
        mysqli_stmt_close($stmt_rec);
    }
}

// 2) Si llega el formulario de resena (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: login.php");
        exit;
    }

    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $mensaje_resena = "Sesion expirada. Recarga la pagina e intentalo de nuevo.";
    } else {
        $id_usuario = (int) $_SESSION['usuario_id'];
        $puntuacion = (int)($_POST['puntuacion'] ?? 0);
        $comentario = trim((string)($_POST['comentario'] ?? ''));

        if ($puntuacion >= 1 && $puntuacion <= 5 && $comentario !== '') {
            $sql_resena = "INSERT INTO resenas (id_usuario, id_mueble, puntuacion, comentario)
                           VALUES (?, ?, ?, ?)";
            $stmt_resena = mysqli_prepare($conexion, $sql_resena);

            if (!$stmt_resena) {
                $mensaje_resena = "Error al preparar la resena.";
            } else {
                mysqli_stmt_bind_param($stmt_resena, 'iiis', $id_usuario, $id_mueble, $puntuacion, $comentario);
                $ok_resena = mysqli_stmt_execute($stmt_resena);
                mysqli_stmt_close($stmt_resena);

                if ($ok_resena) {
                    $mensaje_resena = "Resena guardada correctamente.";
                } else {
                    $mensaje_resena = "Error al guardar la resena.";
                }
            }
        } else {
            $mensaje_resena = "Debes indicar una puntuacion entre 1 y 5 y escribir un comentario.";
        }
    }
}

// 3) Cargar resenas del mueble
$sql_lista_resenas = "SELECT r.*, u.nombre AS nombre_usuario
                      FROM resenas r
                      JOIN usuarios u ON r.id_usuario = u.id_usuario
                      WHERE r.id_mueble = ?
                      ORDER BY r.fecha_resena DESC";
$stmt_lista = mysqli_prepare($conexion, $sql_lista_resenas);
if (!$stmt_lista) {
    die("Error al preparar la consulta de resenas.");
}
mysqli_stmt_bind_param($stmt_lista, 'i', $id_mueble);
mysqli_stmt_execute($stmt_lista);
$res_resenas = mysqli_stmt_get_result($stmt_lista);
mysqli_stmt_close($stmt_lista);

$loggedIn = isset($_SESSION['usuario_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del mueble - ECO & WOODS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php ew_render_header(); ?>

<main>
    <div class="contenedor">

        <div class="detail-nav">
            <a href="index.php" class="btn-ver">Volver al inicio</a>
            <a href="muebles.php" class="btn-ver">Volver al listado de muebles</a>
        </div>

        <!-- Toast / mensaje flotante -->
        <div id="toastCarrito" class="toast-carrito" style="display:none;"></div>

        <!-- Tarjeta principal del mueble -->
        <article class="tarjeta detail-shell">

            <?php if (!empty($mueble['imagen'])): ?>
                <img
                    src="uploads/<?php echo htmlspecialchars($mueble['imagen']); ?>"
                    alt="<?php echo htmlspecialchars($mueble['titulo']); ?>"
                    class="imagen-mueble"
                >
            <?php endif; ?>

            <h1><?php echo htmlspecialchars($mueble['titulo']); ?></h1>

            <p><strong>DescripciÃ³n:</strong> <?php echo nl2br(htmlspecialchars($mueble['descripcion'])); ?></p>
            <p><strong>Precio:</strong>
                <?php
                $precio_mueble = (float)$mueble['precio'];
                echo number_format($precio_mueble, 2, ',', '.');
                ?> â‚¬
            </p>
            <p><strong>UbicaciÃ³n:</strong>
                <?php echo htmlspecialchars($mueble['provincia']); ?>
                -
                <?php echo htmlspecialchars($mueble['localidad']); ?>
            </p>
            <p><strong>Estado:</strong> <?php echo htmlspecialchars($mueble['estado']); ?></p>
            <?php if (!empty($mueble['categoria'])): ?>
                <p><strong>CategorÃ­a:</strong> <?php echo htmlspecialchars($mueble['categoria']); ?></p>
            <?php endif; ?>
            <p><strong>Vendedor:</strong> <?php echo htmlspecialchars($mueble['nombre_vendedor']); ?></p>
            <p><strong>Fecha de publicaciÃ³n:</strong> <?php echo htmlspecialchars($mueble['fecha_publicacion']); ?></p>

            <div class="tarjeta-footer detail-actions">

                <!-- IZQUIERDA: COMPARTIR -->
                <div class="share-panel">
                    <div class="share-title">
                        Compartir:
                    </div>

                    <div class="share-actions">
                        <button type="button"
                                class="btn-share btn-share-mail"
                                aria-label="Compartir por email"
                               >
                            âœ‰ï¸ Email
                        </button>

                        <button type="button"
                                class="btn-share btn-share-whatsapp"
                                aria-label="Compartir por WhatsApp"
                               >
                            ðŸ’¬ WhatsApp
                        </button>

                        <button type="button"
                                class="btn-share btn-share-instagram"
                                aria-label="Compartir en Instagram"
                               >
                            ðŸ“· Instagram
                        </button>
                    </div>
                </div>

                <!-- DERECHA: CARRITO -->
                <div class="detail-cart">
                    <button type="button"
                            class="btn-carrito-icono btn-carrito-mueble"
                            data-id="<?php echo (int)$id_mueble; ?>"
                            aria-label="AÃ±adir mueble al carrito"
                           >
                        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="margin-left:-7px;">
                            <path d="M7 4h-2l-1 2v2h2l3.6 7.59-1.35 2.44A2 2 0 0 0 10 23h10v-2H10l1.1-2h7.45a2 2 0 0 0 1.8-1.1l3.58-6.49A1 1 0 0 0 23 9H7.42L7 8H4V6h2l1-2Z" fill="currentColor"/>
                        </svg>
                    </button>
                </div>

            </div>

            <?php
            if (isset($_SESSION['usuario_id'])) {
                $id_usuario_sesion = (int)$_SESSION['usuario_id'];

                if ($id_usuario_sesion !== $id_vendedor) {
                    ?>
                    <p class="detail-contact">
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
                    <strong>Inicia sesiÃ³n para contactar con el vendedor.</strong><br>
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
                <h2>MÃ¡s fotos del mueble</h2>
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

        <h2>ReseÃ±as</h2>

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
                            â€” PuntuaciÃ³n:
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
            <p>Este mueble aÃºn no tiene reseÃ±as.</p>
        <?php endif; ?>

        <?php if (isset($_SESSION['usuario_id'])): ?>

            <h3>Escribe una reseÃ±a</h3>

            <form action="ver_mueble.php?id_mueble=<?php echo $id_mueble; ?>" method="post" class="formulario">
                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                <p>
                    <label>PuntuaciÃ³n (1 a 5):<br>
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
                    <button type="submit">Enviar reseÃ±a</button>
                </p>
            </form>

        <?php else: ?>

            <p><strong>Debes iniciar sesiÃ³n para escribir una reseÃ±a.</strong></p>
            <p><a href="login.php">Iniciar sesiÃ³n</a></p>

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
                                ?> â‚¬
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
                                    aria-label="AÃ±adir recambio al carrito">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M7 4h-2l-1 2v2h2l3.6 7.59-1.35 2.44A2 2 0 0 0 10 23h10v-2H10l1.1-2h7.45a2 2 0 0 0 1.8-1.1l3.58-6.49A1 1 0 0 0 23 9H7.42L7 8H4V6h2l1-2Z" fill="currentColor"/>
                                </svg>
                            </button>
                        </div>

                    </article>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>De momento no hay recambios 3D especÃ­ficos para este tipo de mueble.</p>
        <?php endif; ?>

    </div>
</main>

<?php ew_render_footer(); ?>

<button id="btnTop" onclick="scrollToTop()">â–²</button>
<script src="js/app.js"></script>

<script>
(function () {
    const loggedIn = <?php echo $loggedIn ? 'true' : 'false'; ?>;
    const csrfToken = <?php echo json_encode(csrf_token()); ?>;
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

    async function addToCarrito(tipo, id) {
        try {
            const resp = await fetch('add_carrito.php', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: tipo + '=' + encodeURIComponent(id) + '&csrf_token=' + encodeURIComponent(csrfToken)
            });

            const data = await resp.json().catch(() => null);

            if (!resp.ok || !data || data.ok !== true) {
                const msg = (data && data.message) ? data.message : 'No se pudo aÃ±adir al carrito.';
                showToast(msg, false);
            } else {
                showToast(data.message, true);
            }

        } catch (e) {
            showToast('Error de conexiÃ³n al aÃ±adir al carrito.', false);
        }
    }

    const botonesMueble = document.querySelectorAll('.btn-carrito-mueble');
    botonesMueble.forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!loggedIn) {
                window.location.href = 'login.php';
                return;
            }
            const id = btn.getAttribute('data-id');
            if (!id) return;
            btn.disabled = true;
            await addToCarrito('id_mueble', id);
            btn.disabled = false;
        });
    });

    const botonesRecambio = document.querySelectorAll('.btn-carrito-recambio');
    botonesRecambio.forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!loggedIn) {
                window.location.href = 'login.php';
                return;
            }
            const id = btn.getAttribute('data-id');
            if (!id) return;
            btn.disabled = true;
            await addToCarrito('id_recambio', id);
            btn.disabled = false;
        });
    });

    function getShareData() {
        const url = window.location.href;
        const titulo = <?php echo json_encode($mueble['titulo'] ?? 'Mueble'); ?>;
        const texto = `Mira este anuncio en ECO & WOODS: ${titulo}`;
        return { url, titulo, texto };
    }

    function openPopup(url) {
        window.open(url, '_blank', 'noopener,noreferrer');
    }

    async function copyLink(url) {
        try {
            await navigator.clipboard.writeText(url);
            showToast('Enlace copiado. PÃ©galo donde quieras.', true);
            return true;
        } catch (e) {
            showToast('No se pudo copiar el enlace.', false);
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
                try {
                    await navigator.share({ title: d.titulo, text: d.texto, url: d.url });
                    return;
                } catch (e) {}
            }

            const ok = await copyLink(d.url);
            if (ok) {
                openPopup('https://www.instagram.com/');
            }
        });
    }
})();
</script>

</body>
</html>



