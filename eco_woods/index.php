<?php
/*
DOCUMENTACION_PASO4
Portada principal del proyecto.
- Usa layout comun para mantener estructura global.
- Presenta propuesta de valor y accesos rapidos del sitio.
- Sirve como punto de entrada estable para navegacion.
*/
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>GR-Inn - Inicio</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php ew_render_header(['active' => 'index', 'brand_alt' => 'GR-Inn']); ?>

<main>
    <div class="contenedor">
        <section class="home-hero">
            <p class="home-kicker">Compra, venta y reparacion responsable</p>
            <h1>Mercado circular para muebles y recambios 3D</h1>
            <p class="home-lead">
                GR-Inn conecta personas que quieren vender, comprar y alargar la vida de sus muebles.
                Una plataforma directa, clara y enfocada en reutilizacion real.
            </p>
            <div class="landing-acciones">
                <a class="btn" href="muebles.php">Explorar muebles</a>
                <a class="btn" href="recambios.php">Explorar recambios</a>
                <a class="btn btn-soft" href="publicar.php">Publicar anuncio</a>
            </div>
            <div class="home-metrics">
                <span class="stat-chip">Segunda mano con criterio</span>
                <span class="stat-chip">Reparacion con impresion 3D</span>
                <span class="stat-chip">Contacto directo entre usuarios</span>
            </div>
        </section>

        <section class="home-grid">
            <article class="home-panel">
                <h2>Compraventa de muebles</h2>
                <p>
                    Publica anuncios claros y encuentra oportunidades por categoria, precio y ubicacion.
                    El objetivo es facilitar decisiones rapidas sin ruido innecesario.
                </p>
            </article>

            <article class="home-panel">
                <h2>Recambios 3D</h2>
                <p>
                    Si una pieza falla, no siempre hace falta reemplazar el mueble completo.
                    El catalogo de recambios ayuda a reparar y extender vida util.
                </p>
            </article>

            <article class="home-panel">
                <h2>Experiencia transparente</h2>
                <p>
                    Informacion legible, procesos directos y gestion personal de anuncios, favoritos,
                    carrito y mensajes desde el perfil del usuario.
                </p>
            </article>
        </section>
    </div>
</main>

<?php ew_render_footer(['variant' => 'full']); ?>

<button id="btnTop" onclick="scrollToTop()">?</button>
<script src="js/app.js"></script>
</body>
</html>

/*BORRAR*/
