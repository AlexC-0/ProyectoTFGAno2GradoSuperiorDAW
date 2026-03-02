<?php
/*
DOCUMENTACION_PASO4
Formulario de inicio de sesion.
- Valida credenciales y crea sesion de usuario.
- Incluye proteccion CSRF y regeneracion de sesion.
- Guarda rol para habilitar vistas y permisos posteriores.
*/
// Bootstrap: sesion/utilidades globales; layout para header/footer consistente.
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
require 'conexion.php';

// Acumula errores de validacion/autenticacion para mostrarlos en bloque.
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protege el formulario de login frente a envios externos no legitimos.
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errores[] = "Sesion expirada. Recarga la pagina e intentalo de nuevo.";
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errores[] = "Debes introducir email y contrasena.";
    } elseif (empty($errores)) {
        // Se trae tambien es_admin para persistir rol en sesion.
        $sql = "SELECT id_usuario, nombre, email, password, es_admin
                FROM usuarios
                WHERE email = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $usuario = $resultado->fetch_assoc();
        $stmt->close();

        if ($usuario && password_verify($password, $usuario['password'])) {
            // Renueva el identificador de sesion para mitigar session fixation.
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $usuario['id_usuario'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['es_admin'] = (int) $usuario['es_admin'];

            header('Location: index.php');
            exit;
        }

        $errores[] = "Email o contrasena incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - ECO & WOODS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php ew_render_header(['mode' => 'auth', 'active' => 'login']); ?>

<main>
    <div class="contenedor">

        <h2>Iniciar sesion</h2>

        <?php if (!empty($errores)): ?>
            <div class="mensaje error">
                <ul>
                    <?php foreach ($errores as $e): ?>
                        <li><?php echo htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="login.php" method="post" class="formulario">
            <!-- Token anti-CSRF para validar origen del formulario -->
            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

            <p>
                <label for="email">Email</label><br>
                <input type="email" name="email" id="email" required
                       value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
            </p>

            <p>
                <label for="password">Contrasena</label><br>
                <input type="password" name="password" id="password" required>
            </p>

            <p>
                <button type="submit">Entrar</button>
            </p>
        </form>

    </div>
</main>

<?php ew_render_footer(); ?>

<button id="btnTop" onclick="scrollToTop()">?</button>
<script src="js/app.js"></script>

</body>
</html>

