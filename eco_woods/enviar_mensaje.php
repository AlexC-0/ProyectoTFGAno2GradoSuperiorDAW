<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';

// Solo usuarios logueados
ew_require_login('login.php');

require 'conexion.php';

$id_usuario_actual = (int) $_SESSION['usuario_id'];

if (!isset($_GET['id_mueble'])) {
    header("Location: muebles.php");
    exit;
}

$id_mueble = (int) $_GET['id_mueble'];

// Cargar datos del mueble y del vendedor
$sql_mueble = "SELECT m.id_mueble, m.titulo, m.id_usuario AS id_vendedor, u.nombre AS nombre_vendedor
               FROM muebles m
               JOIN usuarios u ON m.id_usuario = u.id_usuario
               WHERE m.id_mueble = $id_mueble
               LIMIT 1";

$res_mueble = mysqli_query($conexion, $sql_mueble);

if (!$res_mueble || mysqli_num_rows($res_mueble) === 0) {
    die("El mueble no existe.");
}

$datos_mueble = mysqli_fetch_assoc($res_mueble);
$id_vendedor  = (int) $datos_mueble['id_vendedor'];

// Evitar que el vendedor se mande mensajes a sí mismo desde aquí
if ($id_vendedor === $id_usuario_actual) {
    header("Location: ver_mueble.php?id_mueble=$id_mueble");
    exit;
}

$errores = [];
$exito   = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errores[] = "Sesion expirada. Recarga la pagina e intentalo de nuevo.";
    }
    $asunto = trim($_POST['asunto'] ?? '');
    $cuerpo = trim($_POST['cuerpo'] ?? '');

    if ($asunto === '' || $cuerpo === '') {
        $errores[] = "Debes rellenar asunto y mensaje.";
    }

    if (empty($errores)) {
        $asunto_esc = mysqli_real_escape_string($conexion, $asunto);
        $cuerpo_esc = mysqli_real_escape_string($conexion, $cuerpo);

        $sql_insert = "INSERT INTO mensajes
                       (id_remitente, id_destinatario, id_mueble, asunto, cuerpo)
                       VALUES
                       ($id_usuario_actual, $id_vendedor, $id_mueble, '$asunto_esc', '$cuerpo_esc')";

        $ok = mysqli_query($conexion, $sql_insert);

        if ($ok) {
            $exito = "Mensaje enviado correctamente.";
            $asunto = $cuerpo = '';
        } else {
            $errores[] = "Error al enviar el mensaje. Inténtalo de nuevo.";
        }
    }
}

// Asunto por defecto
$asunto_por_defecto = "Consulta sobre: " . $datos_mueble['titulo'];
if (empty($asunto)) {
    $asunto = $asunto_por_defecto;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Enviar mensaje - ECO & WOODS</title>
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
            <a href="ver_carrito.php">Carrito</a>
            <a href="publicar.php">Publicar mueble</a>

            <?php if (isset($_SESSION['usuario_id'])): ?>
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

        <p><a href="ver_mueble.php?id_mueble=<?php echo (int)$id_mueble; ?>">← Volver al mueble</a></p>

        <h1>Enviar mensaje al vendedor</h1>

        <p>
            <strong>Mueble:</strong> <?php echo htmlspecialchars($datos_mueble['titulo']); ?><br>
            <strong>Vendedor:</strong> <?php echo htmlspecialchars($datos_mueble['nombre_vendedor']); ?>
        </p>

        <?php if (!empty($errores)): ?>
            <div class="mensaje error">
                <ul>
                    <?php foreach ($errores as $e): ?>
                        <li><?php echo htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($exito !== ""): ?>
            <div class="mensaje exito">
                <?php echo htmlspecialchars($exito); ?>
            </div>
        <?php endif; ?>

        <form action="enviar_mensaje.php?id_mueble=<?php echo (int)$id_mueble; ?>"
              method="post" class="formulario">
            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

            <p>
                <label for="asunto">Asunto</label><br>
                <input type="text" name="asunto" id="asunto" required
                       value="<?php echo htmlspecialchars($asunto); ?>">
            </p>

            <p>
                <label for="cuerpo">Mensaje</label><br>
                <textarea name="cuerpo" id="cuerpo" rows="6" required><?php
                    echo htmlspecialchars($cuerpo ?? '');
                ?></textarea>
            </p>

            <p>
                <button type="submit">Enviar mensaje</button>
            </p>

        </form>

    </div>
</main>

<footer>
    <div class="contenedor">
        ECO & WOODS - Proyecto Trabajo Fin de Grado
    </div>
</footer>

<button id="btnTop" onclick="scrollToTop()">▲</button>
<script src="js/app.js"></script>

</body>
</html>
