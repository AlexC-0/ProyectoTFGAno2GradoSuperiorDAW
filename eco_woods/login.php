<?php
session_start();
require 'conexion.php';

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errores[] = "Debes introducir email y contraseña.";
    } else {
        // OJO: ahora también traemos es_admin
        $sql = "SELECT id_usuario, nombre, email, password, es_admin 
                FROM usuarios 
                WHERE email = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $usuario = $resultado->fetch_assoc();
        $stmt->close();

        if ($usuario && password_verify($password, $usuario['password'])) {
            $_SESSION['usuario_id']     = $usuario['id_usuario'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_email']  = $usuario['email'];
            // NUEVO: guardar si es admin o no
            $_SESSION['es_admin']       = (int)$usuario['es_admin'];

            header("Location: index.php");
            exit;
        } else {
            $errores[] = "Email o contraseña incorrectos.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - ECO & WOODS</title>
    <!-- Unificamos CSS con el resto del proyecto -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>
    <div class="contenedor">
        <h1>ECO & WOODS</h1>
        <nav>
            <a href="index.php">Inicio</a>
            <a href="login.php">Login</a>
            <a href="registro.php">Registro</a>
        </nav>
    </div>
</header>

<main>
    <div class="contenedor">

        <h2>Iniciar sesión</h2>

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
            <p>
                <label for="email">Email</label><br>
                <input type="email" name="email" id="email" required
                       value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
            </p>

            <p>
                <label for="password">Contraseña</label><br>
                <input type="password" name="password" id="password" required>
            </p>

            <p>
                <button type="submit">Entrar</button>
            </p>
        </form>

    </div>
</main>

<footer>
    <div class="contenedor">
        &copy; <?php echo date('Y'); ?> ECO & WOODS
    </div>
</footer>

<button id="btnTop" onclick="scrollToTop()">▲</button>
<script src="js/app.js"></script>

</body>
</html>
