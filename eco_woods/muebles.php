<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once "conexion.php";

$favoritos_usuario = [];

if (isset($_SESSION['usuario_id'])) {
    $id_usuario_fav = (int) $_SESSION['usuario_id'];
    $sql_fav = "SELECT id_mueble 
                FROM favoritos 
                WHERE id_usuario = $id_usuario_fav";
    $res_fav = mysqli_query($conexion, $sql_fav);

    if ($res_fav) {
        while ($f = mysqli_fetch_assoc($res_fav)) {
            $favoritos_usuario[] = (int) $f['id_mueble'];
        }
    }
}

$q                = trim($_GET['q'] ?? '');
$categoria_filtro = trim($_GET['categoria'] ?? '');
$precio_min       = trim($_GET['precio_min'] ?? '');
$precio_max       = trim($_GET['precio_max'] ?? '');
$ubicacion_filtro = trim($_GET['ubicacion'] ?? '');

$sql = "SELECT * FROM muebles WHERE 1";

if ($q !== '') {
    $q_esc = mysqli_real_escape_string($conexion, $q);
    $sql .= " AND (titulo LIKE '%$q_esc%' OR descripcion LIKE '%$q_esc%')";
}

if ($categoria_filtro !== '') {
    $cat_esc = mysqli_real_escape_string($conexion, $categoria_filtro);
    $sql .= " AND categoria = '$cat_esc'";
}

if ($precio_min !== '' && is_numeric($precio_min)) {
    $precio_min_num = (float)$precio_min;
    $sql .= " AND precio >= $precio_min_num";
}

if ($precio_max !== '' && is_numeric($precio_max)) {
    $precio_max_num = (float)$precio_max;
    $sql .= " AND precio <= $precio_max_num";
}

if ($ubicacion_filtro !== '') {
    $ubi_esc = mysqli_real_escape_string($conexion, $ubicacion_filtro);
    $sql .= " AND (provincia LIKE '%$ubi_esc%' OR localidad LIKE '%$ubi_esc%')";
}

$sql .= " ORDER BY fecha_publicacion DESC";
$resultado = mysqli_query($conexion, $sql);

$categorias_posibles = ["", "Mesa", "Armario", "Silla", "Cama", "Estantería", "Sofá", "Otro"];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>GR-Inn - Muebles</title>
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

        <h1>Listado de muebles</h1>

        <!-- Toast general (lo usamos para carrito + favoritos) -->
        <div id="toastGlobal" class="toast-carrito" style="display:none;"></div>

        <form action="muebles.php" method="get" class="formulario formulario-filtros-grid">

            <h3>Buscar y filtrar muebles</h3>

            <div class="filtros-grid">

                <div class="filtro-columna">
                    <p>
                        <label for="q">Palabra clave:</label><br>
                        <input type="text" name="q" id="q"
                               placeholder="Ej: mesa, armario, sofá..."
                               value="<?php echo htmlspecialchars($q); ?>">
                    </p>

                    <p>
                        <label for="categoria">Categoría:</label><br>
                        <select name="categoria" id="categoria">
                            <?php
                            foreach ($categorias_posibles as $cat) {
                                $texto = ($cat === '') ? 'Todas' : $cat;
                                $selected = ($cat !== '' && $cat === $categoria_filtro) ? 'selected' : '';
                                echo "<option value=\"" . htmlspecialchars($cat) . "\" $selected>$texto</option>";
                            }
                            ?>
                        </select>
                    </p>
                </div>

                <div class="filtro-columna">
                    <p>
                        <label for="ubicacion">Ubicación (provincia o localidad):</label><br>
                        <input type="text" name="ubicacion" id="ubicacion"
                               placeholder="Ej: Vizcaya, Bilbao..."
                               value="<?php echo htmlspecialchars($ubicacion_filtro); ?>">
                    </p>

                    <p class="filtro-precios">
                        <span>
                            <label for="precio_min">Precio mínimo (€):</label><br>
                            <input type="number" step="1" min="1" name="precio_min" id="precio_min"
                                   placeholder="Desde"
                                   value="<?php echo htmlspecialchars($precio_min); ?>">
                        </span>

                        <span>
                            <label for="precio_max">Precio máximo (€):</label><br>
                            <input type="number" step="1" min="1" name="precio_max" id="precio_max"
                                   placeholder="Hasta"
                                   value="<?php echo htmlspecialchars($precio_max); ?>">
                        </span>
                    </p>
                </div>

            </div>

            <p class="filtros-botones">
                <button type="submit">Aplicar filtros</button>
                <a href="muebles.php" class="btn-link-reset">Limpiar filtros</a>
            </p>

        </form>

        <?php
        $hayFiltros = (
            $q !== '' ||
            $categoria_filtro !== '' ||
            $precio_min !== '' ||
            $precio_max !== '' ||
            $ubicacion_filtro !== ''
        );
        if ($hayFiltros):
        ?>
            <p><em>Mostrando resultados filtrados.</em></p>
        <?php endif; ?>

        <?php if ($resultado && mysqli_num_rows($resultado) > 0): ?>

            <div class="listado-tarjetas">
                <?php while ($fila = mysqli_fetch_assoc($resultado)): ?>
                    <article class="tarjeta">

                        <?php if (!empty($fila['imagen'])): ?>
                            <img
                                src="uploads/<?php echo htmlspecialchars($fila['imagen']); ?>"
                                alt="<?php echo htmlspecialchars($fila['titulo']); ?>"
                            >
                        <?php endif; ?>

                        <div class="tarjeta-header">
                            <h3 class="tarjeta-titulo">
                                <?php echo htmlspecialchars($fila['titulo']); ?>
                            </h3>
                            <p class="tarjeta-precio">
                                <?php
                                $precio = (float)$fila['precio'];
                                echo number_format($precio, 2, ',', '.');
                                ?> €
                            </p>
                        </div>

                        <p class="tarjeta-descripcion">
                            <?php
                            $descripcion = $fila['descripcion'];
                            if (strlen($descripcion) > 100) {
                                $descripcion = substr($descripcion, 0, 97) . '...';
                            }
                            echo htmlspecialchars($descripcion);
                            ?>
                        </p>

                        <div class="tarjeta-tags">
                            <?php if (!empty($fila['categoria'])): ?>
                                <span class="badge badge-categoria">
                                    <?php echo htmlspecialchars($fila['categoria']); ?>
                                </span>
                            <?php endif; ?>
                            <span class="badge badge-ubicacion">
                                <?php echo htmlspecialchars($fila['provincia']); ?>
                                -
                                <?php echo htmlspecialchars($fila['localidad']); ?>
                            </span>
                        </div>

                        <div class="tarjeta-footer">
                            <a class="btn-ver"
                               href="ver_mueble.php?id_mueble=<?php echo (int)$fila['id_mueble']; ?>">
                                Ver detalles y reseñas
                            </a>

                            <?php if (isset($_SESSION['usuario_id'])): ?>
                                <?php
                                    $idMueble = (int)$fila['id_mueble'];
                                    $esFavorito = in_array($idMueble, $favoritos_usuario, true);
                                ?>

                                <!-- Favorito por AJAX -->
                                <a class="btn-fav js-fav <?php echo $esFavorito ? 'es-favorito' : ''; ?>"
                                   href="toggle_favorito.php?id_mueble=<?php echo $idMueble; ?>"
                                   data-id="<?php echo $idMueble; ?>"
                                   data-fav="<?php echo $esFavorito ? '1' : '0'; ?>">
                                    <?php echo $esFavorito ? '★ Quitar de favoritos' : '☆ Añadir a favoritos'; ?>
                                </a>

                                <!-- Botón icono carrito (AJAX) -->
                                <button type="button"
                                        class="btn-carrito-icono btn-carrito-mueble"
                                        data-id="<?php echo $idMueble; ?>"
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

            <p>No se han encontrado muebles con los criterios seleccionados.</p>

        <?php endif; ?>

    </div>
</main>

<footer>
    <div class="contenedor">
        GR-Inn - Proyecto Trabajo Fin de Grado
    </div>
</footer>

<button id="btnTop" onclick="scrollToTop()">▲</button>
<script src="js/app.js"></script>

<script>
(function () {
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

    // Carrito muebles
    const botonesCarrito = document.querySelectorAll('.btn-carrito-mueble');
    botonesCarrito.forEach(btn => {
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
                    body: 'id_mueble=' + encodeURIComponent(id) + '&csrf_token=' + encodeURIComponent(csrfToken)
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

    // Favoritos (sin recargar)
    const botonesFav = document.querySelectorAll('.js-fav');
    botonesFav.forEach(a => {
        a.addEventListener('click', async (ev) => {
            ev.preventDefault();

            const id = a.getAttribute('data-id');
            if (!id) return;

            a.classList.add('cargando');

            try {
                const resp = await fetch('toggle_favorito.php', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: 'id_mueble=' + encodeURIComponent(id) + '&csrf_token=' + encodeURIComponent(csrfToken)
                });

                const data = await resp.json().catch(() => null);

                if (!resp.ok || !data || data.ok !== true) {
                    const msg = (data && data.message) ? data.message : 'No se pudo actualizar favoritos.';
                    showToast(msg, false);
                    return;
                }

                // Actualizar estado visual
                if (data.action === 'added') {
                    a.classList.add('es-favorito');
                    a.textContent = '★ Quitar de favoritos';
                    a.setAttribute('data-fav', '1');
                } else if (data.action === 'removed') {
                    a.classList.remove('es-favorito');
                    a.textContent = '☆ Añadir a favoritos';
                    a.setAttribute('data-fav', '0');
                }

                showToast(data.message, true);

            } catch (e) {
                showToast('Error de conexión en favoritos.', false);
            } finally {
                a.classList.remove('cargando');
            }
        });
    });

})();
</script>

</body>
</html>
