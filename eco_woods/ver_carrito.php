<?php
/*
DOCUMENTACION_EXPLICATIVA_TFG
Que hace: Presenta el carrito con importes y lineas actuales.
Por que se hizo asi: Carga datos ya validados para evitar incoherencias en pantalla.
Para que sirve: Permite revisar pedido antes del pago final.
*/
/*
DOCUMENTACION_PASO4
Vista de carrito activo del usuario.
- Lista items de muebles/recambios, cantidades y subtotales.
- Permite sumar, restar y eliminar sin recargar pagina.
- Sincroniza UI con respuestas JSON del backend protegido.
*/
// Bootstrap inicia sesiÃ³n y helpers globales; layout evita duplicar header/footer.
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
require_once "conexion.php";

function ew_stmt_result(mysqli $conexion, string $sql, string $types = '', array $params = [])
{
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return false;
    }
    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return false;
    }
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

// Estado base para render estable cuando el usuario no tiene carrito.
$carrito_vacio = true;
$res_items = null;
$id_carrito = 0;

if (isset($_SESSION['usuario_id'])) {
    // El carrito se vincula al usuario autenticado de la sesiÃ³n actual.
    $id_usuario = (int) $_SESSION['usuario_id'];

    $res_carrito = ew_stmt_result(
        $conexion,
        "SELECT id_carrito FROM carritos
         WHERE id_usuario = ? AND estado = 'activo'
         LIMIT 1",
        'i',
        [$id_usuario]
    );

    if ($res_carrito && mysqli_num_rows($res_carrito) > 0) {
        // Si existe carrito activo, obtenemos sus lÃ­neas de detalle.
        $carrito_vacio = false;
        $fila_carrito = mysqli_fetch_assoc($res_carrito);
        $id_carrito = (int)$fila_carrito['id_carrito'];

        $res_items = ew_stmt_result(
            $conexion,
            "SELECT
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
             WHERE ci.id_carrito = ?
             ORDER BY ci.id_item DESC",
            'i',
            [$id_carrito]
        );
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
    <div class="contenedor cart-shell">

        <h1>Carrito de productos</h1>

        <div class="landing-acciones cart-links">
            <a href="index.php" class="btn-ver">Volver al inicio</a>
            <a href="muebles.php" class="btn-ver">Seguir viendo productos</a>
        </div>

        <div id="toastCarrito" class="toast-carrito" style="display:none;"></div>

        <?php
        if (!isset($_SESSION['usuario_id'])) {
            echo "<p>Debes iniciar sesiÃ³n para ver tu carrito.</p>";
        } else if ($carrito_vacio) {
            echo "<p>No tienes ningÃºn carrito activo o estÃ¡ vacÃ­o.</p>";
        } else {

            if (!$res_items || mysqli_num_rows($res_items) == 0) {
                echo "<p>El carrito estÃ¡ vacÃ­o.</p>";
            } else {

                $total = 0;

                echo "<table class='tabla-carrito'>";
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
                    echo "<td>" . number_format($precio, 2, ',', '.') . " â‚¬</td>";

                    echo "<td class='cell-cantidad'>";
                    echo "  <button type='button' class='btn-cant' data-id='$id_item' data-accion='menos'>âˆ’</button>";
                    echo "  <span class='cant-num' id='cant_$id_item' style='display:inline-block;min-width:26px;text-align:center;'>" . $cantidad . "</span>";
                    echo "  <button type='button' class='btn-cant' data-id='$id_item' data-accion='mas'>+</button>";
                    echo "</td>";

                    echo "<td><span id='sub_$id_item'>" . number_format($subtotal, 2, ',', '.') . "</span> â‚¬</td>";

                    echo "<td class='cell-acciones'>";
                    echo "  <button type='button' class='btn-del' data-id='$id_item'>Eliminar</button>";
                    echo "</td>";

                    echo "</tr>";
                }

                echo "<tr>
                        <td colspan='4'><strong>Total</strong></td>
                        <td colspan='2'><strong><span id='totalCarrito'>" . number_format($total, 2, ',', '.') . "</span> â‚¬</strong></td>
                      </tr>";
                echo "</table>";

                echo "<p class='cart-checkout'>";
                echo "  <a href='finalizar_compra.php' class='btn-ver'>Finalizar compra</a>";
                echo "</p>";
            }
        }
        ?>

    </div>
</main>

<?php ew_render_footer(); ?>

<button id="btnTop" onclick="scrollToTop()">â–²</button>
<script src="js/app.js"></script>

/*BORRAR*/

<script>
(function () {
    // Toast de feedback para operaciones asÃ­ncronas del carrito.
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
                // Actualiza cantidad de una lÃ­nea y devuelve nuevo subtotal.
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
                showToast('Error de conexiÃ³n al actualizar cantidad.', false);
            } finally {
                btn.disabled = false;
            }
        });
    });

    document.querySelectorAll('.btn-del').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.getAttribute('data-id');
            if (!id) return;

            if (!confirm('Â¿Seguro que quieres eliminar este producto del carrito?')) return;

            btn.disabled = true;

            try {
                // Elimina una lÃ­nea del carrito por id_item.
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
                showToast('Error de conexiÃ³n al eliminar.', false);
            } finally {
                btn.disabled = false;
            }
        });
    });
})();
</script>

</body>
</html>



