<?php
/*
DOCUMENTACION_EXPLICATIVA_TFG
Que hace: Cierra el proceso de compra y registra pedido.
Por que se hizo asi: Se valida estado de carrito y se persiste con consultas seguras.
Para que sirve: Convierte el carrito en una operacion final controlada.
*/
/*
DOCUMENTACION_PASO4
Confirmacion y cierre de carrito en modo prueba.
- GET solo muestra confirmacion, no cambia estado.
- POST con CSRF finaliza carrito activo no vacio.
- Deja flujo preparado para integrar pago real en futuro.
*/
// Flujo transaccional de cierre de carrito:
// - requiere login
// - solo confirma compra por POST + CSRF
// - GET muestra pantalla de confirmacion sin mutar estado
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/auth.php';

ew_require_login('login.php');
require 'conexion.php';

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
$finalizado = false;
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $mensaje = 'Sesion expirada. Recarga la pagina e intentalo de nuevo.';
    } else {
        $res_carrito = ew_stmt_result(
            $conexion,
            "SELECT id_carrito FROM carritos
             WHERE id_usuario = ? AND estado = 'activo'
             LIMIT 1",
            'i',
            [$id_usuario]
        );

        if ($res_carrito && mysqli_num_rows($res_carrito) > 0) {
            $fila = mysqli_fetch_assoc($res_carrito);
            $id_carrito = (int)$fila['id_carrito'];

            // No cerramos carritos vacios para no registrar compras nulas.
            $res_items = ew_stmt_result(
                $conexion,
                "SELECT id_item FROM carrito_items WHERE id_carrito = ? LIMIT 1",
                'i',
                [$id_carrito]
            );

            if ($res_items && mysqli_num_rows($res_items) > 0) {
                $ok = ew_stmt_execute(
                    $conexion,
                    "UPDATE carritos SET estado = 'finalizado' WHERE id_carrito = ?",
                    'i',
                    [$id_carrito]
                );

                if ($ok) {
                    $finalizado = true;
                    $mensaje = 'Compra finalizada (modo prueba).';
                } else {
                    $mensaje = 'No se pudo finalizar la compra. Intentalo de nuevo.';
                }
            } else {
                $mensaje = 'No puedes finalizar un carrito vacio.';
            }
        } else {
            $mensaje = 'No tienes carrito activo para finalizar.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Finalizar compra - ECO & WOODS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php ew_render_header(['active' => 'carrito']); ?>

<main>
    <div class="contenedor">
        <h1>Finalizar compra</h1>

        <?php if ($finalizado): ?>
            <p><strong><?php echo e($mensaje); ?></strong></p>
            <p>Esto es una simulacion: aqui iria el pago, direccion y confirmacion final.</p>
            <p><a href="index.php">Volver al inicio</a></p>
        <?php else: ?>
            <?php if ($mensaje !== ''): ?>
                <p><strong><?php echo e($mensaje); ?></strong></p>
            <?php endif; ?>

            <p>Vas a cerrar tu carrito activo en modo prueba.</p>

            <form action="finalizar_compra.php" method="post" class="formulario">
                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                <p>
                    <button type="submit">Confirmar finalizacion</button>
                </p>
            </form>

            <p><a href="ver_carrito.php">Volver al carrito</a></p>
        <?php endif; ?>
    </div>
</main>

<?php ew_render_footer(); ?>

<button id="btnTop" onclick="scrollToTop()">â–²</button>
<script src="js/app.js"></script>

</body>
</html>


