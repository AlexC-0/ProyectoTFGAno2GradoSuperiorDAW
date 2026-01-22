<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

require 'conexion.php';

$id_usuario_actual = (int)$_SESSION['usuario_id'];

// Comprobar parámetro id del mensaje
if (!isset($_GET['id'])) {
    header("Location: mi_perfil.php");
    exit;
}

$id_mensaje = (int)$_GET['id'];

// Cargar datos del mensaje + remitente + destinatario + mueble (si lo hay)
$sql = "SELECT msg.*,
               ur.nombre AS nombre_remitente,
               ud.nombre AS nombre_destinatario,
               m.titulo AS titulo_mueble,
               m.id_mueble AS id_mueble
        FROM mensajes msg
        JOIN usuarios ur ON msg.id_remitente = ur.id_usuario
        JOIN usuarios ud ON msg.id_destinatario = ud.id_usuario
        LEFT JOIN muebles m ON msg.id_mueble = m.id_mueble
        WHERE msg.id_mensaje = $id_mensaje
        LIMIT 1";

$res = mysqli_query($conexion, $sql);

if (!$res || mysqli_num_rows($res) === 0) {
    die("El mensaje no existe.");
}

$mensaje = mysqli_fetch_assoc($res);

// Comprobar que el usuario actual es remitente o destinatario
$id_remitente   = (int)$mensaje['id_remitente'];
$id_destinatario= (int)$mensaje['id_destinatario'];

if ($id_usuario_actual !== $id_remitente && $id_usuario_actual !== $id_destinatario) {
    die("No tienes permiso para ver este mensaje.");
}

// Si soy el destinatario y el mensaje no estaba leído, marcar como leído
if ($id_usuario_actual === $id_destinatario && (int)$mensaje['leido'] === 0) {
    $sql_leido = "UPDATE mensajes SET leido = 1 WHERE id_mensaje = $id_mensaje LIMIT 1";
    mysqli_query($conexion, $sql_leido);
}

$errores = [];
$exito   = "";

// Preparar datos para la respuesta
// Si yo soy el remitente original, responderé al destinatario.
// Si yo soy el destinatario original, responderé al remitente.
if ($id_usuario_actual === $id_remitente) {
    $id_destinatario_respuesta = $id_destinatario;
} else {
    $id_destinatario_respuesta = $id_remitente;
}

$id_mueble_asociado = !empty($mensaje['id_mueble']) ? (int)$mensaje['id_mueble'] : null;

// Asunto de respuesta (RE: ...)
$asunto_original = $mensaje['asunto'];
if (strpos($asunto_original, 'RE: ') === 0) {
    $asunto_respuesta = $asunto_original;
} else {
    $asunto_respuesta = 'RE: ' . $asunto_original;
}

// Envío de respuesta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cuerpo = trim($_POST['cuerpo'] ?? '');

    if ($cuerpo === '') {
        $errores[] = "El mensaje de respuesta no puede estar vacío.";
    }

    if (empty($errores)) {
        $cuerpo_esc = mysqli_real_escape_string($conexion, $cuerpo);
        $asunto_esc = mysqli_real_escape_string($conexion, $asunto_respuesta);
        $id_destino = $id_destinatario_respuesta;

        $sql_resp = "INSERT INTO mensajes
                     (id_remitente, id_destinatario, id_mueble, asunto, cuerpo)
                     VALUES
                     ($id_usuario_actual, $id_destino, " .
                     ($id_mueble_asociado !== null ? $id_mueble_asociado : "NULL") . ",
                     '$asunto_esc', '$cuerpo_esc')";

        $ok = mysqli_query($conexion, $sql_resp);

        if ($ok) {
            $exito = "Respuesta enviada correctamente.";
        } else {
            $errores[] = "Error al enviar la respuesta. Inténtalo de nuevo.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mensaje - ECO & WOODS</title>
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

        <p><a href="mi_perfil.php">← Volver a mi perfil</a></p>

        <article class="tarjeta">
            <h1><?php echo htmlspecialchars($mensaje['asunto']); ?></h1>

            <p>
                <strong>De:</strong>
                <?php echo htmlspecialchars($mensaje['nombre_remitente']); ?><br>
                <strong>Para:</strong>
                <?php echo htmlspecialchars($mensaje['nombre_destinatario']); ?><br>
                <strong>Fecha:</strong>
                <?php echo htmlspecialchars($mensaje['fecha_envio']); ?>
            </p>

            <?php if (!empty($mensaje['titulo_mueble']) && !empty($mensaje['id_mueble'])): ?>
                <p>
                    <strong>Mueble relacionado:</strong>
                    <a href="ver_mueble.php?id_mueble=<?php echo (int)$mensaje['id_mueble']; ?>">
                        <?php echo htmlspecialchars($mensaje['titulo_mueble']); ?>
                    </a>
                </p>
            <?php endif; ?>

            <hr>

            <p><?php echo nl2br(htmlspecialchars($mensaje['cuerpo'])); ?></p>
        </article>

        <hr>

        <h2>Responder</h2>

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

        <form action="ver_mensaje.php?id=<?php echo (int)$id_mensaje; ?>"
              method="post" class="formulario">

            <p>
                <label>Asunto</label><br>
                <input type="text"
                       value="<?php echo htmlspecialchars($asunto_respuesta); ?>"
                       disabled>
            </p>

            <p>
                <label for="cuerpo">Mensaje</label><br>
                <textarea name="cuerpo" id="cuerpo" rows="6" required></textarea>
            </p>

            <p>
                <button type="submit">Enviar respuesta</button>
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
