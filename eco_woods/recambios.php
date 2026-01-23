<?php
session_start();
require_once "conexion.php";

$sql = "SELECT * FROM recambios3d ORDER BY id_recambio DESC";
$resultado = mysqli_query($conexion, $sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recambios 3D ECO & WOODS</title>
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

            <!-- Carrito como icono (mantengo enlace, pero ya no canta como "sección") -->
            <a href="ver_carrito.php" class="nav-icon" aria-label="Carrito">
                <!-- SVG carrito -->
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M7 4h-2l-1 2v2h2l3.6 7.59-1.35 2.44A2 2 0 0 0 10 23h10v-2H10l1.1-2h7.45a2 2 0 0 0 1.8-1.1l3.58-6.49A1 1 0 0 0 23 9H7.42L7 8H4V6h2l1-2Z" fill="currentColor"/>
                </svg>
            </a>

            <?php if (isset($_SESSION['usuario_id'])): ?>

                <?php if (!empty($_SESSION['es_admin']) && $_SESSION['es_admin'] == 1): ?>
                    <a href="admin.php">Panel Admin</a>
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

        <h1>Recambios 3D ECO & WOODS</h1>

        <p>
            Aquí encontrarás los <strong>recambios oficiales</strong> que ECO & WOODS pone a disposición
            para complementar los muebles de segunda mano:
            bisagras, topes, piezas específicas y otros componentes impresos en 3D.
        </p>

        <hr>

        <!-- Toast / mensaje flotante -->
        <div id="toastCarrito" class="toast-carrito" style="display:none;"></div>

        <?php
        if ($resultado && mysqli_num_rows($resultado) > 0) {

            while ($fila = mysqli_fetch_assoc($resultado)) {
                $idRec = (int)$fila['id_recambio'];

                echo '<div class="tarjeta">';
                echo "<h3>" . htmlspecialchars($fila['nombre']) . "</h3>";
                echo "<p><strong>Descripción:</strong> " . htmlspecialchars($fila['descripcion']) . "</p>";
                echo "<p><strong>Tipo:</strong> " . htmlspecialchars($fila['tipo']) . "</p>";
                echo "<p><strong>Compatible con:</strong> " . htmlspecialchars($fila['compatible_con']) . "</p>";
                echo "<p><strong>Precio:</strong> " . htmlspecialchars($fila['precio']) . " €</p>";

                // Botón icono carrito (sin redirección)
                echo '<div class="tarjeta-footer">';
                echo '  <button type="button" class="btn-carrito-icono" data-id="' . $idRec . '" aria-label="Añadir al carrito">';
                echo '      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">';
                echo '          <path d="M7 4h-2l-1 2v2h2l3.6 7.59-1.35 2.44A2 2 0 0 0 10 23h10v-2H10l1.1-2h7.45a2 2 0 0 0 1.8-1.1l3.58-6.49A1 1 0 0 0 23 9H7.42L7 8H4V6h2l1-2Z" fill="currentColor"/>';
                echo '      </svg>';
                echo '  </button>';
                echo '</div>';

                echo "</div>";
            }

        } else {
            echo "<p>De momento no hay recambios 3D disponibles en el catálogo.</p>";
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

<!-- JS específico: añadir al carrito SIN redirigir -->
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

    const botones = document.querySelectorAll('.btn-carrito-icono');
    botones.forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.getAttribute('data-id');
            if (!id) return;

            btn.disabled = true;

            try {
                const resp = await fetch('add_carrito.php?id_recambio=' + encodeURIComponent(id), {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
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
})();
</script>

</body>
</html>
