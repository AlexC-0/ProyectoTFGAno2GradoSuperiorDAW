<?php







declare(strict_types=1);

function ew_nav_cart_count(): int
{
    if (empty($_SESSION['usuario_id'])) {
        return 0;
    }

    require_once __DIR__ . '/../conexion.php';
    global $conexion;

    if (!isset($conexion) || !$conexion instanceof mysqli) {
        return 0;
    }

    $stmt = mysqli_prepare(
        $conexion,
        "SELECT COALESCE(SUM(ci.cantidad), 0) AS total
         FROM carritos c
         JOIN carrito_items ci ON c.id_carrito = ci.id_carrito
         WHERE c.id_usuario = ? AND c.estado = 'activo'"
    );

    if (!$stmt) {
        return 0;
    }

    $idUsuario = (int)$_SESSION['usuario_id'];
    mysqli_stmt_bind_param($stmt, 'i', $idUsuario);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $fila = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    return $fila ? (int)$fila['total'] : 0;
}



function ew_render_header(array $options = []): void
{
    
    
    
    $mode = $options['mode'] ?? 'app';
    
    $active = $options['active'] ?? '';
    
    $brandAlt = $options['brand_alt'] ?? 'ECO & WOODS';
    
    $showCart = (bool)($options['show_cart'] ?? true);

    
    $isLogged = isset($_SESSION['usuario_id']);
    $isAdmin = !empty($_SESSION['es_admin']) && (int)$_SESSION['es_admin'] === 1;
    $cartCount = ($isLogged && $showCart) ? ew_nav_cart_count() : 0;
    ?>
<header>
    <div class="contenedor">
        <h1 class="brand-wrap">
            <img src="uploads/Verde.png" alt="<?php echo e($brandAlt); ?>" class="brand-logo">
        </h1>
        <nav>
            <a href="index.php" class="<?php echo ($active === 'index') ? 'activo' : ''; ?>">Inicio</a>
            <?php if ($mode === 'auth'): ?>
                <a href="login.php" class="<?php echo ($active === 'login') ? 'activo' : ''; ?>">Log-In</a>
            <?php else: ?>
                <?php if ($isLogged): ?>
                    <a href="muebles.php" class="<?php echo ($active === 'muebles') ? 'activo' : ''; ?>">Muebles</a>
                    <a href="recambios.php" class="<?php echo ($active === 'recambios') ? 'activo' : ''; ?>">Recambios 3D</a>
                    <?php if ($isAdmin): ?>
                        <a href="publicar.php">Publicar</a>
                        <a href="admin.php" class="<?php echo ($active === 'admin') ? 'activo' : ''; ?>">Panel Admin</a>
                    <?php else: ?>
                        <a href="publicar.php">Publicar mueble</a>
                    <?php endif; ?>
                    <a href="mi_perfil.php" class="<?php echo ($active === 'perfil') ? 'activo' : ''; ?>">Mi perfil</a>
                    <a href="logout.php">Cerrar sesion</a>
                    <?php if ($showCart): ?>
                        <a href="ver_carrito.php" class="nav-icon nav-cart <?php echo ($active === 'carrito') ? 'activo' : ''; ?>" aria-label="Carrito">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M7 4h-2l-1 2v2h2l3.6 7.59-1.35 2.44A2 2 0 0 0 10 23h10v-2H10l1.1-2h7.45a2 2 0 0 0 1.8-1.1l3.58-6.49A1 1 0 0 0 23 9H7.42L7 8H4V6h2l1-2Z" fill="currentColor"/>
                            </svg>
                            <span class="cart-badge" data-cart-count <?php echo ($cartCount > 0) ? '' : 'hidden'; ?>>
                                <?php echo ($cartCount > 99) ? '99+' : (int)$cartCount; ?>
                            </span>
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="login.php" class="<?php echo ($active === 'login') ? 'activo' : ''; ?>">Log-In</a>
                <?php endif; ?>
            <?php endif; ?>
        </nav>
    </div>
</header>
<?php
}

function ew_render_footer(array $options = []): void
{
    
    
    
    $variant = $options['variant'] ?? 'full';
    ?>
<footer>
    <div class="contenedor <?php echo ($variant === 'full') ? 'footer-flex' : ''; ?>">
        <?php if ($variant === 'full'): ?>
            <div class="footer-texto">GR-Inn - Proyecto Trabajo Fin de Grado</div>
            <div class="footer-contacto">
                <span style="margin-right:6px; font-weight:600;">Contacto:</span>
                <a class="footer-icon" href="https://mail.google.com/" target="_blank" rel="noopener noreferrer" aria-label="Acceso a Gmail" title="Gmail">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 6h16v12H4V6Z" stroke="currentColor" stroke-width="2"/>
                        <path d="M4 7l8 6 8-6" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </a>
                <a class="footer-icon" href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer" aria-label="Acceso a Instagram" title="Instagram">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <rect x="4" y="4" width="16" height="16" rx="4" stroke="currentColor" stroke-width="2"/>
                        <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="2"/>
                        <circle cx="17" cy="7" r="1" fill="currentColor"/>
                    </svg>
                </a>
            </div>
        <?php else: ?>
            GR-Inn - Proyecto Trabajo Fin de Grado
        <?php endif; ?>
    </div>
</footer>
<?php
}

