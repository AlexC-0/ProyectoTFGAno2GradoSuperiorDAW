<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>ECO & WOODS - Inicio</title>
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

            <!-- Carrito como icono -->
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

        <!-- TARJETA 1: Presentación -->
        <div class="tarjeta">
            <h2>ECO & WOODS</h2>
            <p>
                Plataforma para la compra y venta de muebles de segunda mano y recambios impresos en 3D.
                Un espacio sencillo, intuitivo y orientado a dar una segunda vida al mobiliario con confianza.
            </p>

            <div class="landing-acciones">
                <a class="btn" href="muebles.php">Ver muebles</a>
                <a class="btn" href="recambios.php">Ver recambios 3D</a>
            </div>
        </div>

        <!-- TARJETA 2: Qué puedes hacer -->
        <div class="tarjeta">
            <h2>¿Qué puedes hacer aquí?</h2>

            <div class="landing-cajas">
                <div class="landing-caja">
                    <h3>Comprar y vender muebles</h3>
                    <p>
                        Publica muebles de segunda mano de forma clara y accesible.
                        Explora anuncios con información real y encuentra oportunidades cerca de ti.
                    </p>
                </div>

                <div class="landing-caja">
                    <h3>Reparar con recambios 3D</h3>
                    <p>
                        Un mueble no tiene por qué acabar en la basura por una pieza rota.
                        Los recambios 3D ayudan a prolongar su vida útil.
                    </p>
                </div>

                <div class="landing-caja">
                    <h3>Confianza y claridad</h3>
                    <p>
                        Menos ruido y más utilidad: descripciones, fotos reales y una estructura pensada
                        para que el usuario encuentre lo que busca sin complicaciones.
                    </p>
                </div>
            </div>
        </div>

        <!-- TARJETA 3: Enfoque sostenible -->
        <div class="tarjeta">
            <h2>Segunda vida, mejor impacto</h2>
            <p>
                Reutilizar reduce residuos, ahorra recursos y mantiene el valor de productos que todavía pueden usarse.
                ECO & WOODS está orientada a facilitar ese proceso con una experiencia práctica y directa.
            </p>
        </div>

        <!-- TARJETA 4: Accesos -->
        <div class="tarjeta">
            <h2>Accesos rápidos</h2>

            <div class="landing-acciones">
                <a class="btn" href="muebles.php">Explorar muebles</a>
                <a class="btn" href="recambios.php">Explorar recambios 3D</a>
                <a class="btn" href="publicar.php">Publicar</a>
                <a class="btn" href="ver_carrito.php">Ver carrito</a>
            </div>
        </div>

    </div>
</main>

<footer>
    <div class="contenedor footer-flex">
        <div class="footer-texto">
            ECO & WOODS - Proyecto Trabajo Fin de Grado
        </div>

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

            <a class="footer-icon" href="#" aria-label="X / Twitter" title="X">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </a>

            <a class="footer-icon" href="#" aria-label="Facebook" title="Facebook">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M14 8h2V5h-2c-2.2 0-4 1.8-4 4v2H8v3h2v6h3v-6h2.2l.8-3H13V9c0-.6.4-1 1-1Z" fill="currentColor"/>
                </svg>
            </a>
        </div>
    </div>
</footer>

<button id="btnTop" onclick="scrollToTop()">▲</button>
<script src="js/app.js"></script>
</body>
</html>
