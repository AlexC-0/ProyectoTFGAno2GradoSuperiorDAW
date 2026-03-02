<?php
/*
DOCUMENTACION_PASO4
Portada principal del proyecto.
- Usa layout comun para mantener estructura global.
- Presenta propuesta de valor y accesos rapidos del sitio.
- Sirve como punto de entrada estable para navegacion.
*/
// Bootstrap: sesión + utilidades compartidas (escape, CSRF, etc.).
require_once __DIR__ . '/includes/bootstrap.php';
// Layout común para reutilizar cabecera y pie en toda la web.
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

<?php
// Cabecera pública de portada. active=index marca estado visual de navegación.
ew_render_header(['active' => 'index', 'brand_alt' => 'GR-Inn']);
?>

<main>
    <div class="contenedor">

        <!-- TARJETA 1: Presentación -->
        <div class="tarjeta">
            <h2>GR-Inn</h2>
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
                GR-Inn está orientada a facilitar ese proceso con una experiencia práctica y directa.
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

<?php ew_render_footer(['variant' => 'full']); ?>

<button id="btnTop" onclick="scrollToTop()">▲</button>
<script src="js/app.js"></script>
</body>
</html>


