<?php
/*
DOCUMENTACION_PASO4
Catalogo de recambios 3D con favoritos y carrito.
- Renderiza tarjetas y selecciona imagen representativa.
- Integra acciones asincronas de carrito y favoritos.
- Alinea frontend con seguridad y respuestas del backend.
*/
// Bootstrap/layout para sesión y estructura visual consistente.
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
require_once "conexion.php";

// Verifica columnas opcionales de imágenes; permite convivir con distintos estados de esquema.
function columnExists($conexion, $tabla, $columna) {
    $tabla_esc = mysqli_real_escape_string($conexion, $tabla);
    $col_esc = mysqli_real_escape_string($conexion, $columna);
    $sql = "SHOW COLUMNS FROM `$tabla_esc` LIKE '$col_esc'";
    $res = mysqli_query($conexion, $sql);
    return ($res && mysqli_num_rows($res) > 0);
}

$col_img1 = columnExists($conexion, 'recambios3d', 'imagen');
$col_img2 = columnExists($conexion, 'recambios3d', 'imagen2');
$col_img3 = columnExists($conexion, 'recambios3d', 'imagen3');
$col_img4 = columnExists($conexion, 'recambios3d', 'imagen4');
$col_img5 = columnExists($conexion, 'recambios3d', 'imagen5');

// Favoritos de recambios para pintar estado inicial del botón en cada tarjeta.
$favoritos_recambios = [];
if (isset($_SESSION['usuario_id'])) {
    $id_usuario_fav = (int) $_SESSION['usuario_id'];
    $sql_fav = "SELECT id_recambio FROM favoritos_recambios WHERE id_usuario = $id_usuario_fav";
    $res_fav = mysqli_query($conexion, $sql_fav);

    if ($res_fav) {
        while ($f = mysqli_fetch_assoc($res_fav)) {
            $favoritos_recambios[] = (int) $f['id_recambio'];
        }
    }
}

$sql = "SELECT * FROM recambios3d ORDER BY id_recambio DESC";
$resultado = mysqli_query($conexion, $sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recambios 3D GR-Inn</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php ew_render_header(['active' => 'recambios', 'brand_alt' => 'GR-Inn']); ?>

<main>
    <div class="contenedor">

        <section class="catalog-hero">
            <p class="catalog-kicker">Reparacion y mantenimiento</p>
            <h1>Recambios 3D</h1>
            <p class="catalog-lead">
                Piezas orientadas a ampliar la vida util de muebles y componentes.
                Puedes anadir al carrito o guardar favoritos directamente desde el catalogo.
            </p>
        </section>

        <div id="toastGlobal" class="toast-carrito" style="display:none;"></div>

        <?php if ($resultado && mysqli_num_rows($resultado) > 0): ?>

            <div class="listado-tarjetas">
                <?php while ($fila = mysqli_fetch_assoc($resultado)): ?>
                    <?php
                        $idRec = (int)$fila['id_recambio'];

                        $img = '';
                        $candidatas = [];

                        if ($col_img1 && !empty($fila['imagen'])) $candidatas[] = trim($fila['imagen']);
                        if ($col_img2 && !empty($fila['imagen2'])) $candidatas[] = trim($fila['imagen2']);
                        if ($col_img3 && !empty($fila['imagen3'])) $candidatas[] = trim($fila['imagen3']);
                        if ($col_img4 && !empty($fila['imagen4'])) $candidatas[] = trim($fila['imagen4']);
                        if ($col_img5 && !empty($fila['imagen5'])) $candidatas[] = trim($fila['imagen5']);

                        foreach ($candidatas as $cand) {
                            if ($cand === '') continue;

                            // Compatibilidad con guardados antiguos separados por ';'.
                            if (strpos($cand, ';') !== false) {
                                $partes = array_filter(array_map('trim', explode(';', $cand)));
                                if (!empty($partes)) {
                                    $img = $partes[0];
                                    break;
                                }
                            } else {
                                $img = $cand;
                                break;
                            }
                        }

                        $descripcion = (string)($fila['descripcion'] ?? '');
                        // Limitamos preview para homogeneidad visual en el grid.
                        if (mb_strlen($descripcion) > 100) {
                            $descripcion = mb_substr($descripcion, 0, 97) . '...';
                        }

                        $precio = (float)($fila['precio'] ?? 0);
                        $esFav = in_array($idRec, $favoritos_recambios, true);
                    ?>

                    <article class="tarjeta">

                        <?php if (!empty($img)): ?>
                            <img
                                src="uploads/<?php echo htmlspecialchars($img); ?>"
                                alt="<?php echo htmlspecialchars($fila['nombre'] ?? 'Recambio'); ?>"
                            >
                        <?php endif; ?>

                        <div class="tarjeta-header">
                            <h3 class="tarjeta-titulo">
                                <?php echo htmlspecialchars($fila['nombre'] ?? ''); ?>
                            </h3>
                            <p class="tarjeta-precio">
                                <?php echo number_format($precio, 2, ',', '.'); ?> €
                            </p>
                        </div>

                        <p class="tarjeta-descripcion">
                            <?php echo htmlspecialchars($descripcion); ?>
                        </p>

                        <div class="tarjeta-tags">
                            <?php if (!empty($fila['tipo'])): ?>
                                <span class="badge badge-categoria">
                                    <?php echo htmlspecialchars($fila['tipo']); ?>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($fila['compatible_con'])): ?>
                                <span class="badge badge-ubicacion">
                                    <?php echo htmlspecialchars($fila['compatible_con']); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="tarjeta-footer">

                            <a class="btn-ver"
                               href="ver_recambio.php?id_recambio=<?php echo $idRec; ?>">
                                Ver detalles y reseñas
                            </a>

                            <?php if (isset($_SESSION['usuario_id'])): ?>
                                <button type="button"
                                        class="btn-fav <?php echo $esFav ? 'es-favorito' : ''; ?> btn-fav-recambio"
                                        data-id="<?php echo $idRec; ?>">
                                    <?php echo $esFav ? '★ Quitar de favoritos' : '☆ Añadir a favoritos'; ?>
                                </button>

                                <button type="button"
                                        class="btn-carrito-icono btn-carrito-recambio"
                                        data-id="<?php echo $idRec; ?>"
                                        aria-label="Añadir al carrito">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M7 4h-2l-1 2v2h2l3.6 7.59-1.35 2.44A2 2 0 0 0 10 23h10v-2H10l1.1-2h7.45a2 2 0 0 0 1.8-1.1l3.58-6.49A1 1 0 0 0 23 9H7.42L7 8H4V6h2l1-2Z" fill="currentColor"/>
                                    </svg>
                                </button>
                            <?php endif; ?>

                        </div>

                    </article>
                <?php endwhile; ?>
            </div>

        <?php else: ?>
            <p class="catalog-empty">De momento no hay recambios 3D disponibles en el catalogo.</p>
        <?php endif; ?>

    </div>
</main>

<?php ew_render_footer(); ?>

<button id="btnTop" onclick="scrollToTop()">▲</button>
<script src="js/app.js"></script>

<script>
(function () {
    // Feedback no intrusivo para acciones de carrito/favoritos.
    const toast = document.getElementById('toastGlobal');
    const csrfToken = <?php echo json_encode(csrf_token()); ?>;

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

    document.querySelectorAll('.btn-carrito-recambio').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.getAttribute('data-id');
            if (!id) return;

            btn.disabled = true;

            try {
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
                    showToast(msg, false);
                } else {
                    showToast(data.message, true);
                }

            } catch (e) {
                showToast('Error de conexión al añadir al carrito.', false);
            } finally {
                btn.disabled = false;
            }
        });
    });

    document.querySelectorAll('.btn-fav-recambio').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.getAttribute('data-id');
            if (!id) return;

            btn.disabled = true;

            try {
                const resp = await fetch('toggle_favorito_recambio.php', {
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
                    const msg = (data && data.message) ? data.message : 'No se pudo actualizar favoritos.';
                    showToast(msg, false);
                } else {
                    showToast(data.message, true);

                    if (data.es_favorito === true) {
                        btn.classList.add('es-favorito');
                        btn.textContent = '★ Quitar de favoritos';
                    } else if (data.es_favorito === false) {
                        btn.classList.remove('es-favorito');
                        btn.textContent = '☆ Añadir a favoritos';
                    }
                }

            } catch (e) {
                showToast('Error de conexion en favoritos.', false);
            } finally {
                btn.disabled = false;
            }
        });
    });
})();
</script>

</body>
</html>




