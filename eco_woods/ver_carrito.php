<?php
// Bootstrap inicia sesión y helpers globales; layout evita duplicar header/footer.
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
require_once "conexion.php";

// Estado base para render estable cuando el usuario no tiene carrito.
$carrito_vacio = true;
$res_items = null;
$id_carrito = 0;

if (isset($_SESSION['usuario_id'])) {
    // El carrito se vincula al usuario autenticado de la sesión actual.
    $id_usuario = (int) $_SESSION['usuario_id'];

    $sql_carrito = "SELECT id_carrito FROM carritos
                    WHERE id_usuario = $id_usuario AND estado = 'activo'
                    LIMIT 1";

    $res_carrito = mysqli_query($conexion, $sql_carrito);

    if ($res_carrito && mysqli_num_rows($res_carrito) > 0) {
        // Si existe carrito activo, obtenemos sus líneas de detalle.
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

<?php ew_render_header(); ?>

<main>
    <div class="contenedor">

        <h1>Carrito de productos</h1>

        <div class="landing-acciones" style="margin-bottom:16px;">
            <a href="index.php" class="btn-ver">Volver al inicio</a>
            <a href="muebles.php" class="btn-ver">Seguir viendo productos</a>
        </div>

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
                        // Linea de recambio.
                        $tipo = 'Recambio 3D';
                        $nombre = $fila['nombre_recambio'];
                        $precio = (float)$fila['precio_recambio'];
                    } elseif (!empty($fila['id_mueble'])) {
                        // Linea de mueble.
                        $tipo = 'Mueble';
                        $nombre = $fila['titulo_mueble'];
                        $precio = (float)$fila['precio_mueble'];
                    } else {
                        // Defensa ante datos incompletos en DB.
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

<?php ew_render_footer(); ?>

<button id="btnTop" onclick="scrollToTop()">▲</button>
<script src="js/app.js"></script>

<script>
(function () {
    // Toast de feedback para operaciones asíncronas del carrito.
    const toast = document.getElementById('toastCarrito');
    // Token CSRF usado en todos los POST AJAX.
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

    function fmt2(num) {
        return (Math.round(num * 100) / 100).toFixed(2).replace('.', ',');
    }

    function recalcTotal() {
        // Recalcula total leyendo subtotales actuales en el DOM.
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
                // Actualiza cantidad de una línea y devuelve nuevo subtotal.
                const resp = await fetch('carrito_cantidad.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'id_item=' + encodeURIComponent(id) + '&accion=' + encodeURIComponent(accion) + '&csrf_token=' + encodeURIComponent(csrfToken)
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
                // Elimina una línea del carrito por id_item.
                const resp = await fetch('carrito_eliminar.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'id_item=' + encodeURIComponent(id) + '&csrf_token=' + encodeURIComponent(csrfToken)
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

