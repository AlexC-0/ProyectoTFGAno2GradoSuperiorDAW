<?php






require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/auth.php';
require 'conexion.php';

ew_require_login('login.php');

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

function ew_stmt_execute(mysqli $conexion, string $sql, string $types, array $params): bool
{
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

$id_usuario = (int)$_SESSION['usuario_id'];
$id_carrito = 0;
$items = [];
$total = 0.0;
$finalizado = false;
$mensaje = '';

$res_carrito = ew_stmt_result(
    $conexion,
    "SELECT id_carrito FROM carritos
     WHERE id_usuario = ? AND estado = 'activo'
     LIMIT 1",
    'i',
    [$id_usuario]
);

if ($res_carrito && mysqli_num_rows($res_carrito) > 0) {
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

    if ($res_items) {
        while ($fila = mysqli_fetch_assoc($res_items)) {
            if (!empty($fila['id_recambio'])) {
                $tipo = 'Recambio 3D';
                $nombre = (string)$fila['nombre_recambio'];
                $precio = (float)$fila['precio_recambio'];
            } elseif (!empty($fila['id_mueble'])) {
                $tipo = 'Mueble';
                $nombre = (string)$fila['titulo_mueble'];
                $precio = (float)$fila['precio_mueble'];
            } else {
                continue;
            }

            $cantidad = (int)$fila['cantidad'];
            $subtotal = $precio * $cantidad;
            $total += $subtotal;

            $items[] = [
                'tipo' => $tipo,
                'nombre' => $nombre,
                'precio' => $precio,
                'cantidad' => $cantidad,
                'subtotal' => $subtotal,
            ];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $mensaje = 'Sesion expirada. Recarga la pagina e intentalo de nuevo.';
    } elseif ($id_carrito <= 0) {
        $mensaje = 'No tienes carrito activo para finalizar.';
    } elseif (count($items) === 0) {
        $mensaje = 'No puedes finalizar un carrito vacio.';
    } else {
        $ok = ew_stmt_execute(
            $conexion,
            "UPDATE carritos SET estado = 'finalizado' WHERE id_carrito = ? AND id_usuario = ? AND estado = 'activo'",
            'ii',
            [$id_carrito, $id_usuario]
        );

        if ($ok) {
            $finalizado = true;
            $mensaje = 'Compra finalizada correctamente en modo prueba.';
        } else {
            $mensaje = 'No se pudo finalizar la compra. Intentalo de nuevo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar compra - ECO & WOODS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php ew_render_header(['active' => 'carrito']); ?>

<main>
    <div class="contenedor cart-shell checkout-shell">
        <section class="section-head-card">
            <p class="section-head-kicker">Confirmacion del pedido</p>
            <h1>Finalizar compra</h1>
            <p>Revisa el contenido del carrito antes de confirmar el cierre del pedido en modo prueba.</p>
        </section>

        <?php if ($finalizado): ?>
            <section class="section-content-card checkout-summary-card">
                <div class="perfil-subcard-titulo">
                    <h2>Pedido confirmado</h2>
                </div>
                <p><strong><?php echo e($mensaje); ?></strong></p>
                <p>Esta pantalla simula el cierre de compra. En una version con pago real aqui se incorporaria la pasarela, la direccion y la confirmacion final.</p>
                <div class="landing-acciones">
                    <a href="index.php" class="btn-ver">Volver al inicio</a>
                    <a href="muebles.php" class="btn-ver">Seguir viendo productos</a>
                </div>
            </section>
        <?php else: ?>
            <?php if ($mensaje !== ''): ?>
                <section class="section-content-card">
                    <p><strong><?php echo e($mensaje); ?></strong></p>
                </section>
            <?php endif; ?>

            <?php if (count($items) === 0): ?>
                <section class="section-content-card checkout-summary-card">
                    <div class="perfil-subcard-titulo">
                        <h2>Carrito sin productos</h2>
                    </div>
                    <p>No hay productos disponibles para confirmar el pedido.</p>
                    <div class="landing-acciones">
                        <a href="ver_carrito.php" class="btn-ver">Volver al carrito</a>
                        <a href="muebles.php" class="btn-ver">Ver muebles</a>
                    </div>
                </section>
            <?php else: ?>
                <section class="section-content-card checkout-summary-card">
                    <div class="perfil-subcard-titulo">
                        <h2>Resumen del carrito</h2>
                    </div>

                    <table class="tabla-carrito checkout-table">
                        <tr>
                            <th>Tipo</th>
                            <th>Producto</th>
                            <th>Precio</th>
                            <th>Cantidad</th>
                            <th>Subtotal</th>
                        </tr>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo e($item['tipo']); ?></td>
                                <td><?php echo e($item['nombre']); ?></td>
                                <td><?php echo number_format($item['precio'], 2, ',', '.'); ?> &euro;</td>
                                <td><?php echo (int)$item['cantidad']; ?></td>
                                <td><?php echo number_format($item['subtotal'], 2, ',', '.'); ?> &euro;</td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="4"><strong>Total del pedido</strong></td>
                            <td><strong><?php echo number_format($total, 2, ',', '.'); ?> &euro;</strong></td>
                        </tr>
                    </table>
                </section>

                <section class="section-content-card checkout-confirm-card">
                    <div class="perfil-subcard-titulo">
                        <h2>Confirmacion</h2>
                    </div>
                    <p>Al confirmar, el carrito activo quedara cerrado como compra de prueba.</p>

                    <form action="finalizar_compra.php" method="post" class="formulario checkout-form">
                        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                        <p>
                            <button type="submit">Confirmar finalizacion</button>
                        </p>
                    </form>

                    <div class="landing-acciones">
                        <a href="ver_carrito.php" class="btn-ver">Volver al carrito</a>
                    </div>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<?php ew_render_footer(); ?>

<button id="btnTop" onclick="scrollToTop()">↑</button>
<script src="js/app.js?v=<?php echo filemtime(__DIR__ . '/js/app.js'); ?>"></script>

</body>
</html>