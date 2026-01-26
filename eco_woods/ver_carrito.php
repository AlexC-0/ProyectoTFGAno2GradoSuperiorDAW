<?php
session_start();
require_once "conexion.php";

$carrito_vacio = true;
$res_items = null;
$id_carrito = 0;

if (isset($_SESSION['usuario_id'])) {
    $id_usuario = (int) $_SESSION['usuario_id'];

    $sql_carrito = "SELECT id_carrito FROM carritos
                    WHERE id_usuario = $id_usuario AND estado = 'activo'
                    LIMIT 1";

    $res_carrito = mysqli_query($conexion, $sql_carrito);

    if ($res_carrito && mysqli_num_rows($res_carrito) > 0) {
        $carrito_vacio = false;
        $fila_carrito = mysqli_fetch_assoc($res_carrito);
        $id_carrito = (int)$fila_carrito['id_carrito'];

        $sql_items = "SELECT 
                            ci.id_item,
                            ci.cantidad,
                            ci.id_recambio,
                            ci.id_mueble,
                            r.nombre AS nombre_recambio,
                            r.precio AS precio_recambio,
                            m.titulo AS titulo_mueble,
                            m.precio AS precio_mueble
                      FROM carrito_items ci
                      LEFT JOIN recambios3d r ON ci.id_recambio = r.id_recambio
                      LEFT JOIN muebles m ON ci.id_mueble = m.id_mueble
                      WHERE ci.id_carrito = $id_carrito
                      ORDER BY ci.id_item DESC";

        $res_items = mysqli_query($conexion, $sql_items);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carrito de productos - ECO & WOODS</title>
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

        <h1>Carrito de productos</h1>

        <p><a href="index.php">Volver al inicio</a></p>
        <p><a href="muebles.php">Seguir viendo productos</a></p>

        <div id="toastCarrito" class="toast-carrito" style="display:none;"></div>

        <?php
        if (!isset($_SESSION['usuario_id'])) {
            echo "<p>Debes iniciar sesión para ver tu carrito.</p>";
        } else if ($carrito_vacio) {
            echo "<p>No tienes ningún carrito activo o está vacío.</p>";
        } else {

            if (!$res_items || mysqli_num_rows($res_items) == 0) {
                echo "<p>El carrito está vacío.</p>";
            } else {

                $total = 0;

                echo "<table border='1' cellpadding='5' cellspacing='0' style='width:100%;max-width:900px;'>";
                echo "<tr>
                        <th>Tipo</th>
                        <th>Producto</th>
                        <th>Precio</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                        <th>Acciones</th>
                      </tr>";

                while ($fila = mysqli_fetch_assoc($res_items)) {

                    $tipo = '';
                    $nombre = '';
                    $precio = 0;

                    if (!empty($fila['id_recambio'])) {
                        $tipo = 'Recambio 3D';
                        $nombre = $fila['nombre_recambio'];
                        $precio = (float)$fila['precio_recambio'];
                    } elseif (!empty($fila['id_mueble'])) {
                        $tipo = 'Mueble';
                        $nombre = $fila['titulo_mueble'];
                        $precio = (float)$fila['precio_mueble'];
                    } else {
                        continue;
                    }

                    $id_item = (int)$fila['id_item'];
                    $cantidad = (int)$fila['cantidad'];
                    $subtotal = $precio * $cantidad;
                    $total += $subtotal;

                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($tipo) . "</td>";
                    echo "<td>" . htmlspecialchars($nombre) . "</td>";
                    echo "<td>" . number_format($precio, 2, ',', '.') . " €</td>";

                    echo "<td style='white-space:nowrap;'>";
                    echo "  <button type='button' class='btn-cant' data-id='$id_item' data-accion='menos'>−</button>";
                    echo "  <span class='cant-num' id='cant_$id_item' style='display:inline-block;min-width:26px;text-align:center;'>" . $cantidad . "</span>";
                    echo "  <button type='button' class='btn-cant' data-id='$id_item' data-accion='mas'>+</button>";
                    echo "</td>";

                    echo "<td><span id='sub_$id_item'>" . number_format($subtotal, 2, ',', '.') . "</span> €</td>";

                    echo "<td style='white-space:nowrap;'>";
                    echo "  <button type='button' class='btn-del' data-id='$id_item'>Eliminar</button>";
                    echo "</td>";

                    echo "</tr>";
                }

                echo "<tr>
                        <td colspan='4'><strong>Total</strong></td>
                        <td colspan='2'><strong><span id='totalCarrito'>" . number_format($total, 2, ',', '.') . "</span> €</strong></td>
                      </tr>";
                echo "</table>";

                echo "<p style='margin-top:16px;'>";
                echo "  <a href='finalizar_compra.php' class='btn-ver'>Finalizar compra</a>";
                echo "</p>";
            }
        }
        ?>

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

    function fmt2(num) {
        return (Math.round(num * 100) / 100).toFixed(2).replace('.', ',');
    }

    function recalcTotal() {
        let total = 0;
        document.querySelectorAll('[id^="sub_"]').forEach(el => {
            const raw = (el.textContent || '0').replace('.', '').replace(',', '.');
            const n = parseFloat(raw) || 0;
            total += n;
        });
        const totalEl = document.getElementById('totalCarrito');
        if (totalEl) totalEl.textContent = fmt2(total);
    }

    document.querySelectorAll('.btn-cant').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.getAttribute('data-id');
            const accion = btn.getAttribute('data-accion');
            if (!id || !accion) return;

            btn.disabled = true;

            try {
                const resp = await fetch('carrito_cantidad.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'id_item=' + encodeURIComponent(id) + '&accion=' + encodeURIComponent(accion)
                });

                const data = await resp.json().catch(() => null);

                if (!resp.ok || !data || data.ok !== true) {
                    const msg = (data && data.message) ? data.message : 'No se pudo actualizar la cantidad.';
                    showToast(msg, false);
                    return;
                }

                if (data.eliminado === true) {
                    const rowBtn = btn.closest('tr');
                    if (rowBtn) rowBtn.remove();
                    showToast(data.message || 'Producto eliminado del carrito.', true);
                    recalcTotal();
                    return;
                }

                const cantEl = document.getElementById('cant_' + id);
                const subEl = document.getElementById('sub_' + id);

                if (cantEl && typeof data.cantidad !== 'undefined') cantEl.textContent = data.cantidad;
                if (subEl && typeof data.subtotal !== 'undefined') subEl.textContent = fmt2(parseFloat(data.subtotal) || 0);

                recalcTotal();
                showToast(data.message || 'Cantidad actualizada.', true);

            } catch (e) {
                showToast('Error de conexión al actualizar cantidad.', false);
            } finally {
                btn.disabled = false;
            }
        });
    });

    document.querySelectorAll('.btn-del').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.getAttribute('data-id');
            if (!id) return;

            if (!confirm('¿Seguro que quieres eliminar este producto del carrito?')) return;

            btn.disabled = true;

            try {
                const resp = await fetch('carrito_eliminar.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'id_item=' + encodeURIComponent(id)
                });

                const data = await resp.json().catch(() => null);

                if (!resp.ok || !data || data.ok !== true) {
                    const msg = (data && data.message) ? data.message : 'No se pudo eliminar el producto.';
                    showToast(msg, false);
                    return;
                }

                const row = btn.closest('tr');
                if (row) row.remove();
                recalcTotal();
                showToast(data.message || 'Producto eliminado.', true);

            } catch (e) {
                showToast('Error de conexión al eliminar.', false);
            } finally {
                btn.disabled = false;
            }
        });
    });
})();
</script>

</body>
</html>
