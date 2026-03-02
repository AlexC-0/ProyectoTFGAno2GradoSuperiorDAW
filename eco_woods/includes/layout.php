<?php
/*
DOCUMENTACION_PASO4
Este archivo renderiza cabecera y pie comunes del sitio.
- Centraliza navegacion, marca activa y variantes visuales.
- Evita repetir HTML estructural en cada pagina.
- Mantiene una experiencia coherente en todo el proyecto.
*/
declare(strict_types=1);

// Renderizador comun de cabecera:
// centraliza navbar, marca y estado de sesion para evitar duplicacion.
function ew_render_header(array $options = []): void
{
    // mode:
    // - app  -> navegacion completa de la aplicacion
    // - auth -> navegacion minima para login/registro
    $mode = $options['mode'] ?? 'app';
    // active se usa para marcar visualmente la seccion activa.
    $active = $options['active'] ?? '';
    // Permite personalizar ALT de logotipo segun contexto de pagina.
    $brandAlt = $options['brand_alt'] ?? 'ECO & WOODS';
    // showCart permite ocultar icono carrito en paginas concretas.
    $showCart = (bool)($options['show_cart'] ?? true);

    // Estado de autenticacion y rol para decidir opciones del menu.
    $isLogged = isset($_SESSION['usuario_id']);
    $isAdmin = !empty($_SESSION['es_admin']) && (int)$_SESSION['es_admin'] === 1;
    ?>
<header>
    <div class="contenedor">
        <h1 style="display:flex; align-items:center;">
            <img src="uploads/Verde.png" alt="<?php echo e($brandAlt); ?>" style="height:180px; width:auto; object-fit:contain; display:block;">
        </h1>
        <nav>
            <a href="index.php" class="<?php echo ($active === 'index') ? 'activo' : ''; ?>">Inicio</a>
            <?php if ($mode === 'auth'): ?>
                <a href="login.php" class="<?php echo ($active === 'login') ? 'activo' : ''; ?>">Login</a>
                <a href="registro.php" class="<?php echo ($active === 'registro') ? 'activo' : ''; ?>">Registro</a>
            <?php else: ?>
                <a href="muebles.php" class="<?php echo ($active === 'muebles') ? 'activo' : ''; ?>">Muebles</a>
                <a href="recambios.php" class="<?php echo ($active === 'recambios') ? 'activo' : ''; ?>">Recambios 3D</a>
                <?php if ($showCart): ?>
                    <a href="ver_carrito.php" class="nav-icon" aria-label="Carrito">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M7 4h-2l-1 2v2h2l3.6 7.59-1.35 2.44A2 2 0 0 0 10 23h10v-2H10l1.1-2h7.45a2 2 0 0 0 1.8-1.1l3.58-6.49A1 1 0 0 0 23 9H7.42L7 8H4V6h2l1-2Z" fill="currentColor"/>
                        </svg>
                    </a>
                <?php endif; ?>
                <?php if ($isLogged): ?>
                    <?php if ($isAdmin): ?>
                        <a href="publicar.php">Publicar</a>
                        <a href="admin.php" class="<?php echo ($active === 'admin') ? 'activo' : ''; ?>">Panel Admin</a>
                    <?php else: ?>
                        <a href="publicar.php">Publicar mueble</a>
                    <?php endif; ?>
                    <span class="saludo">Hola, <?php echo e((string)($_SESSION['usuario_nombre'] ?? 'Usuario')); ?></span>
                    <a href="mi_perfil.php" class="<?php echo ($active === 'perfil') ? 'activo' : ''; ?>">Mi perfil</a>
                    <a href="logout.php">Cerrar sesion</a>
                <?php else: ?>
                    <a href="login.php" class="<?php echo ($active === 'login') ? 'activo' : ''; ?>">Login</a>
                    <a href="registro.php" class="<?php echo ($active === 'registro') ? 'activo' : ''; ?>">Registro</a>
                <?php endif; ?>
            <?php endif; ?>
        </nav>
    </div>
</header>
<?php
}

function ew_render_footer(array $options = []): void
{
    // variant:
    // - simple -> texto base
    // - full   -> texto + iconos/contacto
    $variant = $options['variant'] ?? 'simple';
    ?>
<footer>
    <div class="contenedor <?php echo ($variant === 'full') ? 'footer-flex' : ''; ?>">
        <?php if ($variant === 'full'): ?>
            <div class="footer-texto">GR-Inn - Proyecto Trabajo Fin de Grado</div>
            <div class="footer-contacto">
                <span style="margin-right:6px; font-weight:600;">Contacto:</span>
                <a class="footer-icon" href="mailto:contacto@ecoandwoods.es" aria-label="Correo de contacto" title="Correo">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 6h16v12H4V6Z" stroke="currentColor" stroke-width="2"/>
                        <path d="M4 7l8 6 8-6" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </a>
                <a class="footer-icon" href="#" aria-label="Instagram" title="Instagram">
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

